FROM php:8.0-apache

# Install dependencies (if any)
# Enable mod_rewrite for .htaccess support
RUN a2enmod rewrite

# Copy local code to the container image
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Permissions
# Apache needs write access to data/ directory for cache generation
RUN mkdir -p data && chown -R www-data:www-data data && chmod -R 755 data

# Expose port 80
EXPOSE 80

# Configure Apache to allow .htaccess
RUN echo '<Directory /var/www/html/>' >> /etc/apache2/apache2.conf && \
    echo '    AllowOverride All' >> /etc/apache2/apache2.conf && \
    echo '</Directory>' >> /etc/apache2/apache2.conf
