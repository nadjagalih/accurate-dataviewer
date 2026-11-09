#!/bin/bash

set -e

echo "======================================"
echo "Laravel + Accurate API Setup"
echo "======================================"

echo ""
echo "Installing PHP extensions..."

sudo docker-php-ext-install pdo_mysql

echo ""
echo "Installing Composer dependencies..."

if [ -f composer.json ]; then
    composer install
fi

echo ""
echo "Installing Node dependencies..."

if [ -f package.json ]; then
    npm install
    npm run build
fi

echo ""
echo "Preparing Laravel environment..."

if [ ! -f .env ]; then
    cp .env.example .env
fi

if grep -q '^CODESPACES=' .env; then
    sed -i 's/^CODESPACES=.*/CODESPACES=true/' .env
else
    echo 'CODESPACES=true' >> .env
fi

echo ""
echo "Generating application key..."

php artisan key:generate

echo ""
echo "Waiting for MySQL..."

until php -r "
try {
    \$pdo = new PDO(
        'mysql:host=mysql;dbname=accurate_integration',
        'laravel',
        'secret'
    );
    echo 'MySQL is ready';
} catch (Exception \$e) {
    exit(1);
}
"; do

    echo "Waiting for MySQL..."
    sleep 2

done

echo ""
echo "======================================"
echo "Setup completed!"
echo "======================================"

echo ""
echo "Laravel:"
echo "php artisan serve --host=0.0.0.0"

echo ""
echo "Vite:"
echo "npm run dev -- --host 0.0.0.0"