#!/bin/sh

# CREATION du rapport journalier
flagRapportJournalier='/tmp/.flagSymfonyRapportJournalier'
sytemLog=${BOILERBOX}/web/logs/system.log

# Vérification qu'un rapport journalier n'est pas déjà en cours de création
if [ -e $flagRapportJournalier ]; then
        echo "Le rapport Journalier est déjà en cours de création"
	echo `date +"%Y-%m-%d %T"`";Le rapport Journalier est déjà en cours de création" >> $sytemLog	
        exit 1
fi
#Lancement du processus & sans attendre le retour de la commande
`exec php ${BOILERBOX}/app/console creation:rapports 1>>/dev/null 2>&1`
exit 0
