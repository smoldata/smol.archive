# thingmonger

A web application for archiving your cloud-y bits.

## Are you a developer wanting to try this out?

You should start by setting up the [Vagrant virtual machine](https://github.com/smoldata/vagrant-thingmonger). It's not required, but it will make things go a lot smoother.

## How to install

```
mkdir /usr/local/smoldata
cd /usr/local/smoldata
git clone git@github.com:smoldata/thingmonger.git
cd thingmonger
make setup
```

## Things you may be asked during setup

* Accept the defaults for unattended upgrades
* Choose a password for your Certificate Authority (you will be prompted to enter it again)
* Choose a root MySQL password
* Enter your root MySQL password when prompted

## Load it up in a browser

If everything goes as planned, you should be able to load up https://dev.thingmonger.org/ in your browser.
