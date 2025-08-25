FROM ghcr.io/jacq-system/symfony-base:main@sha256:67b99ce661dda2a49de0ca58cecccd2617c7897016cf0cd52c2eba22985a3ca4
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
