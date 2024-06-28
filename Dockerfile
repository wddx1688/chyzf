FROM php:7.4-fpm

# Copy composer.lock and composer.json into the working directory
# COPY app/composer.lock app/composer.json /var/www/html/

# Set working directory
WORKDIR /var/www/html/

# Install dependencies for the operating system software
RUN apt-get update 

RUN apt-get install -y build-essential
RUN apt-get install -y libpng-dev
RUN apt-get install -y libjpeg62-turbo-dev
RUN apt-get install -y libfreetype6-dev
RUN apt-get install -y locales
RUN apt-get install -y zip
RUN apt-get install -y jpegoptim
RUN apt-get install -y optipng
RUN apt-get install -y pngquant
RUN apt-get install -y gifsicle
RUN apt-get install -y vim
RUN apt-get install -y libzip-dev
RUN apt-get install -y unzip
RUN apt-get install -y git
RUN apt-get install -y libonig-dev
RUN apt-get install -y curl

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install extensions for php
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd

# Install mysqli and enable extension
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Install composer (php package manager)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy existing application directory contents to the working directory
COPY ./app/ /var/www/html

# Assign permissions of the working directory to the www-data user
# Activate for Laravel
# RUN chown -R www-data:www-data /var/www/html/storage
# RUN chown -R www-data:www-data /var/www/html/bootstrap/cache

# Expose port 9000 and start php-fpm server (for FastCGI Process Manager)
EXPOSE 9000
CMD ["php-fpm"]