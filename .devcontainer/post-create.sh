#!/usr/bin/env bash
#
# Provisions a ready-to-use Part-DB development environment inside the dev container.
# This runs once, when the container is created.
#
set -euo pipefail

echo "🔧 Installing required PHP extensions..."
# `install-php-extensions` usually ships with the Microsoft PHP dev container
# image, but it is not always on PATH (and `sudo` resets PATH). Resolve it and
# fall back to downloading the official helper if it is missing.
install_php_extensions_bin="$(command -v install-php-extensions || true)"
if [ -z "$install_php_extensions_bin" ] && [ -x /usr/local/bin/install-php-extensions ]; then
    install_php_extensions_bin="/usr/local/bin/install-php-extensions"
fi
if [ -z "$install_php_extensions_bin" ]; then
    echo "   install-php-extensions not found, downloading it..."
    sudo curl -sSLf \
        https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions \
        -o /usr/local/bin/install-php-extensions
    sudo chmod +x /usr/local/bin/install-php-extensions
    install_php_extensions_bin="/usr/local/bin/install-php-extensions"
fi

sudo PHP_INI_DIR="${PHP_INI_DIR:-/usr/local/etc/php}" "$install_php_extensions_bin" \
    gd \
    intl \
    zip \
    bcmath \
    gmp \
    pdo_mysql \
    pdo_pgsql \
    xsl \
    opcache

echo "🧠 Raising the PHP CLI memory limit for provisioning..."
# Symfony's post-install auto-scripts (e.g. cache:clear) run `php` with the
# default 128M memory_limit, which is not enough for Part-DB and causes an OOM
# fatal error during `composer install`. Lift the limit container-wide.
echo 'memory_limit = -1' \
    | sudo tee "${PHP_INI_DIR:-/usr/local/etc/php}/conf.d/zz-devcontainer-memory.ini" >/dev/null

echo "🎼 Installing the Symfony CLI (provides 'symfony serve')..."
if ! command -v symfony >/dev/null 2>&1; then
    curl -sS https://get.symfony.com/cli/installer | bash
    sudo mv "$HOME/.symfony5/bin/symfony" /usr/local/bin/symfony
fi

echo "📦 Installing Composer dependencies..."
composer install --no-interaction --prefer-dist

echo "🧶 Installing Yarn (classic) and JavaScript dependencies..."
# The project uses a Yarn v1 lockfile, so install the classic Yarn client.
if ! command -v yarn >/dev/null 2>&1; then
    sudo npm install -g yarn
fi
yarn install --network-timeout 600000

echo "🏗️  Building frontend assets..."
yarn build

echo "🗄️  Setting up the development database (SQLite)..."
# Start from a clean database so re-running provisioning (or recovering from a
# previously interrupted run) cannot leave stale rows that collide with the
# fixtures' explicit IDs (e.g. "UNIQUE constraint failed: currencies.id").
# Note: on SQLite, `--if-exists` (drop) and both forms of `create` trigger
# operations the platform doesn't support (listDatabases/getCreateDatabaseSQL).
# The drop with `--force` works and gives us a clean slate; the SQLite database
# file is then (re)created automatically when migrations first connect, so we
# tolerate a failing `create` (it still creates the DB on MySQL/PostgreSQL).
php bin/console doctrine:database:drop --force --env dev || true
php bin/console doctrine:database:create --if-not-exists --env dev || true
COMPOSER_MEMORY_LIMIT=-1 php bin/console doctrine:migrations:migrate -n --env dev
# Load demo data so there is a usable dataset and login to start with.
php bin/console partdb:fixtures:load -n --env dev

echo "🔥 Warming up the development cache..."
COMPOSER_MEMORY_LIMIT=-1 php -d memory_limit=1G bin/console cache:warmup --env dev -n

cat <<'EOF'

✅ Part-DB development environment is ready!

Start the dev server with:
    symfony serve            # https://localhost:8000
  or
    php -S 0.0.0.0:8000 -t public

Useful commands:
    yarn watch               # rebuild assets on change
    php bin/phpunit          # run the test suite (run `make test-setup` first)
    composer phpstan         # static analysis

EOF
