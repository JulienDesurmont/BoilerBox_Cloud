#!/bin/sh

# CREATION du rapport de sécurité
flagRapportSecurite='/tmp/.flagSymfonyRapportSecurite'
sytemLog=${BOILERBOX}/web/logs/system.log


# Vérification qu'un rapport de sécurité n'est pas déjà en cours de création
if [ -e $flagRapportSecurite ]; then
        echo "Le rapport de sécurité est déjà en cours de création"
	echo `date +"%Y-%m-%d %T"`";Le rapport de sécurité est déjà en cours de création" >> $sytemLog
        exit 1
fi

#Lancement du processus & sans attendre le retour de la commande
`exec php ${BOILERBOX}/app/console creation:rapportsSecurite 1>>/dev/null 2>&1`
exit 0
