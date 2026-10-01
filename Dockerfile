FROM ghcr.io/jacq-system/symfony-base:main@sha256:52d5087010eab8be368daff59c07f832a6393cd18ac8567d82bc900dffc0a833
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
