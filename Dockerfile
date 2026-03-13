# Tumia image ya PHP yenye Apache (nyepesi na imara)
FROM php:8.2-apache

# 1. Sakinisha system dependencies na PHP extensions muhimu
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd mysqli pdo pdo_mysql

# 2. Washa Apache mod_rewrite kwa ajili ya SEO-friendly URLs
RUN a2enmod rewrite

# 3. Weka Working Directory
WORKDIR /var/www/html

# 4. Nakili kodi zako zote kwenda kwenye container
COPY . .

# 5. Weka ruhusa (permissions) sahihi kwa files
# Hii inazuia matatizo ya "Permission Denied" kule Render
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# 6. Sanidi PHP iwe na production settings (Opcache kwa speed)
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# 7. Fungua Port 80
EXPOSE 80

# 8. Amuru Apache ianze
CMD ["apache2-foreground"]