FROM ghcr.io/jacq-system/symfony-base:main@sha256:8df3f110ac10f74a8f728c679dbb6f648398a1c7c3b1c5515e4e92fcfcc6c68a
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
