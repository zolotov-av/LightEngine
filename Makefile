
.PHONY: all clean distclean

all: deb_lightengine

clean: deb_clean_lightengine

distclean: clean
	rm -f lightengine.deb

#### LightEngine
.PHONY: deb_lightengine deb_install_lightengine deb_clean_lightengine

deb_lightengine: deb_clean_lightengine deb_install_lightengine
	fakeroot dpkg-deb --build deb_lightengine lightengine.deb

deb_install_lightengine:
	mkdir -p deb_lightengine/etc/lightengine
	mkdir -p deb_lightengine/var/lib/lightengine/modules
	mkdir -p deb_lightengine/var/lib/lightengine/engine/modules
	mkdir -p deb_lightengine/var/lib/lightengine/cache
	install -m 0644 system-dpkg.php deb_lightengine/etc/lightengine/system.php
	install -m 0644 config/db-example-mysql.php deb_lightengine/etc/lightengine/db.config.php
	install -m 0644 core-dpkg.php deb_lightengine/var/lib/lightengine/engine/core.php
	install -m 0644 engine/engine.php deb_lightengine/var/lib/lightengine/engine/
	install -m 0644 engine/modules/*.php deb_lightengine/var/lib/lightengine/engine/modules

deb_clean_lightengine:
	rm -rf deb_lightengine/etc
	rm -rf deb_lightengine/var
