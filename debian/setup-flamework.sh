#!/bin/sh

WHOAMI=`python -c 'import os, sys; print os.path.realpath(sys.argv[1])' $0`

UBUNTU=`dirname $WHOAMI`
ROOT=`dirname $UBUNTU`

sudo apt-get update
sudo apt-get -y install apache2 apache2-utils
sudo apt-get -y install php5 php5-cli php5-curl php5-mcrypt php5-memcache php5-mysql

for mod in proxy_wstunnel.load rewrite.load proxy.load proxy.conf proxy_http.load ssl.conf ssl.load socache_shmcb.load headers.load
do

    if [ -L /etc/apache2/mods-enabled/${mod} ]
    then
        sudo rm /etc/apache2/mods-enabled/${mod}
    fi

    if [ -f /etc/apache2/mods-enabled/${mod} ]
    then
        sudo mv /etc/apache2/mods-enabled/${mod} /etc/apache2/mods-enabled/${mod}.bak
    fi

    sudo ln -s /etc/apache2/mods-available/${mod} /etc/apache2/mods-enabled/${mod}
done

for conf_disable in javascript-common
do
    if [ -L /etc/apache2/conf-enabled/${conf_disable} ]
    then
        sudo rm /etc/apache2/conf-enabled/${conf_disable}
    fi

    if [ -f /etc/apache2/conf-enabled/${conf_disable} ]
    then
        sudo mv /etc/apache2/conf-enabled/${conf_disable} /etc/apache2/conf-enabled/${conf_disable}.disabled
    fi
done

for ctx in apache2 cli
do

    # We don't really need this part as long as 20-mcrypt.ini gets symplinked by default.
    # (20170317/dphiffer)

    #for mod in mcrypt.ini
    #do
    #    if [ -L /etc/php5/${ctx}/conf.d/${mod} ]
    #    then
    #        sudo rm /etc/php5/${ctx}/conf.d/${mod}
    #    fi

    #    if [ -f /etc/php5/${ctx}/conf.d/${mod} ]
    #    then
    #        sudo mv /etc/php5/${ctx}/conf.d/${mod} /etc/php5/${ctx}/conf.d/${mod}.bak
    #    fi

    #    sudo ln -s /etc/php5/mods-available/${mod} /etc/php5/${ctx}/conf.d/${mod}
    #done
    sudo perl -p -i -e "s/short_open_tag = Off/short_open_tag = On/" /etc/php5/${ctx}/php.ini;
done

if [ ! -d ${ROOT}/www/templates_c ]
then
    mkdir ${ROOT}/www/templates_c
fi

sudo chgrp -R www-data ${ROOT}/www/templates_c
sudo chmod -R g+ws ${ROOT}/www/templates_c

sudo /etc/init.d/apache2 restart
