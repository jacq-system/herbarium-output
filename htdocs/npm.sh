#!/usr/bin/env bash
ARGS="$@"

if [ $# -eq 0 ]; then
	COMMAND="install"
	else
	COMMAND=$ARGS
	fi

docker run --rm --interactive --tty \
        --volume ${PWD}:/data \
        --volume $HOME/.npm:/tmp \
        --user=$(id -u):$(id -g) \
        -w="/data" \
        --entrypoint "npm" \
        node $COMMAND
