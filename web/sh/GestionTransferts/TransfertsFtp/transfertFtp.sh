#!/bin/sh

#TRANSFERT ftp des fichiers des automates
flagFtp='/tmp/.flagSymfonyDownloadFtp'
sytemLog=${BOILERBOX}/web/logs/system.log

nbScriptTransfert=$(ps -ef | grep "php.*BoilerBox.*transfert.*ftp" | grep -v grep | wc -l)

# Vérification qu'un téléchargement Ftp n'est pas en cours
if [ -e "$flagFtp" ] 
then
	if [ $nbScriptTransfert -eq 1 ]
	then
		echo `date +"%Y-%m-%d %T"`";[transfertFtp.sh] Le téléchargement des fichiers par ftp est déjà en cours d'execution" >> $sytemLog
	    exit 0
	fi
    if [ $nbScriptTransfert -eq 0 ]
    then
		echo `date +"%Y-%m-%d %T"`";[transfertFtp.sh] Flag de transfert ftp trouvé mais aucun script en cours d'execution" >> $sytemLog
	   	rm $flagFtp
    fi
fi

#Lancement du processus & sans attendre le retour de la commande
`exec php ${BOILERBOX}/app/console transfert:ftp 1>>/dev/null 2>&1`
