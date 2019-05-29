FROM php:7.1-alpine3.9

WORKDIR /app

#install composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php composer-setup.php  --install-dir=/bin --filename=composer && \
    php -r "unlink('composer-setup.php');"

RUN apk add --no-cache --update nodejs-npm git openssh ca-certificates

RUN apk add --no-cache --update icu-dev && \
    docker-php-ext-install bcmath

RUN docker-php-ext-install intl

RUN apk add --no-cache --update openldap-dev && \
    docker-php-ext-install ldap


#clone the source
RUN git clone git://github.com/etraxis/etraxis.git
WORKDIR /app/etraxis
#COPY ./ ./

RUN docker-php-ext-install mysqli pdo pdo_mysql && \
    docker-php-ext-enable pdo_mysql

ENV APP_ENV=prod
ENV DATABASE_URL="mysql://root@127.0.0.1/etraxis?charset=utf8"

RUN composer install

# RUN ./bin/console --no-interaction doctrine:database:create
# RUN ./bin/console --no-interaction doctrine:schema:create
# RUN ./bin/console --no-interaction doctrine:fixtures:load -n


# RUN npm install && \
#     ./node_modules/gulp/bin/gulp.js && \
#     ./node_modules/webpack/bin/webpack.js

ENTRYPOINT [ "/app/etraxis/bin/console" ]
CMD ["server:run"]
