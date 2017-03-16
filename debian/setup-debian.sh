#!/bin/sh

sudo apt-get update
sudo apt-get upgrade -y
sudo apt-get install -y make git emacs24-nox htop sysstat ufw fail2ban unattended-upgrades unzip imagemagick
echo "unattended-upgrades       unattended-upgrades/enable_auto_updates boolean true" | debconf-set-selections; dpkg-reconfigure -f noninteractive unattended-upgrades
