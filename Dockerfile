FROM ghcr.io/jacq-system/symfony-base:main@sha256:91e2f9bbd6eb1d2273be822bc1f37670fdf62a15d82ff908bd5951d75b060538
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
