#!/bin/bash
#
# WordPress Docker Management Script
# Supports mobile device testing via local network access
#

THEME_DIRECTORY=$THEME_DIRECTORY 

export REPOSITORY_NAME=$(basename $(git rev-parse --show-toplevel 2>/dev/null || echo $(pwd)))

# Docker compose command with environment file
DOCKER_COMPOSE="docker compose --env-file .env -f docker/docker-compose.yml"

# Get local IP address (LAN, not loopback)
get_local_ip() {
    if [[ "$OSTYPE" == "darwin"* ]]; then
        local ip=$(ipconfig getifaddr en0 2>/dev/null)
        if [ -z "$ip" ]; then
            ip=$(ipconfig getifaddr en1 2>/dev/null)
        fi
        if [ -z "$ip" ]; then
            ip=$(ifconfig | grep "inet " | grep -v 127.0.0.1 | awk '{print $2}' | head -1)
        fi
        echo "${ip:-127.0.0.1}"
    elif [[ "$OSTYPE" == "linux-gnu"* ]]; then
        hostname -I | awk '{print $1}' || echo "127.0.0.1"
    elif [[ "$OSTYPE" == "msys" || "$OSTYPE" == "cygwin" || "$OSTYPE" == "msys2" ]]; then
        # Windows (Git Bash / Cygwin): ipconfig outputs "IPv4 Address . . . : 192.168.1.100"
        local ip=$(ipconfig 2>/dev/null | grep -i "IPv4" | sed 's/.*: *//' | tr -d '\r' | grep -v "^127\." | head -1)
        echo "${ip:-127.0.0.1}"
    else
        echo "127.0.0.1"
    fi
}

# Cross-platform browser open function
open_browser() {
    local url=$1
    if [[ "$OSTYPE" == "darwin"* ]]; then
        # macOS
        open "$url"
    elif [[ "$OSTYPE" == "linux-gnu"* ]]; then
        # Linux
        if command -v xdg-open &> /dev/null; then
            xdg-open "$url"
        elif command -v gnome-open &> /dev/null; then
            gnome-open "$url"
        else
            echo "Please open $url in your browser"
        fi
    elif [[ "$OSTYPE" == "msys" || "$OSTYPE" == "cygwin" ]]; then
        # Windows (Git Bash / Cygwin)
        start "$url"
    else
        echo "Please open $url in your browser"
    fi
}

# Check if .env file exists
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        cp .env.example .env
        echo "The .env file was created from .env.example. Check the settings in .env."
    else
        echo "Error: .env.example was not found, it is not possible to create .env."
        exit 1
    fi
fi

# Import variables from .env (strip \r for Windows CRLF)
if [ -f .env ]; then
    export $(cat .env | grep -v '#' | awk '/=/ {print $1}' | tr -d '\r')
else
    echo ".env file not found. Please create one."
    exit 1
fi

# Single site URL (LAN IP) — same address from desktop and mobile
LOCAL_IP=$(get_local_ip)
SITE_URL="http://${LOCAL_IP}:${WORDPRESS_PORT}"

# WP-CLI call via function
wpcli() {
    $DOCKER_COMPOSE run --rm -e HOME=/tmp wpcli "$@"
}

# Read plugins list
read_plugins() {
    local plugins=()
    local content=$(cat wp-content/themes/$THEME_DIRECTORY/plugins.txt | tr -d '\r')
    [[ "$content" != *$'\n' ]] && content="$content"$'\n'
    while IFS= read -r line; do
        [[ -n "$line" ]] && plugins+=("$line")
    done <<< "$content"
    echo "${plugins[@]}"
}

# Get list of installed plugin slugs (one per line). Normalize "slug/file.php" -> "slug"
get_installed_plugin_slugs() {
    wpcli plugin list --field=name 2>/dev/null | sed 's|/.*||' || echo ""
}

# Plugins to never install (removed by default)
PLUGINS_REMOVED="akismet hello hello-dolly"

# Install only plugins that are not yet installed
install_plugins() {
    local installed
    installed=$(get_installed_plugin_slugs)
    local plugins=($(read_plugins))
    for plugin in "${plugins[@]}"; do
        if echo "$PLUGINS_REMOVED" | grep -qw "$plugin" 2>/dev/null; then
            continue
        fi
        if echo "$installed" | grep -Fxq "$plugin" 2>/dev/null; then
            continue
        fi
        wpcli plugin install "$plugin"
    done
}

# On first DB init only: activate allowlisted plugins (plugins.txt). Plugins already
# present under wp-content/plugins but not listed here stay inactive — same as wp.org
# installs skipped by install_plugins when the folder already exists.
activate_listed_plugins() {
    local plugins=($(read_plugins))
    for plugin in "${plugins[@]}"; do
        if echo "$PLUGINS_REMOVED" | grep -qw "$plugin" 2>/dev/null; then
            continue
        fi
        wpcli plugin activate "$plugin" 2>/dev/null || true
    done
}

