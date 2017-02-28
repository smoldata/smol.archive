#!/bin/sh

sudo apt-get update
sudo apt-get upgrade -y
sudo apt-get install -y make git emacs24-nox htop sysstat ufw fail2ban unattended-upgrades unzip
sudo dpkg-reconfigure --priority=low unattended-upgrades

sudo touch /var/log/smol_dbug.log
sudo chown www-data:www-data /var/log/smol_dbug.log
