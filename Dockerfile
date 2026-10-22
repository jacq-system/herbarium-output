FROM ghcr.io/jacq-system/symfony-base:main@sha256:ee3b18f20d97a46b1e9b113b4cb36baeb1e6d52e9ccf77e9a611438ac8ba9b8e
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
