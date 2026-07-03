FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    nodejs \
    npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql gd zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Salin seluruh project terlebih dahulu agar artisan tersedia
COPY . .

# Install PHP dependency setelah artisan sudah ada
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Build asset Inertia Vue
RUN npm install && npm run build

# Bersihkan cache Laravel
RUN php artisan optimize:clear

EXPOSE 8080

CMD php artisan serve --host=0.0.0.0 --port=${PORT:-8080}