FROM php:8.3-cli-alpine

WORKDIR /var/www

# Install SQLite and PDO extensions
RUN apk add --no-cache sqlite-dev \
    && docker-php-ext-install pdo pdo_sqlite

# Copy backend files
COPY . .

# Ensure instance and uploads directories exist
RUN mkdir -p instance public/uploads \
    && chmod -R 777 instance public/uploads

# Run seed script to pre-populate demo accounts and data
RUN php database/seed.php

# Expose default port
EXPOSE 5000

# Start PHP built-in web server
CMD ["php", "-S", "0.0.0.0:5000", "-t", "public", "public/router.php"]
