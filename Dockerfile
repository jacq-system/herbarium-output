FROM ghcr.io/jacq-system/symfony-base:main@sha256:2aea971fd5f285af7a3ed7a047fc354843ee45318b0488a2827a63e8c7ba0683
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
