# ვიყენებთ PHP 8.2-ს, რომელიც დაფუძნებულია Debian 11 "Bullseye"-ზე
FROM php:8.2-fpm-bullseye

# განვაახლოთ პაკეტები და დავაყენოთ საჭირო ხელსაწყოები
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    wget \
    # === wkhtmltopdf-ის დამოკიდებულებები ===
    libxrender1 \
    fontconfig \
    libxext6 \
    libssl1.1 \
    xfonts-75dpi \
    xfonts-base \
    && rm -rf /var/lib/apt/lists/*

# დავაყენოთ PHP გაფართოებები
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install zip \
    && docker-php-ext-install intl

#
# === ვიწერთ BUSTER-ის ვერსიას ===
#
RUN wget https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6-1/wkhtmltox_0.12.6-1.buster_amd64.deb

#
# === ვაყენებთ BUSTER-ის ვერსიას ===
#
RUN apt-get install -y ./wkhtmltox_0.12.6-1.buster_amd64.deb

#
# === ვშლით BUSTER-ის ვერსიას ===
#
RUN rm ./wkhtmltox_0.12.6-1.buster_amd64.deb

# დავაყენოთ Composer
COPY --from=composer/composer:latest-bin /composer /usr/bin/composer

# სამუშაო დირექტორია
WORKDIR /var/www/html
