#!/bin/sh

sudo apt-get update
sudo apt-get upgrade -y
sudo apt-get install -y make git emacs24-nox htop sysstat ufw fail2ban unattended-upgrades unzip imagemagick
sudo echo "unattended-upgrades       unattended-upgrades/enable_auto_updates boolean true" | sudo debconf-set-selections; sudo dpkg-reconfigure -f noninteractive unattended-upgrades
