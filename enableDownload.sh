#!/bin/bash
BACKUPDIR=/volume1/web/scripts/.conf-backup NOW=$(date +"%Y-%m-%d")
##DSM 5
#APACHEDIR=/etc/httpd/conf/extra/mod_xsendfile.conf-user
##Synology DSM6
#APACHEDIR=/volume1/@appstore/WebStation/usr/local/etc/httpd/conf/extra/mod_xsendfile.conf-user
##Synology DSM6 for package Apache 2.2
#APACHEDIR=/volume1/@appstore/Apache2.2/usr/local/etc/apache22/conf/extra/mod_xsendfile.conf
##Synology DSM6 for package Apache 2.4
APACHEDIR=/volume1/@appstore/Apache2.4/usr/local/etc/apache24/conf/extra/mod_xsendfile.conf 

#if there is no difference between reference file and live file do not do anything
if ! diff -q mod_xsendfile.conf $APACHEDIR &>/dev/null; then
	#Backup Live File
	cp $APACHEDIR $BACKUPDIR/$NOW-mod_xsendfile.conf
	sed -i 's/^XSendFilePath .*/XSendFilePath \/volume1\/video/' $APACHEDIR
	#restart Apache2.4
	synoservice --restart pkgctl-Apache2.4 
else
	echo "nothing todo"; 
fi
