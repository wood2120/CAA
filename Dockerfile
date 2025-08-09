# Imagen base con PHP y Apache
FROM php:8.2-apache

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install mysqli pdo pdo_mysql zip intl \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Configurar PHP
RUN echo "date.timezone = America/Costa_Rica" >> /usr/local/etc/php/conf.d/timezone.ini \
    && echo "upload_max_filesize = 10M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 10M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/limits.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/limits.ini

# Configurar Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Crear directorios necesarios
RUN mkdir -p /var/www/html/reports \
    && mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html

# Copiar el código al contenedor
COPY . /var/www/html/

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/reports \
    && chmod -R 777 /var/www/html/uploads

# Copiar archivo de configuración de Apache personalizado
COPY docker/apache-config.conf /etc/apache2/sites-available/000-default.conf

# Exponer el puerto 80
EXPOSE 80

# Comando de inicio
CMD ["apache2-foreground"]
