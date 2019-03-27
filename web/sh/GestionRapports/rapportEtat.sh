#!/bin/sh

# CREATION du rapport d'analyse
flagRapportEtat='/tmp/.flagSymfonyRapportEtat'
sytemLog=${BOILERBOX}/web/logs/system.log
nbScriptEtat=$(ps -ef | grep "php.*BoilerBox.*creation.*rapportsEtat" | grep -v grep | wc -l)

# Vérification que la création des rapports d'etat n'est pas déjà en cours de fonctionnement
if [ -e $flagRapportEtat ]
then
    if [ $nbScriptEtat -eq 1 ]
	then
        echo "La création des rapports d'etat est déjà en cours de fonctionnement"
		echo `date +"%Y-%m-%d %T"`";La création des rapports d'etat est déjà en cours de fonctionnement" >> $sytemLog
        exit 1
	fi
	if [ $nbScriptEtat -eq 0 ]
	then
		echo "Flag de création des rapports d'Etat trouvé mais aucun script en cours d'execution"
		echo `date +"%Y-%m-%d %T"`";[importBin.sh] Flag de création des rapports d'Etat trouvé mais aucun script en cours d'execution" >> $sytemLog
		rm $flagRapportEtat
	fi
fi

# Création du flag et lancement du script
touch $flagRapportEtat
chmod 666 $flagRapportEtat
chown wwwrun $flagRapportEtat
retour=`nice -0 php ${BOILERBOX}/app/console creation:rapportsEtat`
# Libération du flag
rm $flagRapportEtat
exit 0
