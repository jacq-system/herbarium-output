FROM ghcr.io/jacq-system/symfony-base:main@sha256:ab6a923c2a05191a40a493ddda2990cccb1d7eff6b7c36aeb32b7500718e7a4d
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
