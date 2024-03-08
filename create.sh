# Определяем цвета
RED='\033[1;31m'
GREEN='\033[1;32m'
NC='\033[0m' # No Color


# импортируем переменные из .env
if [ -f .env ]; then
  export $(echo $(cat .env | sed 's/#.*//g'| xargs) | envsubst)
else
  echo "${RED}Looks like you don't have .ENV file!${NC}"
  exit
fi


# именуем тему
# НЕ ЗАБУДЬ УКАЗАТЬ НАЗВАНИЕ В .ENV
mv content/themes/* content/themes/${THEME_SLUG}
touch content/themes/${THEME_SLUG}/style.css
echo "/**
* Theme Name: ${THEME_SLUG}
*/" | cat - content/themes/${THEME_SLUG}/style.css > temp && mv temp content/themes/${THEME_SLUG}/style.css


# проверяем установлен ли composer
if which composer; then
  echo "${GREEN}Composer is already installed.${NC}"
else
  echo "${RED}Composer is not installed, we'll fix it now!${NC}"
  curl -sS https://getcomposer.org/installer | php
fi


# устанавливаем wordpress и зависимости
composer install


# проверяем установлен ли wp-cli
if which wp > /dev/null; then
  echo "${GREEN}WP-CLI is already installed.${NC}"
else
  echo "${RED}WP-CLI is not installed, we'll fix it now!${NC}"
  curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
  php wp-cli.phar --info
  chmod +x wp-cli.phar
  sudo mv wp-cli.phar /usr/local/bin/wp
  # wp --info
fi

# создаем wp-config
# НУЖНО РАЗРЕШИТЬ УДАЛЕННЫЙ ДОСТУП К БД НА СТОРОНЕ СЕРВЕРА!!!
wp config create --dbhost=${DB_HOST} --dbname=${DB_NAME} --dbuser=${DB_USER} --dbpass=${DB_PASSWORD} --extra-php <<PHP
define('WP_CONTENT_DIR', dirname(__DIR__, 1) . '/content');
define('WP_CONTENT_URL', '${SITE_HOST}' . '/content');
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
PHP

# устанавливаем wordpress
echo "${GREEN}Installing WordPress may take time.${NC}"
wp core install --url=${SITE_HOST} --title=${SITE_TITLE} --admin_user=${ADMIN_USER} --admin_password=${ADMIN_PASSWORD} --admin_email=${ADMIN_EMAIL} --skip-email

# настраиваем cms
# активируем тему
wp theme activate ${THEME_SLUG}
# активируем плагины
wp plugin activate --all
# пермалинки
# wp rewrite flush
wp rewrite structure '/%postname%/'
# домашняя страница
wp post update 2 --post_title=Home --post_name=home
wp option update page_on_front 2
wp option update show_on_front page

# запускаем локальный сервер
wp server