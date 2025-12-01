FROM ghcr.io/jacq-system/symfony-base:main@sha256:fb6ce31ad6ebeedcba419bf909945a70a33c8ef82884b758a3099e463216b76d
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
