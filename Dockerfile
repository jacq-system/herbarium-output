FROM ghcr.io/jacq-system/symfony-base:main@sha256:b098bdabf0a33741b553fe6fd092c15dcac32b4d7e03d19b88919713fc546570
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
