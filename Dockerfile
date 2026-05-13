FROM php:8.2-apache

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache rewrite
RUN a2enmod rewrite

# Copy project files
COPY . /var/www/html/

# Create uploads directory if missing
RUN mkdir -p /var/www/html/uploads

# Set permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html
RUN chmod -R 777 /var/www/html/uploads

# Working directory
WORKDIR /var/www/html

# Expose port
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]