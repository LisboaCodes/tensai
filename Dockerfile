FROM php:8.2-apache

# Instala extensões PHP necessárias (mysqli para MySQL)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Habilita mod_rewrite do Apache
RUN a2enmod rewrite

# Configuração do Apache para permitir .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Copia o código da aplicação
COPY . /var/www/html/

# Ajusta permissões
RUN chown -R www-data:www-data /var/www/html/ \
    && chmod -R 755 /var/www/html/

# Copia o composer vendor da raiz se existir
COPY vendor/ /var/www/html/vendor/

EXPOSE 80
