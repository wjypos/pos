FROM dunglas/frankenphp AS base

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev mariadb-client curl supervisor \
    && docker-php-ext-install pdo pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js + npm (LTS)
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm

WORKDIR /app

# Copy composer files first (cache layer)
COPY composer.json composer.lock ./

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install PHP dependencies (no dev by default)
RUN composer install --no-dev --no-scripts --no-progress --no-interaction

# Copy app source
COPY . .

# Install JS dependencies & build assets
RUN npm install && npm run build

# Fix permissions
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

# Supervisord config
COPY ./supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Expose Octane internal port
EXPOSE 8080

# Entrypoint: setup APP_KEY if needed + run supervisord
CMD ["/bin/sh", "-c", "\
    if [ ! -f .env ]; then cp .env.example .env; fi && \
    if ! grep -q 'APP_KEY=' .env || [ -z \"$(grep 'APP_KEY=' .env | cut -d '=' -f2)\" ]; then \
        php artisan key:generate; \
    fi && \
    supervisord -c /etc/supervisor/conf.d/supervisord.conf \
"]
