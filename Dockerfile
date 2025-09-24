FROM dunglas/frankenphp

ENV APP_RUNTIME=Runtime\\FrankenPhpSymfony\\Runtime
ENV SERVER_NAME=denz.ovh
ENV APP_ENV=dev
ENV APP_DEBUG=1

RUN install-php-extensions \
  pdo_mysql \
  gd \
  intl \
  zip \
  opcache

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

WORKDIR /app
