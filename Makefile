setup:
	debian/setup-debian.sh
	debian/setup-flamework.sh
	debian/setup-apache.sh
	bin/configure_secrets.sh .
	debian/setup-db.sh smoldata smoldata
