FROM ghcr.io/jacq-system/symfony-base:main@sha256:0cdee1af6f3fa888a32d5b8ef77aa5cfc7d6e508d9a6d72bb2c6f48fcf3e73de
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
