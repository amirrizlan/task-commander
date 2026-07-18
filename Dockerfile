FROM php:8.2-apache

# 1. Pasang alat bantuan sistem dan library yang diperlukan untuk php extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl

# 2. Pasang extension PHP yang WAJIB untuk Laravel & PostgreSQL (Neon)
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd xml

# 3. Aktifkan mod rewrite Apache untuk haluan URL Laravel
RUN a2enmod rewrite

# 4. Tukar Document Root Apache ke folder public Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# 5. Salin kod projek ke dalam container
COPY . /var/www/html

# 6. Pasang Composer versi terkini
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 7. Jalankan Composer install dengan selamat
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 8. Set kebenaran (permission) folder storage & cache Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80