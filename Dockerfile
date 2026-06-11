FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
      git \
      unzip \
      zip \
      libicu-dev \
      libonig-dev \
      libzip-dev \
      libpng-dev \
      libjpeg62-turbo-dev \
      libfreetype6-dev \
      libxml2-dev \
      libpq-dev \
      default-mysql-client \
      && docker-php-ext-configure gd \
          --with-freetype=/usr/include/ \
          --with-jpeg=/usr/include/ \
      && docker-php-ext-install -j$(nproc) \
          gd \
          pdo \
          pdo_mysql \
          intl \
          zip \
          opcache

RUN a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

COPY apache-symfony.conf /etc/apache2/sites-available/000-default.conf
RUn cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Do not show PHP version in the response headers for security reasons
RUN sed -i 's/^expose_php\s*=.*/expose_php = Off/' /usr/local/etc/php/php.ini

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

RUN apt-get update && apt-get install -y \
    unzip \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY ~/php.ini /usr/local/etc/php/

WORKDIR /var/www/html

RUN chown -R www-data:www-data /var/www/html/var
RUN usermod -u 1000 www-data
USER www-data

EXPOSE 80
EXPOSE 9003
