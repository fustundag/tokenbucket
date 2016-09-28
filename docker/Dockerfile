# GENERAL fustundag/centos-php7 image docker file.
FROM centos:latest
MAINTAINER Fatih Ustundag <fatih.ustundag@gmail.com>

RUN yum install -y --nogpgcheck epel-release && \
    rpm -qa | grep -q remi-release || rpm -Uvh http://rpms.famillecollet.com/enterprise/remi-release-7.rpm && \
    sed -i "s|enabled=1|enabled=0|" /etc/yum/pluginconf.d/fastestmirror.conf && \
    yum --enablerepo=remi-php70,remi install -y --nogpgcheck \
    gcc \
    git-core \
    make \
    nginx \
    nc \
    unzip \
    mysql \
    netstat \
    openssh \
    openssh-clients \
    openssh-server \
    php \
    php-opcache \
    php-apc \
    php-devel \
    pcre-devel \
    php-pear \
    php-pecl-xdebug \
    php-mysqlnd \
    php-pecl-memcached \
    php-xml \
    php-gd \
    php-mbstring \
    php-mcrypt \
    php-fpm \
    php-soap \
    php-json \
    php-intl \
    php-bcmath \
    php-pecl-redis \
    php-pgsql \
    npm \
    wget \
    telnet \
    vim && \
    rm -rf /etc/nginx/conf.d/default.conf && \
    rm -rf /etc/nginx/conf.d/ssl.conf && \
    rm -rf /etc/nginx/conf.d/virtual.conf && \
    rm -rf /etc/nginx/nginx.conf && \
    git clone --depth=1 git://github.com/phalcon/cphalcon.git && \
    cd /cphalcon/build && ./install && \
    echo "extension=phalcon.so" > /etc/php.d/phalcon.ini && \
    rm -rf /cphalcon && \
    curl -sS https://getcomposer.org/installer | php && \
    mv /composer.phar /usr/local/bin/composer && \
    TMPDIR=/tmp yum clean metadata && \
    TMPDIR=/tmp yum clean all