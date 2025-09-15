FROM ghcr.io/jacq-system/symfony-base:main@sha256:5fa16d692531fd43d16ae0bfe2c25663fd85779316f74f1b549d529442782e7b
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
