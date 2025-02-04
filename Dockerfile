FROM ghcr.io/jacq-system/symfony-base:main@sha256:64ea3e38c2f3ccd2dea3121de5044792afaa2f5c50c9ac68fa2c59d4525918e9
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
