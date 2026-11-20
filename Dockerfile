FROM ghcr.io/jacq-system/symfony-base:main@sha256:f68fba030ca129ec1900854c60d9a213acbd7a85e4efa476cd155f76ace610a7
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
