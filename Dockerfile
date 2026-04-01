FROM ghcr.io/jacq-system/symfony-base:main@sha256:46099667b57c85810b6a79e96f1b0855cb901d16011ab6394b2ac427bd10ab6d
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
