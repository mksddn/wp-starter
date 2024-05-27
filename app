export REPOSITORY_NAME=$(basename `git rev-parse --show-toplevel`)
export URL_LOCAL=${URL_LOCAL}
export URL_DEV=${URL_DEV}
export URL_PROD=${URL_PROD}

export THEME_DIRECTORY='wp-theme'

# alias wp="docker-compose run --rm -e HOME=/tmp --user 33:33 wpcli"

if [ -f .env ]; then
  export $(echo $(cat .env | sed 's/#.*//g'| xargs) | envsubst)
else
  echo "Looks like you don't have .ENV file!"
  exit
fi

if [ "$1" == "install" ]; then
    docker-compose up -d
    # rm -rf wp-content/themes/twentytwentyfour wp-content/themes/twentytwentythree wp-content/themes/twentytwentytwo
    mv wp-content/themes/* wp-content/themes/$THEME_DIRECTORY
    rm -rf wp-content/themes/$THEME_DIRECTORY/style.css
    touch wp-content/themes/$THEME_DIRECTORY/style.css
    echo "/**
    * Theme Name: $THEME_SLUG
    */" | cat - wp-content/themes/$THEME_DIRECTORY/style.css > temp && mv temp wp-content/themes/$THEME_DIRECTORY/style.css

    xdg-open http://localhost:8000
    open http://localhost:8000
    start http://localhost:8000
    # python3 -m webbrowser http://localhost:8000
    exit

elif [ "$1" == "up" ]; then
    docker-compose up -d
    xdg-open http://localhost:8000
    open http://localhost:8000
    start http://localhost:8000
    # python3 -m webbrowser http://localhost:8000
    exit

elif [ "$1" == "stop" ]; then
    docker-compose stop
    exit

elif [ "$1" == "user-create" ]; then
    docker-compose run --rm -e HOME=/tmp --user 33:33 wpcli user create --prompt --role=administrator --user_registered= --display_name= --user_nicename= --user_url= --nickname= --first_name= --last_name= --description= --rich_editing= --send-email= --porcelain=n
    exit

elif [ "$1" == "config" ]; then
    rm -rf wp-content/themes/twentytwentyfour wp-content/themes/twentytwentythree wp-content/themes/twentytwentytwo
    docker-compose run --rm -e HOME=/tmp --user 33:33 wpcli theme activate $THEME_DIRECTORY

    docker-compose run --rm -e HOME=/tmp --user 33:33 wpcli plugin uninstall hello
    docker-compose run --rm -e HOME=/tmp --user 33:33 wpcli plugin uninstall akismet
    docker-compose run --rm -e HOME=/tmp --user 33:33 wpcli plugin install cyr2lat --activate
    docker-compose run --rm -e HOME=/tmp --user 33:33 wpcli plugin install "https://connect.advancedcustomfields.com/v2/plugins/download?p=pro&k=$ACF_KEY" --allow-root
    docker-compose run --rm -e HOME=/tmp --user 33:33 wpcli plugin activate --all

    docker-compose run --rm -e HOME=/tmp --user 33:33 wpcli rewrite structure '/%postname%/'

    docker-compose run --rm -e HOME=/tmp --user 33:33 wpcli post update 2 --post_title=Home --post_name=home
    docker-compose run --rm -e HOME=/tmp --user 33:33 wpcli option update page_on_front 2
    docker-compose run --rm -e HOME=/tmp --user 33:33 wpcli option update show_on_front page
    exit
    
elif [ "$1" == "dbexport" ]; then
    docker-compose run --rm -e HOME=/tmp --user 33:33 wpcli db export dbdump.sql --allow-root
    docker cp ${REPOSITORY_NAME}_wpcli://var/www/html/dbdump.sql .
    docker-compose run --rm wordpress rm -rf dbdump.sql
    exit

elif [ "$1" == "dbimport" ]; then
    docker cp *.sql ${REPOSITORY_NAME}_wpcli://var/www/html/
    docker-compose run --rm -e HOME=/tmp --user 33:33 wpcli db import *.sql --allow-root
    docker-compose run --rm wordpress rm -rf *.sql
    docker-compose run --rm -e HOME=/tmp --user 33:33 wpcli search-replace ${URL_DEV} ${URL_LOCAL}
    docker-compose run --rm -e HOME=/tmp --user 33:33 wpcli search-replace ${URL_PROD} ${URL_LOCAL}
    exit

elif [ "$1" == "debug-on" ]; then
    docker-compose run --rm -e HOME=/tmp --user 33:33 wpcli config set WP_DEBUG true --raw
    exit

elif [ "$1" == "debug-off" ]; then
    docker-compose run --rm -e HOME=/tmp --user 33:33 wpcli config set WP_DEBUG false --raw
    exit

elif [ "$1" == "composer-install" ]; then
    cd wp-content/themes/$THEME_DIRECTORY
    composer install
    cd ../..
    exit

elif [ "$1" == "lint:php" ]; then
    cd wp-content/themes/$THEME_DIRECTORY
    composer lint:php
    cd ../..
    exit

elif [ "$1" == "lint:wpcs" ]; then
    cd wp-content/themes/$THEME_DIRECTORY
    composer lint:wpcs
    cd ../..
    exit

elif [ "$1" == "npm-install" ]; then
    cd wp-content/themes/$THEME_DIRECTORY
    npm install
    cd ../..
    exit

elif [ "$1" == "watch" ]; then
    cd wp-content/themes/$THEME_DIRECTORY
    npm run watch
    cd ../..
    exit

elif [ "$1" == "compile:css" ]; then
    cd wp-content/themes/$THEME_DIRECTORY
    npm run compile:css
    cd ../..
    exit

elif [ "$1" == "clean" ]; then
    docker compose down
    rm -rf .srv --force
    rm -rf wp-content/plugins
    rm -rf wp-content/uploads
    rm -rf wp-content/upgrade
    rm -rf wp-content/index.php
    rm -rf wp-content/themes/index.php
    rm -rf wp-content/themes/twentytwentyfour wp-content/themes/twentytwentythree wp-content/themes/twentytwentytwo
    rm -rf wp-content/themes/$THEME_DIRECTORY/style.css
    rm -rf wp-content/themes/$THEME_DIRECTORY/vendor
    rm -rf wp-content/themes/$THEME_DIRECTORY/node_modules
    exit

fi