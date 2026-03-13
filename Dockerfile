# Tumia PHP 8.2 na Apache
FROM php:8.2-apache

# 1. Sakinisha extensions muhimu za MySQL
RUN apt-get update && apt-get install -y \
    libpng-dev \
    zip \
    unzip \
    && docker-php-ext-install mysqli pdo pdo_mysql

# 2. Washa mod_rewrite (Muhimu kwa ajili ya kusoma URL vizuri)
RUN a2enmod rewrite

# 3. Rekebisha Apache kuelekeza folder sahihi (Fixes Not Found)
# Hii inahakikisha hata ukiwa na folder ndogo, Apache inaziona
ENV APACHE_DOCUMENT_ROOT /var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 4. Nakili kodi zako zote (Kila kitu kilichopo kwenye folder lako)
WORKDIR /var/www/html
COPY . /var/www/html/

# 5. Weka ruhusa kamili (Permissions)
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# 6. Hakikisha Apache inasikiliza Port 80
EXPOSE 80

CMD ["apache2-foreground"]