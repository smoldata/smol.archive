setup:
	debian/setup-debian.sh
	debian/setup-flamework.sh
	debian/setup-apache.sh
	debian/setup-secrets.sh
	debian/setup-db.sh smoldata smoldata

migrate_db:
	sudo -u www-data php bin/migrate_db.php
