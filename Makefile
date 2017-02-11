setup:
	debian/setup-debian.sh
	debian/setup-certified.sh
	sudo debian/setup-certified-ca.sh
	sudo debian/setup-certified-certs.sh
	debian/setup-flamework.sh
	bin/configure_secrets.sh .
	debian/setup-db.sh thingmonger thingmonger
