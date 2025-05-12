FROM ghcr.io/jacq-system/symfony-base:main@sha256:2e1536fac7a6eeff871bef3a4eb5acb386e1905f4267832159e018613b5c585e
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
