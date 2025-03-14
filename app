#!/bin/bash

# Проверка наличия .env файла
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        cp .env.example .env
        echo "The .env file was created from .env.example. Check the settings in .env."
    else
        echo "Error: .env.example was not found, it is not possible to create .env."
        exit 1
    fi
fi

# Импорт переменных из .env
if [ -f .env ]; then
    export $(cat .env | grep -v '#' | awk '/=/ {print $1}')
else
    echo ".env file not found. Please create one."
    exit 1
fi

export REPOSITORY_NAME=$(basename $(git rev-parse --show-toplevel))

URL_LOCAL=$URL_LOCAL
URL_DEV=$URL_DEV
URL_PROD=$URL_PROD
THEME_DIRECTORY=$THEME_DIRECTORY 

# WP-CLI вызов через функцию
wpcli() {
    docker compose run --rm -e HOME=/tmp wpcli "$@"
}

# Считывание списка плагинов
read_plugins() {
    local plugins=()
    while IFS= read -r line; do
        plugins+=("$line")
    done <wp-content/themes/$THEME_DIRECTORY/plugins.txt
    echo "${plugins[@]}"
}

# Установка плагинов
install_plugins() {
    local plugins=($(read_plugins))
    for plugin in "${plugins[@]}"; do
        wpcli plugin install "$plugin"
    done
    wpcli plugin install "https://connect.advancedcustomfields.com/v2/plugins/download?p=pro&k=$ACF_KEY" --activate
}

# Запуск приложения
if [ "$1" == "up" ]; then
    docker compose up -d

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
    open $URL_LOCAL/wp-admin
    exit

# Остановка приложения
elif [ "$1" == "stop" ]; then
    docker compose stop
    exit

# Создание нового юзера (админа)
elif [ "$1" == "user-create" ]; then
    wpcli user create --prompt --role=administrator \
        --user_registered= --display_name= --user_nicename= --user_url= --nickname= \
        --first_name= --last_name= --description= --rich_editing= --send-email= --porcelain=n
    exit

# Экспорт БД
elif [ "$1" == "db-export" ]; then
    wpcli db export dbdump.sql
    docker cp ${REPOSITORY_NAME}_wpcli://var/www/html/dbdump.sql .
    docker compose run --rm wordpress rm -rf dbdump.sql
    exit

# Импорт БД
elif [ "$1" == "db-import" ]; then
    current_date_time=$(date +%Y%m%d%H%M)
    # wpcli db export --tables=wp_users,wp_usermeta users.sql
    wpcli db export $current_date_time.sql
    docker cp ${REPOSITORY_NAME}_wpcli://var/www/html/$current_date_time.sql ./backup-db/
    docker compose run --rm wordpress rm -rf $current_date_time.sql
    docker cp *.sql ${REPOSITORY_NAME}_wpcli://var/www/html/
    wpcli db clean --yes
    wpcli db import *.sql
    docker compose run --rm wordpress rm -rf *.sql
    wpcli search-replace ${URL_DEV} ${URL_LOCAL}
    wpcli search-replace ${URL_PROD} ${URL_LOCAL}
    # wpcli db import users.sql
    wpcli user create "$ADMIN_USER" "$ADMIN_EMAIL" --user_pass="$ADMIN_PASSWORD" --role=administrator
    exit

# Режим дебага
elif [ "$1" == "debug-on" ]; then
    wpcli config set WP_DEBUG true --raw
    exit
elif [ "$1" == "debug-off" ]; then
    wpcli config set WP_DEBUG false --raw
    exit

# Команды Composer
elif [ "$1" == "composer-install" ]; then
    cd wp-content/themes/$THEME_DIRECTORY
    composer install
    cd ../../..
    exit
elif [ "$1" == "lint:php" ]; then
    cd wp-content/themes/$THEME_DIRECTORY
    composer lint:php
    cd ../../..
    exit
elif [ "$1" == "lint:wpcs" ]; then
    cd wp-content/themes/$THEME_DIRECTORY
    composer lint:wpcs
    cd ../../..
    exit

# Команды NPM
elif [ "$1" == "npm-install" ]; then
    cd wp-content/themes/$THEME_DIRECTORY
    npm install
    cd ../../..
    exit
elif [ "$1" == "watch" ]; then
    cd wp-content/themes/$THEME_DIRECTORY
    npm run watch
    cd ../../..
    exit

# Очистка
elif [ "$1" == "clean" ]; then
    docker compose down
    rm -rf .srv wp-content/plugins wp-content/uploads wp-content/upgrade wp-content/ai1wm-backups
    rm -rf wp-content/index.php wp-content/themes/index.php wp-content/themes/twenty*
    rm -rf wp-content/themes/$THEME_DIRECTORY/{composer.lock,vendor,package-lock.json,node_modules}
    exit

fi
