#!/bin/bash

WHOAMI=`python -c 'import os, sys; print os.path.realpath(sys.argv[1])' $0`
UBUNTU=`dirname $WHOAMI`

PROJECT=`dirname $UBUNTU`
SECRETS="${PROJECT}/www/include/secrets.php"

DBNAME=$1
USERNAME=$2

if [ "${DBNAME}" = "" ]
then
	echo "missing database name"
	exit 1
fi

if [ "${USERNAME}" = "" ]
then
	echo "missing database username"
	exit 1
fi

MYSQL=`which mysql`

if [ "${MYSQL}" = "" ]
then
	CHIPSET=`uname -m`
	if [[ $CHIPSET == arm* ]]
	then
		# This isn't foolproof, but it kiiiiiinda looks like we're
		# running on a Raspberry Pi. Let's assume that's the case. For
		# now this just means we need to convince apt-get into letting
		# us install MySQL 5.6. (20170317/dphiffer)
		sudo sh -c 'echo "deb http://archive.raspbian.org/raspbian/ stretch main" >> /etc/apt/sources.list'
	fi
	sudo apt-get update
	sudo apt-get install -y mysql-server-5.6 mysql-client-5.6
fi

# We probably don't care about any errors...
PHP='php -d display_errors=off -q'

PASSWORD=`${PHP} ${PROJECT}/bin/generate_secret.php`

if [ -f /tmp/${DBNAME}.sql ]
then
	rm /tmp/${DBNAME}.sql
	touch /tmp/${DBNAME}.sql
fi

echo "DROP DATABASE IF EXISTS ${DBNAME};" >> /tmp/${DBNAME}.sql;
echo "CREATE DATABASE ${DBNAME};" >> /tmp/${DBNAME}.sql
#echo "DROP user '${USERNAME}'@'localhost';" >> /tmp/${DBNAME}.sql
echo "CREATE user '${USERNAME}'@'localhost' IDENTIFIED BY '${PASSWORD}';" >> /tmp/${DBNAME}.sql
echo "GRANT SELECT,UPDATE,DELETE,INSERT ON ${DBNAME}.* TO '${USERNAME}'@'localhost' IDENTIFIED BY '${PASSWORD}';" >> /tmp/${DBNAME}.sql
echo "FLUSH PRIVILEGES;" >> /tmp/${DBNAME}.sql

echo "USE ${DBNAME};" >> /tmp/${DBNAME}.sql;

for f in `ls -a ${PROJECT}/schema/*.schema`
do
	echo "" >> /tmp/${DBNAME}.sql
	cat $f >> /tmp/${DBNAME}.sql
done

echo "Please enter your MySQL root password."
mysql -u root -p < /tmp/${DBNAME}.sql

unlink /tmp/${DBNAME}.sql

perl -p -i -e "s/GLOBALS\['cfg'\]\['db_main'\]\['pass'\] = '[^']*'/GLOBALS\['cfg'\]\['db_main'\]\['pass'\] = '${PASSWORD}'/" ${SECRETS};
perl -p -i -e "s/GLOBALS\['cfg'\]\['db_users'\]\['pass'\] = '[^']*'/GLOBALS\['cfg'\]\['db_users'\]\['pass'\] = '${PASSWORD}'/" ${SECRETS};
perl -p -i -e "s/GLOBALS\['cfg'\]\['db_accounts'\]\['pass'\] = '[^']*'/GLOBALS\['cfg'\]\['db_accounts'\]\['pass'\] = '${PASSWORD}'/" ${SECRETS};

exit 0
