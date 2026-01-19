FROM php:8.2-apache

# Enable Apache rewrite
RUN a2enmod rewrite

# Install required PHP extensions
RUN apt-get update -y && apt-get install -y \
    libzip-dev \
    unzip \
    && docker-php-ext-install pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# ✅ PHP upload limits (THIS FIXES THE ERROR)
RUN { \
  echo "file_uploads=On"; \
  echo "memory_limit=256M"; \
  echo "upload_max_filesize=25M"; \
  echo "post_max_size=30M"; \
  echo "max_execution_time=300"; \
  echo "max_input_time=300"; \
} > /usr/local/etc/php/conf.d/uploads.ini

# Allow .htaccess overrides
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

WORKDIR /var/www/html
COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html
