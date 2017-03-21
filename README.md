# Thingmonger

:robot: A web application for :floppy_disk: archiving your :cloud: cloud-y bits.

## Are you looking to try this out?

It's almost there, but still not quite yet ready.

## Are you a developer wanting to run this yourself?

You should start by setting up the [Vagrant virtual machine](https://github.com/smoldata/vagrant-thingmonger). It's not required, but it will make things go a lot smoother.

This is a [LAMP](https://en.wikipedia.org/wiki/LAMP_(software_bundle)) web application, written for PHP 5.6, MySQL 5.6 and Apache 2.4. It inherits its back-end approach from [Flamework](https://github.com/exflickr/flamework) (see: [philosophy](https://github.com/exflickr/flamework/blob/master/docs/philosophy.md) and [style guide](https://github.com/exflickr/flamework/blob/master/docs/style_guide.md)) and uses [Bootstrap](http://getbootstrap.com/) for the front-end implementation.

## This <del>is</del> *will be* a federated service

The software is being designed with a federated hosting model in mind. Something like: you plug a Raspberry Pi into your home internet router, it connects to the Smol Data VPN, and joins a kind of mini-cloud to host your data on your own hardware. The idea being that at any time you could unplug the thumb drive from the Raspberry Pi and back it up somewhere of your choosing.

But, the software is just not there yet. It's aspriational, it won't be required of all users, but it's an important design constraint going forward.

## How to install

This assumes either [Ubuntu 14.04](https://wiki.ubuntu.com/TrustyTahr/ReleaseNotes) (dev) or [Raspbian Jessie](https://www.raspberrypi.org/downloads/raspbian/) (prod):

```
mkdir /usr/local/smoldata
cd /usr/local/smoldata
git clone git@github.com:smoldata/thingmonger.git
cd thingmonger
make setup
```

## Things you may be asked during setup

* Accept the defaults for unattended upgrades
* Choose a root MySQL password (choose something you will remember later)

## Load it up in a browser

If everything goes as planned, you should be able to load up http://localhost:4700/ in your browser.

## Services

Right now there is support for archiving [Twitter](https://twitter.com/) and [mlkshk](https://mlkshk.com/) accounts (the latter got a priority boost because it is shutting down soon). Instagram is next in line, then maybe Wikipedia? Jeez, maybe [YouTube should come sooner than later](https://twitter.com/coolerpatrol/status/843614265693147138) if they [can't get their shit together](https://www.theguardian.com/media/2017/mar/16/guardian-pulls-ads-google-placed-extremist-material).

## Secrets

Each service you add will need API keys and secrets. Here are some links for you to set up your own applications:

* [Twitter](https://apps.twitter.com/)
* [mlkshk](http://mlkshk.com/developers)

Then add the following to your `www/include/secrets.php`:

```
# Twitter API
$GLOBALS['cfg']['twitter_api_consumer_key'] = '...';
$GLOBALS['cfg']['twitter_api_consumer_secret'] = '...';

# mlkshk API
$GLOBALS['cfg']['mlkshk_api_key'] = '...';
$GLOBALS['cfg']['mlkshk_api_secret'] = '...';
```

## I OAuth'd my account but ... nothing is happening?

The script you want to run is `bin/download.php`. Here is how you can test it out to make sure things are working as expected.

```
cd /usr/local/smoldata/thingmonger
sudo -u www-data php bin/download.php --verbose
```

Here is an entry for your `/etc/crontab` to run `bin/download.php` every 1 minute.

```
* * * * * www-data /usr/bin/php /usr/local/smoldata/thingmonger/bin/download.php
```

And don't forget the thing about crontabs needing a line break at the end of the file.

## Shoutouts

* [Starmonger](https://github.com/dphiffer/starmonger/)
* [Parallel Flickr](http://straup.github.io/parallel-flickr/)
* [ThinkUp](https://github.com/ThinkUpLLC/ThinkUp)
* [Tilde Club](http://tilde.club/)
