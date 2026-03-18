FROM php:8.2-cli

WORKDIR /app

# تثبيت المكتبات المطلوبة
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    libpng-dev \
    libxml2-dev \
    libpq-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# تثبيت Composer
RUN curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer

# نسخ المشروع
COPY . .

# تثبيت مكتبات Laravel
RUN composer install --no-dev --optimize-autoloader

# صلاحيات المجلدات
RUN chmod -R 775 /app/storage /app/bootstrap/cache

EXPOSE 10000

# تشغيل التطبيق
CMD php artisan optimize:clear && php artisan migrate --force --seed && php artisan serve --host=0.0.0.0 --port=10000
