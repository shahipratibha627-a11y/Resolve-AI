FROM php:8.3-cli-alpine

WORKDIR /var/www

# Install system deps: SQLite dev libs + PostgreSQL client libs
RUN apk add --no-cache sqlite-dev libpq-dev \
    && docker-php-ext-install pdo pdo_sqlite pdo_pgsql

# Copy backend source files
COPY . .

# Ensure writable runtime directories exist
RUN mkdir -p instance public/uploads \
    && chmod -R 777 instance public/uploads

# Render injects $PORT at runtime (default 10000); expose as a hint
EXPOSE 10000

# Start PHP built-in server on 0.0.0.0 using Render $PORT (fallback 10000)
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-10000} -t public public/router.php"]
