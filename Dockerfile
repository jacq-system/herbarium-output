FROM ghcr.io/jacq-system/symfony-base:main@sha256:fd92ad4ebf96041bec553cbc10ed44335018f2254cf9b760d600c8b95c5d3806
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