# Replace stored dev URLs in DB. Skips when home already equals SITE_URL.
# Optional arg: current home URL (avoids extra wp option get when caller has it)
replace_urls_in_db() {
    local current_home="${1:-}"
    if [ -z "$current_home" ]; then
        current_home=$(wpcli option get home 2>/dev/null || echo "")
    fi
    if [ "$current_home" = "$SITE_URL" ]; then
        return
    fi
    # Replace localhost and 127.0.0.1
    for old in "http://localhost:${WORDPRESS_PORT}" "http://127.0.0.1:${WORDPRESS_PORT}"; do
        if [ "$old" != "$SITE_URL" ]; then
            wpcli search-replace "$old" "$SITE_URL" --all-tables --quiet 2>/dev/null || true
        fi
    done
    # Replace old URL (including old IP addresses) if it differs from SITE_URL
    if [ -n "$current_home" ] && [ "$current_home" != "$SITE_URL" ]; then
        wpcli search-replace "$current_home" "$SITE_URL" --all-tables --quiet 2>/dev/null || true
    fi
}

# Start application
if [ "$1" == "up" ]; then
    $DOCKER_COMPOSE up -d

    echo "Checking WordPress..."
    # First-ever core install: turn on plugins.txt only; later runs never mass-activate.
    FRESH_WP_INSTALL=0
    if ! wpcli core is-installed 2>/dev/null; then
        FRESH_WP_INSTALL=1
        retries=0
        until wpcli core install \
            --url=$SITE_URL --title="$SITE_URL" \
            --admin_user=$ADMIN_USER --admin_password=$ADMIN_PASSWORD --admin_email=$ADMIN_EMAIL; do
            retries=$((retries + 1))
            echo "Couldn't connect to DB. Try - ${retries}. Retrying in 5 seconds..."
            sleep 3
            if [ "$retries" -eq 30 ]; then
                echo "Failed to connect to DB after 30 attempts. Exiting."
                exit 1
            fi
        done
    else
        current_home=$(wpcli option get home 2>/dev/null || echo "")
        if [ "$current_home" != "$SITE_URL" ]; then
            wpcli option update home "$SITE_URL"
            wpcli option update siteurl "$SITE_URL"
            # Replace old URLs in database after updating options
            replace_urls_in_db "$current_home"
        else
            # Even if URLs match, check for any old URLs in database
            replace_urls_in_db "$current_home"
        fi
    fi

    if compgen -G "wp-content/themes/twenty*" &>/dev/null; then
        rm -rf wp-content/themes/twenty*
    fi
    active_theme=$(wpcli theme list --status=active --field=name 2>/dev/null || echo "")
    if [ "$active_theme" != "$THEME_DIRECTORY" ]; then
        wpcli theme activate $THEME_DIRECTORY
    fi
    install_plugins
    if [ "$FRESH_WP_INSTALL" -eq 1 ]; then
        activate_listed_plugins
    fi
    installed_plugins=$(get_installed_plugin_slugs)
    for p in hello hello-dolly akismet; do
        if echo "$installed_plugins" | grep -Fxq "$p" 2>/dev/null; then
            wpcli plugin uninstall "$p" --deactivate 2>/dev/null || true
        fi
    done
    
    # Set debug mode based on environment
    if [ "$ENVIRONMENT" == "production" ]; then
        # Production mode: error logging only
        wpcli config set WP_DEBUG true --raw
        wpcli config set WP_DEBUG_LOG true --raw
        wpcli config set WP_DEBUG_DISPLAY false --raw
        wpcli config set SCRIPT_DEBUG false --raw
    else
        # Development mode: show all errors and enable script debugging
        wpcli config set WP_DEBUG true --raw
        wpcli config set WP_DEBUG_LOG true --raw
        wpcli config set WP_DEBUG_DISPLAY true --raw
        wpcli config set SCRIPT_DEBUG true --raw
    fi
    
    # Ensure URLs are updated one more time before opening browser
    current_home=$(wpcli option get home 2>/dev/null || echo "")
    if [ "$current_home" != "$SITE_URL" ]; then
        wpcli option update home "$SITE_URL"
        wpcli option update siteurl "$SITE_URL"
        replace_urls_in_db "$current_home"
    fi
    
    # Small delay to ensure WordPress processes the changes
    sleep 1
    
    echo ""
    echo "=========================================="
    echo "Site is ready!"
    echo "Open (desktop + mobile): $SITE_URL"
    echo "=========================================="
    echo ""
    open_browser "$SITE_URL/wp-admin"
    exit

