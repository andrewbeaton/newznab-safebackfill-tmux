#!/usr/bin/env bash
set -e

# Read in config file
for i in `cat ../config/newznab-safebackfill-tmux.conf | grep '^[^;].*'`
do
    var=`echo "$i" | awk -F "=" '{print $1}'`
    param=`echo "$i" | awk -F "=" '{print $2}'`
    eval $var=$param
done

while :
do
    $PHP_PATH monitor.php
done
