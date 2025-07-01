FROM ghcr.io/jacq-system/symfony-base:main@sha256:4c746f7106142510f9b682b8118ceb8d78725927b5a68ee13bcca60fd170a94a
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
