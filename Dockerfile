

FROM php:8.2-cli

WORKDIR /app

# تثبيت الإضافات الضرورية لـ PHP (بما فيها pdo_mysql و pdo_sqlite)
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    libsqlite3-dev \
    libpng-dev \
    libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

# 2. تثبيت Composer
RUN curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer

# 3. نسخ ملفات المشروع
COPY . .

# 4. تثبيت مكتبات Laravel
RUN composer install --no-dev --optimize-autoloader

# --- الخطوات المهمة لحل المشكلة ---
# إنشاء ملف قاعدة البيانات وضمان صلاحيات الكتابة للمجلدات
RUN mkdir -p /app/database && \
    touch /app/database/database.sqlite && \
    chmod -R 775 /app/storage /app/bootstrap/cache /app/database && \
    chown -R www-data:www-data /app
# ---------------------------------

EXPOSE 10000

# تشغيل الـ Migrations ثم تشغيل السيرفر
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=10000
