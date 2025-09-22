FROM ghcr.io/jacq-system/symfony-base:main@sha256:d3283aaffea1cf416b6a387604aa9c1a7159560241b5b62072bc39d13b6b1106
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
