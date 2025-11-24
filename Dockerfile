FROM php:8.2-apache

# 1. Install system dependencies (opzionale, ma utile per estensioni future)
# RUN apt-get update && apt-get install -y ...

# 2. Install extensions PHP (opzionale, es. per gd, pdo_mysql, etc.)
# RUN docker-php-ext-install gd

# 3. Enable Apache mod_rewrite per URL puliti
RUN a2enmod rewrite

# 3a. Copy custom Apache config
COPY .docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# 4. Copy application source
COPY . /var/www/html/

# 5. Set working directory
WORKDIR /var/www/html

# Le porte sono già esposte dall'immagine base (80)
