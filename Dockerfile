FROM ghcr.io/jacq-system/symfony-base:main@sha256:4b499df3b1051a7d3f49f6ae1c3d9315399618d5cd2a98d117af9c281a1de499
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
