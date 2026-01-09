FROM ghcr.io/jacq-system/symfony-base:main@sha256:c599d8cc7442b334794c82ab7be58eb60b508847ebae4931512b378fb1b14c21
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
