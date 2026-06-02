FROM ghcr.io/jacq-system/symfony-base:main@sha256:36d1987809ae6496b8ce461891bbd23bd192f3e121f644232d7df9a07eda2ab4
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
