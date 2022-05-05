#!/usr/bin/env bash
set -e

uid=$(stat -c %u /app)
gid=$(stat -c %g /app)

if [ $uid == 0 ] && [ $gid == 0 ]; then
    if [ $# -eq 0 ]; then
        php
    else
        exec "$@"
    fi
fi

sed -i -r "s/foo:x:[0-9]+:[0-9]+:/foo:x:$uid:$gid:/g" /etc/passwd
sed -i -r "s/foo:x:[0-9]+:/foo:x:$gid:/g" /etc/group

chown $uid:$gid /home/foo

if [ $# -eq 0 ]; then
    php
else
    exec gosu foo "$@"
fi
