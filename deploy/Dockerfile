# OpenKIS web image: Ubuntu 24.04 + Apache 2.4 + PHP 8.3 (mod_php) with the
# extensions the code uses (mysqli, gd, exif, curl, mbstring, xml, zip).
#
# The OpenKIS source (with the finis submodule checked out) is taken from the
# "openkis" build context; deploy/docker-compose.yml points it at the
# repository root (..).
ARG BASE_IMAGE=ubuntu:24.04
FROM ${BASE_IMAGE}

ENV DEBIAN_FRONTEND=noninteractive \
    APACHE_RUN_USER=www-data \
    APACHE_RUN_GROUP=www-data \
    APACHE_LOG_DIR=/var/log/apache2 \
    APACHE_PID_FILE=/var/run/apache2/apache2.pid \
    APACHE_RUN_DIR=/var/run/apache2 \
    APACHE_LOCK_DIR=/var/lock/apache2 \
    TZ=UTC

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        apache2 libapache2-mod-php8.3 \
        php8.3-cli php8.3-mysql php8.3-gd php8.3-mbstring php8.3-xml \
        php8.3-curl php8.3-zip \
        mariadb-client rsync patch ca-certificates tzdata curl; \
    rm -rf /var/lib/apt/lists/*; \
    a2enmod rewrite headers expires php8.3; \
    a2dissite 000-default; \
    ln -sf /dev/stdout /var/log/apache2/access.log; \
    ln -sf /dev/stderr /var/log/apache2/error.log; \
    mkdir -p /var/run/apache2 /var/lock/apache2; \
    rm -f /var/www/html/index.html

COPY docker/php.ini /etc/php/8.3/apache2/conf.d/99-openkis.ini
COPY docker/php.ini /etc/php/8.3/cli/conf.d/99-openkis.ini
COPY docker/apache-openkis.conf /etc/apache2/sites-enabled/openkis.conf
COPY docker/entrypoint.sh /usr/local/bin/openkis-entrypoint
COPY patches /usr/src/openkis-patches
RUN chmod +x /usr/local/bin/openkis-entrypoint

# Application code. It is kept in /usr/src/openkis and copied into the
# /var/www/html volume by the entrypoint (first start and image upgrades),
# because the framework writes into the web root at run time (.htaccess,
# misc/, sections/, include/, images/, languages/, modules/, themes/default/).
COPY --from=openkis . /usr/src/openkis/
RUN set -eux; \
    cd /usr/src/openkis; \
    test -f finis/src/include/finis.php \
      || { echo "finis submodule missing: run git submodule update --init --recursive" >&2; exit 1; }; \
    rm -rf .git finis/.git misc deploy; \
    # optional local patches: deploy/patches/<subdir>/*.patch apply to that
    # subdir (_root = the OpenKIS root, finis = the submodule)
    for d in /usr/src/openkis-patches/*/; do \
        sub=$(basename "$d"); [ "$sub" = _root ] && sub=.; \
        for p in "$d"*.patch; do \
            [ -f "$p" ] || continue; \
            if patch -d "$sub" -p1 -R --dry-run --force --silent < "$p" >/dev/null 2>&1; then \
                echo "already applied, skipping $p"; continue; \
            fi; \
            echo "applying $p to $sub/"; \
            patch -d "$sub" -p1 --forward --batch < "$p"; \
        done; \
    done; \
    test -s .openkis-image-version || date -u +build-%Y%m%dT%H%M%SZ > .openkis-image-version; \
    echo "+patches-$(cat /usr/src/openkis-patches/*/*.patch 2>/dev/null | md5sum | cut -c1-8)" >> .openkis-image-version; \
    tr -d '\n' < .openkis-image-version > .v && mv .v .openkis-image-version; \
    cat .openkis-image-version; echo; \
    find . -name '*.php' -not -path './finis/tests/*' -print0 | xargs -0 -n1 -P4 php -l > /dev/null

EXPOSE 80
VOLUME ["/var/www/html"]
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s \
    CMD curl -s -o /dev/null -w '%{http_code}' http://localhost/index.php | grep -qE '^(200|30[1237])$'
ENTRYPOINT ["openkis-entrypoint"]
CMD ["apache2ctl", "-D", "FOREGROUND"]
