FROM ghcr.io/jacq-system/symfony-base:main@sha256:801064f8c0e5a6e08f0cd3cbfa19442c9a61cad0e668461b8807447705c006fc
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
