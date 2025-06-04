FROM ghcr.io/jacq-system/symfony-base:main@sha256:ec596911a05285094f49b10ec56cc86b36085aca16ee722ba1d361912dd5cfed
LABEL org.opencontainers.image.source=https://github.com/jacq-system/symfony
LABEL org.opencontainers.image.description="JACQ herbarium service Symfony"
ARG GIT_TAG
ENV GIT_TAG=$GIT_TAG

COPY  --chown=www:www htdocs /app
RUN chmod -R 777 /app/var
