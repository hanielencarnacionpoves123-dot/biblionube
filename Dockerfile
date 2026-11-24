# Imagen oficial de PHP 8.3 con Apache
FROM php:8.3-apache

# Extensión para conectarse a MySQL/MariaDB y módulo rewrite de Apache
RUN docker-php-ext-install pdo_mysql && a2enmod rewrite

# Configuración de Apache para el sitio
COPY docker/biblionube.conf /etc/apache2/conf-enabled/biblionube.conf

# Certificado de Amazon RDS para conectar cifrado
ADD https://truststore.pki.rds.amazonaws.com/global/global-bundle.pem /etc/ssl/certs/rds-global-bundle.pem
RUN chmod 644 /etc/ssl/certs/rds-global-bundle.pem

# Código de la aplicación
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
