FROM ghcr.io/jacq-system/symfony-base:main@sha256:6d4ab9b474ee93570c3cac4d3067df3611c6c6cf46239d6e158b211dc8339f34
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
