FROM php:8.3-cli-alpine

WORKDIR /var/www

# Install SQLite and PostgreSQL PDO extensions
RUN apk add --no-cache sqlite-dev libpq-dev \
    && docker-php-ext-install pdo pdo_sqlite pdo_pgsql

# Copy backend files
COPY . .

# Ensure instance and uploads directories exist with write permissions
RUN mkdir -p instance public/uploads \
    && chmod -R 777 instance public/uploads

# Expose default port (Railway automatically injects and routes via $PORT)
EXPOSE 5000

# Start PHP built-in web server with dynamic $PORT support (defaults to 5000)
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-5000} -t public public/router.php"]
