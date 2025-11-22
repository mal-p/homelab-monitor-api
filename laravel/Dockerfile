FROM php:8.4-apache

#
# PHP
#
RUN apt-get update && apt-get install -y \
	libpq-dev \
	libcurl4-openssl-dev \
	libonig-dev \
	libxml2-dev \
	zlib1g-dev \
	libzip-dev \
	libicu-dev \
	unzip \
	&& docker-php-ext-install pgsql pdo pdo_pgsql zip

RUN docker-php-ext-configure intl \
    && docker-php-ext-install intl

# Install PHP composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Configure PHP
COPY php_api_config.ini /usr/local/etc/php/conf.d/php_api_config.ini

# Install Laravel
# Volume ""./laravel/src:/var/www/html" is defined in docker-compose
WORKDIR /var/www/html
RUN composer global require laravel/installer

# Add Composer to $PATH
RUN echo 'export PATH="$HOME/.composer/vendor/bin:$PATH"' >> ~/.bashrc && \
    export PATH="$HOME/.composer/vendor/bin:$PATH"

#
# Apache
#

# Setup Apache config
RUN a2enmod rewrite

# Set document root to match laravel project name
ENV APACHE_DOCUMENT_ROOT /var/www/html/home-monitor/public

# Run on non-privileged port
RUN sed -i     's/80/8080/g' /etc/apache2/ports.conf /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Configure MPM
COPY mpm_prefork.conf /etc/apache2/mods-available/mpm_prefork.conf

# Create a group and user
RUN addgroup --gid 1000 appgroup
RUN adduser --uid 1000 --gid 1000 appuser

USER appuser
