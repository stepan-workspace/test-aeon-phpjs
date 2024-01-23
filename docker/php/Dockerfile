FROM php:apache

RUN apt-get update \
    && docker-php-ext-install pdo pdo_mysql

RUN a2enmod rewrite