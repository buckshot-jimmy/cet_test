FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    git unzip zip \
    libicu-dev libzip-dev libonig-dev \
    libpng-dev libxml2-dev libpq-dev \
    default-mysql-client \
    && docker-php-ext-install \
    pdo pdo_mysql intl zip opcache

RUN a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install dependencies first
COPY composer.json composer.lock ./

RUN composer install --no-dev --optimize-autoloader

# Copy project files
COPY . .

RUN chown -R www-data:www-data var

EXPOSE 80

CMD ["apache2-foreground"]
