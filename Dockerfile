FROM ghcr.io/jacq-system/symfony-base:main@sha256:ba5c59da46ba8575e8bcdae61929dd0cbbf7700ed7bf9a4e948e5fc163b3e356
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
