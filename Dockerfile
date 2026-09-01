FROM ghcr.io/jacq-system/symfony-base:main@sha256:c77639e2156d586ca00c3737a5b7020ed8fec9f5715ee897f6e75413bce13887
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
