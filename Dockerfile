FROM php:8.4-cli

RUN apt-get update && apt-get install -y git unzip zip && rm -rf /var/lib/apt/lists/*

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions intl mbstring zip pdo pdo_mysql pdo_sqlite opcache gd pcov

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

CMD ["php", "-v"]
