#!/bin/bash
#
# Removal of local caches (except hierarchy cache)
#
# To specify your own LOCAL_DIR, use e.g.:
# export LOCAL_DIR=/usr/local/vufind/httpd/local/classic/dev; sudo -E bash -c cli/removeLocalCache.sh
#
#

VUFIND_BASE=/usr/local/vufind/httpd
if [ -z "$LOCAL_DIR" ]; # if $LOCAL_DIR empty or unset, use default localdir
    then export VUFIND_LOCAL=${VUFIND_BASE}/local;
    else export VUFIND_LOCAL=$LOCAL_DIR;
fi

echo $LOCAL_DIR
VUFIND_CACHE=$VUFIND_LOCAL/cache

if [ "$UID"  -eq 0 ]; then

    echo "Tryinig to remove local cache: "
    echo $VUFIND_CACHE
    # no removal of hierarchy cache
    rm -rf $VUFIND_CACHE/searchspecs/*
    rm -rf $VUFIND_CACHE/objects/*
    rm -rf $VUFIND_CACHE/languages/*

    echo "now restart apache ..."

    service apache2 restart

else
        echo "You have to be root to start this script!"
        exit 1
fi