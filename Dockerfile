FROM ghcr.io/jacq-system/symfony-base:main@sha256:e70d381b8d440bc23b7f9668a9e8964882eaee388258fe99d24985720bb05a1f
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
