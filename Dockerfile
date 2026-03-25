# Use the official PHP image with Apache
FROM php:8.2-apache

# Install PostgreSQL client libraries and the PDO PGSQL extension
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Enable Apache mod_rewrite (useful for clean API URLs)
RUN a2enmod rewrite

# Copy your application code to the container
# This includes api.php, .htaccess, etc.
COPY . /var/www/html/

# Set permissions so Apache can read your files
RUN chown -R www-data:www-data /var/www/html

# Expose port 80 for web traffic
EXPOSE 80

# Start Apache in the foreground
CMD ["apache2-foreground"]