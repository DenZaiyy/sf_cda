FROM dunglas/frankenphp

ENV FRANKENPHP_CONFIG="worker ./public/index.php"
ENV APP_RUNTIME=Runtime\\FrankenPhpSymfony\\Runtime

RUN install-php-extensions \
  pdo_mysql \
  gd \
  intl \
  zip \
  opcache

COPY . /app/
