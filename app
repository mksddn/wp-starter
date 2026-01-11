#!/bin/bash

URL_LOCAL=$URL_LOCAL
THEME_DIRECTORY=$THEME_DIRECTORY 

export REPOSITORY_NAME=$(basename $(git rev-parse --show-toplevel 2>/dev/null || echo $(pwd)))

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

# Import variables from .env
if [ -f .env ]; then
    export $(cat .env | grep -v '#' | awk '/=/ {print $1}')
else
    echo ".env file not found. Please create one."
    exit 1
fi

# WP-CLI call via function
wpcli() {
    docker compose -f docker/docker-compose.yml run --rm -e HOME=/tmp wpcli "$@"
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

# Install plugins
install_plugins() {
    local plugins=($(read_plugins))
    for plugin in "${plugins[@]}"; do
        wpcli plugin install "$plugin"
    done
    # wpcli plugin install "https://connect.advancedcustomfields.com/v2/plugins/download?p=pro&k=$ACF_KEY" --activate
}

# Start application
if [ "$1" == "up" ]; then
    docker compose -f docker/docker-compose.yml up -d

    retries=0
    until wpcli core install \
        --url=$URL_LOCAL --title=$URL_LOCAL \
        --admin_user=$ADMIN_USER --admin_password=$ADMIN_PASSWORD --admin_email=$ADMIN_EMAIL; do
        retries=$((retries + 1))
        echo "Couldn't connect to DB. Try - ${retries}. Retrying in 5 seconds..."
        sleep 3
        if [ "$retries" -eq 30 ]; then
            echo "Failed to connect to DB after 30 attempts. Exiting."
            exit 1
        fi
    done

    rm -rf wp-content/themes/twenty*
    wpcli theme activate $THEME_DIRECTORY
    wpcli plugin uninstall hello akismet
    install_plugins
    wpcli plugin activate --all
    wpcli config set WP_DEBUG true --raw
    open_browser "$URL_LOCAL/wp-admin"
    exit

# Stop application
elif [ "$1" == "stop" ]; then
    docker compose -f docker/docker-compose.yml stop
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
    docker compose -f docker/docker-compose.yml run --rm wordpress rm -rf dbdump.sql
    exit

# Import database
elif [ "$1" == "db-import" ]; then
    current_date_time=$(date +%Y%m%d%H%M)
    # wpcli db export --tables=wp_users,wp_usermeta users.sql
    wpcli db export $current_date_time.sql
    docker cp ${REPOSITORY_NAME}_wpcli://var/www/html/$current_date_time.sql ./backup-db/
    docker compose -f docker/docker-compose.yml run --rm wordpress rm -rf $current_date_time.sql
    docker cp *.sql ${REPOSITORY_NAME}_wpcli://var/www/html/
    wpcli db clean --yes
    wpcli db import *.sql
    docker compose -f docker/docker-compose.yml run --rm wordpress rm -rf *.sql
    # wpcli db import users.sql
    wpcli user create "$ADMIN_USER" "$ADMIN_EMAIL" --user_pass="$ADMIN_PASSWORD" --role=administrator
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
    docker compose -f docker/docker-compose.yml down
    rm -rf .srv wp-content/plugins wp-content/uploads wp-content/upgrade wp-content/ai1wm-backups
    rm -rf wp-content/index.php wp-content/themes/index.php wp-content/themes/twenty*
    rm -rf wp-content/themes/$THEME_DIRECTORY/{composer.lock,vendor,package-lock.json,node_modules}
    rm -rf {composer.lock,vendor,package-lock.json,node_modules}
    rm -rf dbdump.sql
    exit

fi
