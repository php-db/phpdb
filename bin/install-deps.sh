#!/usr/bin/env bash

/usr/bin/composer validate && \
/usr/bin/composer --ignore-platform-reqs install \
    --no-ansi --no-progress --no-scripts \
    --classmap-authoritative --no-interaction \
    --quiet