# Stop application
elif [ "$1" == "stop" ]; then
    $DOCKER_COMPOSE stop
    exit

# Create new user (administrator)
elif [ "$1" == "user-create" ]; then
    wpcli user create --prompt --role=administrator \
        --user_registered= --display_name= --user_nicename= --user_url= --nickname= \
        --first_name= --last_name= --description= --rich_editing= --send-email= --porcelain=n
    exit

# Export database
elif [ "$1" == "db-export" ]; then
    wpcli db export dbdump.sql
    docker cp ${REPOSITORY_NAME}_wpcli://var/www/html/dbdump.sql .
    $DOCKER_COMPOSE run --rm wordpress rm -rf dbdump.sql
    exit

# Import database
elif [ "$1" == "db-import" ]; then
    mkdir -p backup-db
    current_date_time=$(date +%Y%m%d%H%M)
    # wpcli db export --tables=wp_users,wp_usermeta users.sql
    wpcli db export $current_date_time.sql
    docker cp ${REPOSITORY_NAME}_wpcli://var/www/html/$current_date_time.sql ./backup-db/
    $DOCKER_COMPOSE run --rm wordpress rm -rf $current_date_time.sql
    docker cp *.sql ${REPOSITORY_NAME}_wpcli://var/www/html/
    wpcli db clean --yes
    wpcli db import *.sql
    $DOCKER_COMPOSE run --rm wordpress rm -rf *.sql
    # wpcli db import users.sql
    wpcli user create "$ADMIN_USER" "$ADMIN_EMAIL" --user_pass="$ADMIN_PASSWORD" --role=administrator
    wpcli option update home "$SITE_URL"
    wpcli option update siteurl "$SITE_URL"
    replace_urls_in_db
    exit

# Debug mode
elif [ "$1" == "debug-on" ]; then
    if [ "$ENVIRONMENT" == "production" ]; then
        # Production mode: error logging only
        wpcli config set WP_DEBUG true --raw
        wpcli config set WP_DEBUG_LOG true --raw
        wpcli config set WP_DEBUG_DISPLAY false --raw
        wpcli config set SCRIPT_DEBUG false --raw
    else
        # Development mode: show all errors and enable script debugging
        wpcli config set WP_DEBUG true --raw
        wpcli config set WP_DEBUG_LOG true --raw
        wpcli config set WP_DEBUG_DISPLAY true --raw
        wpcli config set SCRIPT_DEBUG true --raw
    fi
    exit
elif [ "$1" == "debug-off" ]; then
    # Disable all debugging regardless of environment
    wpcli config set WP_DEBUG false --raw
    wpcli config set WP_DEBUG_LOG false --raw
    wpcli config set WP_DEBUG_DISPLAY false --raw
    wpcli config set SCRIPT_DEBUG false --raw
    exit

# Cleanup
elif [ "$1" == "clean" ]; then
    $DOCKER_COMPOSE down
    rm -rf .srv wp-content/plugins wp-content/uploads wp-content/upgrade wp-content/ai1wm-backups
    rm -rf wp-content/index.php wp-content/themes/index.php wp-content/themes/twenty*
    rm -rf wp-content/themes/$THEME_DIRECTORY/{composer.lock,vendor,package-lock.json,node_modules}
    rm -rf {composer.lock,vendor,package-lock.json,node_modules}
    rm -rf dbdump.sql
    exit

# Hot reload development server
elif [ "$1" == "hot-reload" ]; then
    THEME_PATH="wp-content/themes/$THEME_DIRECTORY"
    
    if [ ! -d "$THEME_PATH" ]; then
        echo "Error: Theme directory not found: $THEME_PATH"
        exit 1
    fi
    
    if [ ! -f "$THEME_PATH/hot-reload-server.js" ]; then
        echo "Error: hot-reload-server.js not found in $THEME_PATH"
        exit 1
    fi
    
    # Get site URL for display
    LOCAL_IP=$(get_local_ip)
    SITE_URL="http://${LOCAL_IP}:${WORDPRESS_PORT}"
    
    cd "$THEME_PATH" || exit 1
    
    # Check if node_modules exists, if not install dependencies
    if [ ! -d "node_modules" ]; then
        echo "Installing hot reload dependencies..."
        npm install
    fi
    
    # Export port from .env if exists, otherwise use default
    if [ -f "../../.env" ]; then
        export $(cat ../../.env | grep -v '#' | grep HOT_RELOAD_PORT | tr -d '\r')
    fi
    
    echo ""
    echo "Hot Reload: $SITE_URL"
    echo "Press Ctrl+C to stop"
    echo ""
    
    npm run hot-reload
    exit

fi
