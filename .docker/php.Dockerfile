FROM php:8.0-apache
RUN apt-get update && apt-get install -y libpq-dev libsodium-dev libmemcached-dev zlib1g-dev libzip-dev imagemagick libmagickwand-dev \
	&& docker-php-ext-install pdo pdo_pgsql bcmath sodium zip sockets \
	&& pecl install memcached \
	&& docker-php-ext-enable memcached \
	&& pecl install imagick \
	&& docker-php-ext-enable imagick \
	&& printf '[PHP]\nupload_max_filesize = 5M\npost_max_size = 8M\n' > /usr/local/etc/php/conf.d/core.ini

ENV COMPOSER_ALLOW_SUPERUSER=1
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY composer.json composer.lock ./
RUN composer install

RUN mkdir -p /var/www/html/web/assets
COPY app app
COPY yii ./
COPY web/index.php web/.htaccess ./web/
RUN rm -rf /var/www/html/app/runtime/cache /var/www/html/app/runtime/debug /var/www/html/app/runtime/gii* /var/www/html/app/runtime/logs \
    && mkdir -p /var/www/html/app/runtime/logs /var/www/html/app/runtime/cache /var/www/html/app/runtime/debug \
    && chmod -R 0777 /var/www/html/app/runtime/logs /var/www/html/app/runtime/cache /var/www/html/app/runtime/debug
RUN chown -R www-data:www-data /var/www/html

# Prepare Apache
RUN a2enmod rewrite headers
ENV APACHE_DOCUMENT_ROOT /var/www/html/web
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

