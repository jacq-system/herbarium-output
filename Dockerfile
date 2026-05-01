FROM ghcr.io/jacq-system/symfony-base:main@sha256:cd57b0db5bcc56a7891b809f03dfce74b6751c00074da0956a5e923101249216
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
