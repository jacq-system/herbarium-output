FROM ghcr.io/jacq-system/symfony-base:main@sha256:a8f2706069c930699aaa00bd32907443dc4536d4a61c3eae0856e4dca4399726
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
