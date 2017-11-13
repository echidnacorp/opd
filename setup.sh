#!/usr/bin/env bash

cd /opt/tbs/wcms/obd/web/current/html

if [ -d  "modules" ]; then
	rm -r modules
	ln -s /vagrant/modules modules
fi

if [ -d  "modules" ]; then
	rm -r themes
	ln -s /vagrant/themes themes
fi
if [ -d  "modules" ]; then
	rm -r profiles
	ln -s /vagrant/profiles profiles
fi
