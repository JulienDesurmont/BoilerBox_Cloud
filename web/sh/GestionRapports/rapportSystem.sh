#!/bin/sh

# CREATION du rapport de système
flagRapportSystem='/tmp/.flagSymfonyRapportSystem'
sytemLog=${BOILERBOX}/web/logs/system.log

# Vérification qu'un rapport de système n'est pas déjà en cours de création
if [ -e $flagRapportSystem ]; then
        echo "Le rapport Système est déjà en cours de création"
	echo `date +"%Y-%m-%d %T"`";Le rapport Système est déjà en cours de création" >> $sytemLog	
        exit 1
fi
#Lancement du processus & sans attendre le retour de la commande
`exec php ${BOILERBOX}/app/console creation:rapportsSystem 1>>/dev/null 2>&1`
exit 0
