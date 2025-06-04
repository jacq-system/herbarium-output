FROM ghcr.io/jacq-system/symfony-base:main@sha256:718f2b917a72f2586f1eaf9b07716d0ba888638b4b9b0753b0e327383b9ae065
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
