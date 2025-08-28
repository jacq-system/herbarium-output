FROM ghcr.io/jacq-system/symfony-base:main@sha256:a9613c7ca8b0243f01cf004c370d980e4120d233208ab8ff42cb34c93b2965b5
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
