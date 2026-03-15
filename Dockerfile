FROM php:8.2-cli

WORKDIR /app

# تثبيت الإضافات الضرورية لـ PHP
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    libpng-dev \
    libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# تثبيت Composer
RUN curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer

# نسخ ملفات المشروع
COPY . .

# تثبيت مكتبات Laravel
RUN composer install --no-dev --optimize-autoloader

# إعطاء صلاحيات للمجلدات المهمة
RUN chmod -R 775 /app/storage /app/bootstrap/cache

EXPOSE 10000

# تشغيل Laravel
CMD php artisan optimize:clear && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=10000
