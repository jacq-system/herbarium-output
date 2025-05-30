FROM ghcr.io/jacq-system/symfony-base:main@sha256:f377db9997e1ce079d9ba80f1698a0f1b741750a3bf118cfb8fdfbca290759b1
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
