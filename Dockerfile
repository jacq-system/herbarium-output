FROM ghcr.io/jacq-system/symfony-base:main@sha256:26012784d8df172583057201d45ded06ce03a82e5fe3e208d04b448c0170bf8b
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
