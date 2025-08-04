FROM ghcr.io/jacq-system/symfony-base:main@sha256:f9838bd1cb9120ce94b93b9766063d459f13ec11884875fb8afd3d8de1781238
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
