FROM php:8.2-apache

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable rewrite module
RUN a2enmod rewrite

# Remove conflicting MPM modules
RUN a2dismod mpm_event
RUN a2enmod mpm_prefork

# Copy files
COPY . /var/www/html/

# Create uploads folder
RUN mkdir -p /var/www/html/uploads

# Permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Working directory
WORKDIR /var/www/html

# Apache port
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]