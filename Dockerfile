FROM ghcr.io/jacq-system/symfony-base:main@sha256:e232b91153e0f72cc1748d32ef0ade7ed5a2be54189b703fa1a861a3207ee6d0
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
