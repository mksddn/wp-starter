wp core download --force --skip-content --allow-root
wp config create \
  --dbhost=$WORDPRESS_DB_HOST \
  --dbname=$WORDPRESS_DB_NAME \
  --dbuser=$WORDPRESS_DB_USER \
  --dbpass=$WORDPRESS_DB_PASSWORD \
  --allow-root
wp core install \
  --title=$TITLE \
  --url=$URL \
  --admin_user=$ADMIN_USER \
  --admin_password=$ADMIN_PASSWORD \
  --admin_email=$ADMIN_EMAIL \
  --skip-plugins \
  --skip-themes \
  --allow-root
chown -R www-data:www-data wp-content
chmod -R g+w wp-content

wp plugin install cyr2lat --allow-root --activate
wp plugin install "http://connect.advancedcustomfields.com/index.php?p=pro&a=download&k=$ACF_KEY" --allow-root

# активируем тему
wp theme activate $THEME_SLUG --allow-root
# активируем плагины
wp plugin activate --all --allow-root
# Пермалинки
wp rewrite structure '/%postname%/' --allow-root
# домашняя страница
wp post update 2 --post_title=Home --post_name=home --allow-root
wp option update page_on_front 2 --allow-root
wp option update show_on_front page --allow-root