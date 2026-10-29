# ---------- Stage 1: build the Vite/Tailwind assets ----------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm ci --ignore-scripts
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

# ---------- Stage 2: PHP runtime ----------
FROM php:8.3-cli-alpine
WORKDIR /app

RUN apk add --no-cache git unzip \
    && docker-php-ext-install pdo_mysql bcmath

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev --no-scripts \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/public bootstrap/cache

RUN chmod +x docker/start.sh
EXPOSE 8080
CMD ["sh", "docker/start.sh"]
