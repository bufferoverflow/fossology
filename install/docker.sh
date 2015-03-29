#!/bin/bash

/etc/init.d/apache2 stop

/etc/init.d/postgresql start

/etc/init.d/fossology start

sleep 3
/usr/sbin/apache2ctl -X
