FROM ghcr.io/jacq-system/symfony-base:main@sha256:c0afab7977cf319efa525a498b2a0634786115bbaa6af1ba175eebaf86f44bfb
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
