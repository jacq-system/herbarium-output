FROM ghcr.io/jacq-system/symfony-base:main@sha256:3b106e52f264c13169a1f712341c5a0beaef05237914fd6b6f1c922dffa04e9e
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
