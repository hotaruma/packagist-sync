FROM php:8.2-cli-alpine

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    mkdir /action

COPY . /action/

RUN cd /action && composer install && \
    chmod +x /action/entrypoint.sh && \
    chmod +x /action/packagist-sync

ENTRYPOINT ["/action/entrypoint.sh"]
