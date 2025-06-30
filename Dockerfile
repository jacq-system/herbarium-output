FROM ghcr.io/jacq-system/symfony-base:main@sha256:19a76df94d3790cf09df8f37d5d8f32281d87e845339f3b942a4cfa1a69ecff4
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
