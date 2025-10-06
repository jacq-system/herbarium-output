FROM ghcr.io/jacq-system/symfony-base:main@sha256:fd91075786065529623e55bc6309dbbb2b0f4154ff2299ddefc845ffd47c9f13
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
