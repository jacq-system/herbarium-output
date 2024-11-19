FROM ghcr.io/krkabol/php-fpm-noroot-socket:main@sha256:61000c37d58d2162c4799d8fdc3f377ccc038a2aba0016680b026747801f791d
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

# devoted for Kubernetes, where the app has to be copied into final destination (/app) after the container starts
COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var

## use in case you want to run in docker on local machine
#COPY htdocs /var/www/html
