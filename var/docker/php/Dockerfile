################################################################################
# Base image
################################################################################

FROM php:8.0-fpm

################################################################################
# Build instructions
################################################################################

# Install packages
RUN apt-get update && apt-get install -y \
    libmcrypt-dev \
    libicu-dev \
    libxslt-dev \
    libzmq3-dev \
    libevent-dev \
    libgmp-dev \
    libzip-dev \
    procps \
    git

RUN docker-php-source extract \
  && git clone -n https://github.com/zeromq/php-zmq /usr/src/php/ext/zmq \
  && cd /usr/src/php/ext/zmq && git checkout 43464c42a6a47efdf8b7cab03c62f1622fb5d3c6 \
  && docker-php-ext-install zmq \
  && docker-php-source delete

RUN docker-php-ext-install -j$(nproc) \
    intl \
    xsl \
    sockets \
    bcmath \
    pcntl \
    opcache \
    gmp

RUN pecl install ev-1.1.1 xdebug-3.0.2 mcrypt-1.0.4 grpc-1.35.0 protobuf-3.15.0RC1 ds-1.3.0 \
  && docker-php-ext-enable ev mcrypt grpc protobuf ds