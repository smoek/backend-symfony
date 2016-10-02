#!/usr/bin/env bash

CONTAINER_NAME=symfony-smoek

cd `dirname $0` && docker run --rm --volume /tmp:/tmp \
    --volume /home/georg/coding/smoek/backend/symfony:/home/georg/coding/smoek/backend/symfony $CONTAINER_NAME \
    php "$@"