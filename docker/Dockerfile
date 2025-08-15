FROM wordpress:php8.3-fpm-alpine

ARG ADMIN_USER
ARG ADMIN_PASSWORD
ARG ACF_KEY

COPY ./wp-content/themes/wp-theme /var/www/html/wp-content/themes/wp-theme
COPY ./wp-content/mu-plugins /var/www/html/wp-content/mu-plugins
COPY docker/custom.ini /usr/local/etc/php/conf.d/custom.ini

RUN chown -R www-data:www-data /var/www/html/wp-content/themes/wp-theme /var/www/html/wp-content/mu-plugins

EXPOSE 80

