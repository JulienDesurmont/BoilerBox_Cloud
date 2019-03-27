#!/bin/sh

# Suppression des fichiers du cache BoilerBox
rm -rf ${BOILERBOX}/app/cache/prod/*
rm -rf ${BOILERBOX}/app/cache/dev/*

# Droits sur le cache
chmod -R 777 ${BOILERBOX}/app/cache

# Droits sur le dossier etat
chmod 777 ${BOILERBOX}/web/etats

# Droits sur le dossier web
chmod -R 777 ${BOILERBOX}/web/uploads
chmod 644 ${BOILERBOX}/web/uploads/.htaccess
# 	Droits sur les logs applicatifs
chmod -R 777 ${BOILERBOX}/web/logs
chmod 666 ${BOILERBOX}/web/logs/*.log
chmod 666 ${BOILERBOX}/web/logs/*.txt
# 	Droits sur les fichiers
chmod 755 ${BOILERBOX}/web/docs
chmod 644 ${BOILERBOX}/web/docs/*
# 	Droits sur les scripts shell
chmod -R 777 ${BOILERBOX}/web/sh
# 	Droits sur les répertoires des fichiers
chmod 777 ${BOILERBOX}/web/uploads
find ${BOILERBOX}/web/uploads -type d -exec chmod 777 {} \;
# 	Mise en place des droits root
chmod -R 755 ${BOILERBOX}/web/sh/*
# 	Droits d'écriture pour le fichier modifié par les scripts
chmod 777 ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/tmp_sed_table.sql



# Droits sur le dossier app
# 	Droits sur les logs system
chmod -R 777 ${BOILERBOX}/app/logs
chmod 666 ${BOILERBOX}/app/logs/*.log
# 	Droits sur le fichiers console 
chmod +x ${BOILERBOX}/app/console
# 	Droits sur le dossier spool
chmod -R 777 ${BOILERBOX}/app/spool


chown -R wwwrun:www ${BOILERBOX}


# Mise en place des droits sur les fichiers permettant de gérer les démarrages et redémarrages serveurs
#chmod 755 /etc
#chmod 755 /etc/init.d
#chown root:root /etc/init.d/boilerboxReboot
#chmod 777 /etc/init.d/boilerboxReboot

# Mise en place des droits sur le dossier apache2
#chmod 755 /etc/apache2
#chmod 644 /etc/apache2/default-server.conf
#chmod 644 /etc/apache2/httpd.conf
#chmod 644 /etc/apache2/listen.conf
#chmod 755 /etc/apache2/conf.d
#chmod 644 /etc/apache2/conf.d/boilerbox.conf

# Mise en place des droits sur le fichier de rotation des logs applicatifs [app] de boilerbox
#chmod 755 /etc/logrotate.d
#chmod 644 /etc/logrotate.d/boilerbox

# Mise en place des droits sur le fichier de configuration mysql
#chmod 644 /etc/mysql/my.cnf


# NOUVELLE VERSION DE PHP : PHP7 -> REVOIR LES CHEMINS
# Mise en place des droits sur le fichiers de configuration php
#chmod 755 /etc/php/7.0
#chmod 755 /etc/php/7.0/apache2
#chmod 644 /etc/php/7.0/apache2/php.ini

