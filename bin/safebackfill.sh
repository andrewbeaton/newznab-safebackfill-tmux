#!/bin/sh
# This script will safely backfill 1 day a time while completely processing all releases     
# before backfilling an additional day, in order to prevent a giant backlog in postprocessing.											      
#
# Additionally, it will run an optimize_db, update_parsing, update_cleanup and removespecial after
# a day is done processing.
#
# This script assumes everything else in your newznab install is functioning properly.

set -e

# Read in newznab-safebackfill-tmux config file
for i in `cat ../config/newznab-safebackfill-tmux.conf | grep '^[^;].*'`
do
    var=`echo "$i" | awk -F "=" '{print $1}'`
    param=`echo "$i" | awk -F "=" '{print $2}'`
    eval $var=$param
done

export PHP_PATH=$PHP_PATH
export NEWZNAB_DIR=$NEWZNAB_DIR

# Read in newznab config file
eval $( sed -n "/^define/ { s/.*('\([^']*\)', '*\([^']*\)'*);/eval \1=\"\2\"/; p }" /var/www/newznab/www/config.php )

MYSQL_DBNAME=$DB_NAME
MYSQL_HOST=$DB_HOST
MYSQL_USER=$DB_USER
MYSQL_PASS=$DB_PASSWORD

#Begin script
while :

do

cd ${NEWZNAB_DIR}/misc/update_scripts

#Get our groups up to date before starting a backfill day, threaded or not.
#${PHP_PATH} ${NEWZNAB_DIR}/misc/update_scripts/update_binaries.php
${PHP_PATH} ${NEWZNAB_DIR}/misc/update_scripts/update_binaries_threaded.php
${PHP_PATH} ${NEWZNAB_DIR}/misc/update_scripts/update_releases.php

#Run backfill, either regular or threaded, take your pick.
#${PHP_PATH} ${NEWZNAB_DIR}/misc/update_scripts/backfill.php
${PHP_PATH} ${NEWZNAB_DIR}/misc/update_scripts/backfill_threaded.php

#Run update_releases once so we know how many releases we are dealing with.
${PHP_PATH} ${NEWZNAB_DIR}/misc/update_scripts/update_releases.php

#Check the number of releases waiting for postproc
RELEASE_BACKLOG=`mysql -u $MYSQL_USER -h $MYSQL_HOST -p$MYSQL_PASS $MYSQL_DBNAME -s -N -e "select COUNT(*) from releases r left join category c on c.ID = r.categoryID where (r.passwordstatus between -6 and -1) or (r.haspreview = -1 and c.disablepreview = 0)"`
LOOPCOUNTER="0"

while [ $RELEASE_BACKLOG -gt 10 ]; do 
${PHP_PATH} ${NEWZNAB_DIR}/misc/update_scripts/update_releases.php

RELEASE_BACKLOG=`mysql -u $MYSQL_USER -h $MYSQL_HOST -p$MYSQL_PASS $MYSQL_DBNAME -s -N -e "select COUNT(*) from releases r left join category c on c.ID = r.categoryID where (r.passwordstatus between -6 and -1) or (r.haspreview = -1 and c.disablepreview = 0)"`
LOOPCOUNTER=`expr $LOOPCOUNTER + 1`
if [ $LOOPCOUNTER -gt $BINARIES_KEEPUP ]
then

#Update binaries and process releases every time update_releases loops the number of times defined in BINARIES_KEEPUP
#Again, choose threaded or not.

#${PHP_PATH} ${NEWZNAB_DIR}/misc/update_scripts/update_binaries.php
${PHP_PATH} ${NEWZNAB_DIR}/misc/update_scripts/update_binaries_threaded.php
${PHP_PATH} ${NEWZNAB_DIR}/misc/update_scripts/update_releases.php
LOOPCOUNTER="0"

fi

done

echo "Finished backfilling another day, running optimizations and cleanup."
sleep 5
${PHP_PATH} ${NEWZNAB_DIR}/misc/testing/update_parsing.php
${PHP_PATH} ${NEWZNAB_DIR}/misc/testing/removespecial.php
${PHP_PATH} ${NEWZNAB_DIR}/misc/testing/update_cleanup.php
${PHP_PATH} ${NEWZNAB_DIR}/misc/update_scripts/optimise_db.php

echo "Optimization done, on to backfilling another day."
mysql -u $MYSQL_USER -h $MYSQL_HOST -p$MYSQL_PASS $MYSQL_DBNAME -e "UPDATE groups set backfill_target=backfill_target+1 where active=1 and backfill_target<$BACKFILL_DAYS;"
sleep 5

done
