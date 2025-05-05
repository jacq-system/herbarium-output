FROM ghcr.io/jacq-system/symfony-base:main@sha256:331732cc8e053e6a20204fdd90186bb76487200cadaf72de6549a5cc2d69e767
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
