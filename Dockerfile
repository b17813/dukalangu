FROM php:8.2-apache

# Sakinisha extensions za MySQL
RUN apt-get update && apt-get install -y libpng-dev zip unzip \
    && docker-php-ext-install mysqli pdo pdo_mysql

# Washa mod_rewrite
RUN a2enmod rewrite

# Nakili kodi zako (pamoja na index.php yako ya zamani)
WORKDIR /var/www/html
COPY . .

# Weka ruhusa
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

EXPOSE 80