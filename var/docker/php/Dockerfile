################################################################################
# Base image
################################################################################

FROM php:7.4-fpm

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
  && cd /usr/src/php/ext/zmq && git checkout e0db82c3286da81fa8945894dd10125a528299e4 \
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

RUN pecl install ev xdebug mcrypt grpc protobuf ds \
  && docker-php-ext-enable ev mcrypt grpc protobuf ds