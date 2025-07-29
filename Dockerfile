FROM dunglas/frankenphp

ENV APP_RUNTIME=Runtime\\FrankenPhpSymfony\\Runtime

RUN install-php-extensions \
  pdo_mysql \
  gd \
  intl \
  zip \
  opcache

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

WORKDIR /app
