#!/bin/sh

# CREATION du rapport d'analyse
flagRapportAnalyse='/tmp/.flagSymfonyRapportAnalyse'
sytemLog=${BOILERBOX}/web/logs/system.log


# Vérification qu'un rapport d'analyse n'est pas déjà en cours de création
if [ -e $flagRapportAnalyse ]; then
        echo "Le rapport d'Analyse est déjà en cours de création"
	echo `date +"%Y-%m-%d %T"`";Le rapport d'Analyse est déjà en cours de création" >> $sytemLog
        exit 1
fi

#Lancement du processus & sans attendre le retour de la commande
`exec php ${BOILERBOX}/app/console creation:rapportsAnalyse 1>>/dev/null 2>&1`
exit 0
