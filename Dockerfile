FROM php:8.2-apache

# Install mysqli extension for MySQL
RUN docker-php-ext-install mysqli

# Copy the application files into the Apache server's root
COPY . /var/www/html

# Expose port 80 for HTTP
EXPOSE 80
