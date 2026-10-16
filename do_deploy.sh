#!/usr/bin/env bash
#
# Simple script to deploy webpages to their correct location
#

set -e

DEPLOYDIR=/storage/www/cpdnboinc/html/user/cpdn

if [ ! -d "$DEPLOYDIR" ]; then
    echo "$DEPLOYDIR does not exist; creating it"
    mkdir $DEPLOYDIR
fi

echo "Copying OIFS webpages into position on live server.."
set -v

for page in oifs_dashboard.php
do
    cp -i $page $DEPLOYDIR
done

