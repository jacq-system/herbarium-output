FROM ghcr.io/jacq-system/symfony-base:main@sha256:5cafa6f3ad50c5af055e4d6223be40a5a5c13e575eeb090627c5170c1513697f
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
