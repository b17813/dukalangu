# Tumia image ya PHP yenye Apache
FROM php:8.2-apache

# Sakinisha extensions za MySQL ambazo PHP inahitaji
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Washa Apache mod_rewrite (muhimu kwa URL nzuri za ERP yako)
RUN a2enmod rewrite

# Nakili kodi zako zote kwenda kwenye server ya Apache
COPY . /var/www/html/

# Weka ruhusa (permissions) sahihi kwa folder la mradi
RUN chown -R www-data:www-data /var/www/html

# Fungua port 80 kwa ajili ya web traffic
EXPOSE 80

# Amuru Apache ianze kufanya kazi
CMD ["apache2-foreground"]
