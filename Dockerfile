FROM ghcr.io/jacq-system/symfony-base:main@sha256:21f4b67a92a19b229ef69a76b00317e24f54c620a70e969858ac31579298adb6
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
