FROM ghcr.io/jacq-system/symfony-base:main@sha256:6f74a65ba15582e4874ad5872be846077a6d016c9c7c4a241c5dc6e8fcb47106
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
