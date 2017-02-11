setup:
	debian/setup-debian.sh
	debian/setup-flamework.sh
	debian/setup-certified.sh
	sudo ubuntu/setup-certified-ca.sh
	sudo ubuntu/setup-certified-certs.sh
	bin/configure_secrets.sh .
