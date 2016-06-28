#!/bin/bash

BASEDIR=$(dirname $0)
INDEX="$BASEDIR/../public/index.php"

if [ -z "$LOCAL_DIR" ]; # if $LOCAL_DIR empty or unset, use default localdir
   then export VUFIND_LOCAL_DIR=$LOCAL_DIR;
   else export VUFIND_LOCAL_DIR=${BASEDIR}/../local;
fi

export VUFIND_LOCAL_MODULES=Swissbib

php $INDEX tab40import $@