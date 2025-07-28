FROM ghcr.io/jacq-system/symfony-base:main@sha256:37f2511e6122c32276a2f8b2fcdf144cfcfbefc575578a07a7d4d1df06dabd35
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
