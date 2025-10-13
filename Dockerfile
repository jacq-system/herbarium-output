FROM ghcr.io/jacq-system/symfony-base:main@sha256:8a4a159587f3c531c31557d485a544daad7c9f2a38741dc8cb0b1301c3f46d58
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
