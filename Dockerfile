FROM php:7.3-cli

ENV APACHE_DOCUMENT_ROOT=/var/www/html/

# correct timezone
RUN apt-get -qq install -y tzdata

ENV TZ=Europe/Prague
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Install dependencies
RUN apt-get -qq update && apt-get -qq install -y \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libmcrypt-dev \
    libpng-dev \
    libcurl4-nss-dev \
    libc-client-dev \
    libkrb5-dev \
    firebird-dev \
    libicu-dev \
    libxml2-dev \
    libxslt1-dev \
    autoconf \
    zip \
    cron \
    git \
    libssh2-1-dev

RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install imap \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && docker-php-ext-install zip

# Set the timezone
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# xdebug
#RUN pecl install xdebug
#RUN docker-php-ext-enable xdebug

# Cleanup all downloaded packages
RUN apt-get -y autoclean && apt-get -y autoremove && apt-get -y clean && rm -rf /var/lib/apt/lists/* && apt-get update

RUN touch /var/log/cron.log
RUN chmod 0777 /var/log/cron.log

ADD redmine-cron /etc/cron.d/redmine-cron
RUN chmod 0644 /etc/cron.d/redmine-cron

# env
COPY .env /root/raw.env
RUN cat /root/raw.env | sed 's/^\(.*\)\=\(.*\)$/export \1\="\2"/g' > /root/project_env.env

# RUN printenv | sed 's/^\(.*\)\=\(.*\)$/export \1\="\2"/g' > /root/project_env.env

RUN /usr/bin/crontab /etc/cron.d/redmine-cron
#CMD cron

#ADD start.sh /usr/local/bin/start.sh
#RUN chmod 777 /usr/local/bin/start.sh
#RUN cd /usr/local/bin/ && ./start.sh &
#ADD . /var/www/html

#RUN mkdir -p /var/www/html/log

# for robotloader
#RUN mkdir -p /var/www/html/temp

#RUN cd /var/www/html/ && composer install
#RUN tail -f /var/log/cron.log

ENTRYPOINT ["cron"]
CMD ["tail", "-f", "/var/log/cron.log"]

EXPOSE 3306