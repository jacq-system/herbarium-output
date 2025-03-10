FROM ghcr.io/jacq-system/symfony-base:main@sha256:02ad7a8a3bfcd731c4b9c2c04410e2d85d6c864ffbef7944e92bf675005263ac
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
