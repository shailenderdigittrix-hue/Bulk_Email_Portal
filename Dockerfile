FROM php:8.2-apache
RUN a2dismod mpm_worker mpm_event && a2enmod mpm_prefork
RUN docker-php-ext-install mysqli pdo pdo_mysql
COPY . /var/www/html/
WORKDIR /var/www/html
EXPOSE 80