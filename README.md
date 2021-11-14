# KodiWebPortal
**The Web Portal for Kodi to display, search and download your personal multimedia content**

## Demo

You can try **Kodi Web Portal** [here](http://kodi.asafety.fr/) (http://kodi.asafety.fr/)

* Login : **kodi**
* Password : **K0d1P4s5W0rD**

## Description

**Kodi Web Portal** is a web interface to browse, display, search and eventually download your Kodi multimedia content indexed. This web application is very light, without framework (except JQuery) and dependencies.

**Kodi Web Portal** needs to be deployed with a direct file access to your media content if the downloading feature is enabled. This web application requires an access to the MySQL's Kodi database.

You can use Kodi (XBMC) with a MySQL/MariaDB database to centralized all your multimedia content. This dabatase contains all your movies references, tv shows, details about them (script writers, realisators, actors, studio, synopsis, original title and in your language, etc.), fanart and thumb URLs and file path to play the content.

**Kodi Web Portal** allows you to access your Kodi's database through a simple web browser.

![Alt text](/screenshots/002.jpg?raw=true "Kodi Web Portal")
![Alt text](/screenshots/005.jpg?raw=true "Trailer preview")
![Alt text](/screenshots/014.jpg?raw=true "TV Show season browsing")

Original idea of **Kodi Web Portal** came from my personal centralization of all backuped movies and tv shows into a Synology NAS.
Through this excellent kind of NAS, you can deploy additionnal module in the DSM, like :
* Web Station : Apache (2.2 / 2.4) and PHP server (5.6 / 7.2 / 7.3 / 7.4)
* MariaDB : fork of MySQL database (MariaDB 5 or 10)
* phpMyAdmin : to administrate the MariaDB database
* Directory Server : an LDAP server in the Synology for manage users and groups
A Synology NAS acts as a complete web server to host my Kodi's data and the **Kodi Web Portal**.

I wanted to use my Synology NAS with these features to provide a private and personal web interface displaying all my Kodi's movies and TV show scraped, and so, if I was outside home, I would have been able to browse and download my personal content with ease. **Kodi Web Portal** was born.

**Kodi Web Portal** interface is available in French (fr), English (en - default), Spanish (es) and German (de). Language is automatically choosen depending on your browser's language.

## Compatibility

**Kodi's versions :**
* Kodi Matrix 19.3 (default database name "xbmc_video119")
* Kody Leia 18.2 (default database name "xbmc_video116")
* Kodi Krypton 17.0 (default database name "xmbc_video107")
* Kodi Jarvis 16.1 (default database name "xmbc_video99")
* Kodi Jarvis 16.0 (default database name "xmbc_video99")
* Kodi Isengard 15.2 (default database name "xbmc_video93")

**Kodi Web Portal** is configured by default to choose the most recent Kodi's database (xbmc_videoXXX or MyVideosXXX).

**System :**
* Apache server (2.2 / 2.4) with PHP (>= 5.5, 5.6 / 7.2 / 7.3 / 7.4) (Windows / Linux)
* Synology NAS (DSM 5, DSM 6, DSM 7)

## Authentication

Access to **Kodi Web Portal** can be :
* Anonymous : no login/password required, **Kodi Web Portal** content is displayed to anyone
* Internal authentication : user accounts (login / password) are defined in the config.php file
* LDAP authentication : Kodi Web Portal delegate the authentication to an LDAP directory, with group membership check.
* Chaining authentication : check authentication with internal account, then LDAP.

![Alt text](/screenshots/001.jpg?raw=true "Authentication page")

## How to install Kodi Web Portal ?

Just clone the Git repo source code and edit the "config.php" file.
Apache server who host the **Kodi Web Portal** needs :
* mod_xsendfile : to be able to sent big file, like a movie, through HTTP/HTTPS.
* PHP >= 5.5, 6 or 7 : if you want to use internal authentication mecanism. Password are hashed with bcrypt.
* php-pdo_mysql module : to communicate with the Kodi's database MySQL/MariaDB
* php-ldap module : only if you want to authenticate your users on LDAP directory.
* access to your multimedia content through filesystem (with mounting point or stored locally)

## How to configure my Synology NAS to use Kodi Web Portal?

For this specific deployement on a Synology NAS, you need :
* Web Station package installed via DSM
* PHP package installed via DSM
* MariaDB package installed via DSM
* phpMyAdmin package installed via DSM (just for administration tasks, this package can be stopped after)
* Directory Server package installed via DSM (only if you need to manage users and groups through LDAP)

![Alt text](/screenshots/synology/DSM6_config_003.jpg?raw=true "Synology DSM packages")

Check PHP configuration for loading right modules (pdo_mysql, ldap if needed...) :

![Alt text](/screenshots/synology/DSM6_config_007.jpg?raw=true "PHP configuration")

Configure your Kodi to use the MariaDB/MySQL database on your NAS with a dedicated account (Kodi creates a database like "xbmc_videoXXX" with "XXX" a number automatically defined).

![Alt text](/screenshots/synology/DSM6_config_005.jpg?raw=true "Kodi's database via phpMyAdmin")

Once Kodi has pushed all your multimedia content into the SQL database, check if all your media content files are presents in a dedicated Synology share like "MEDIATHEQUE".

![Alt text](/screenshots/synology/DSM6_config_001.jpg?raw=true "Movies and TVShows centralized in a share")

Allow the internal Synology user "httpd" to access to this "MEDIATHEQUE" share with "read" permission (in the DSM, edit "permission" on the "MEDIATHEQUE" share, choose "Internal Group" and enable "read" for user "httpd").

![Alt text](/screenshots/synology/DSM6_config_002.jpg?raw=true "Allow httpd to access this share")

Finaly, you have to edit an Apache config file manualy through SSH, to add the "/volume1/MEDIATHEQUE" path usable by the X-send-file Apache module (and only this path in the XSendFilePath directive).
* vi /etc/httpd/conf/extra/mod_xsendfile.conf-user # Synology DSM5
* vi /volume1/@appstore/WebStation/usr/local/etc/httpd/conf/extra/mod_xsendfile.conf-user # Synology DSM6
* vi /volume1/@appstore/Apache2.2/usr/local/etc/apache22/conf/extra/mod_xsendfile.conf # Synology DSM6/7 for package Apache 2.2
* vi /volume1/@appstore/Apache2.4/usr/local/etc/apache24/conf/extra/mod_xsendfile.conf # Synology DSM6/7 for package Apache 2.4
* For the next updates and evolution of DSM version and package version, it's possible to use the following command to find the right mod_xsendfile.conf via SSH : find / -name "\*xsendfile\*conf\*"

```shell
[...]
XSendFilePath /volume1/MEDIATHEQUE
[...]
```

![Alt text](/screenshots/synology/DSM6_config_004.jpg?raw=true "Update XSendFilePath through SSH")

**Note : be carefull, after any upgrade of your DSM or WebStation package, XSendFile configuration is reset, so you have to edit this file again to allow download.**
You can automatize the XSendFile update via a crontab script, please refers to https://github.com/yanncam/KodiWebPortal/issues/12.

Reboot the Synology's Apache server to load the new configuration (stop the package then restart it).

![Alt text](/screenshots/synology/DSM6_config_006.jpg?raw=true "Reboot Apache server")

## Ideas for the future...

* Adding SQL users authentication
* Adding users comments on movies / TV show
* Track movies / tvshow watched by a user himself
* Create an install.php page with compatibility checks and auto-create config.php file
* Add "stars" on new entry since the last connection of a user
* Add "Music" and others Kodi's category
* Extend functionality with home personal streaming

## Misc

Thanks to all contributor !
* vmauduit
* @boing86
* @JayRabas
