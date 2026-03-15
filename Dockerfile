FROM php:8.2-cli

WORKDIR /app

COPY . .

RUN apt-get update && apt-get install -y unzip git
RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer

RUN composer install

EXPOSE 10000

CMD php artisan serve --host=0.0.0.0 --port=10000
