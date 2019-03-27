#!/bin/sh

fichierlog=${BOILERBOX}/web/logs/system.log
scriptArret=${BOILERBOX}/web/sh/Developpement/arretDesScriptsDImports.sh

# Log de l'arrêt demandé du sserveur
echo `date +"%Y-%m-%d %T"`";Arrêt du serveur demandé;Début d'arrêt des scripts BoilerBox" >> $fichierlog

flagArretServeur='/tmp/.flagBoilerBoxArretServeur'
# Création du flag indiquant que l'arret du serveur est en cours => Arrêt des scripts BoilerBox demandé
touch $flagArretServeur


#Vérification que les scripts de création des fichiers binaires et d'importation des données ne sont pas en cours d'execution
flagBoilerBoxCreationBinaire='/tmp/.flagBoilerBoxScriptCreationBinaires'
flagBoilerBoxImportationBinaire='/tmp/.flagBoilerBoxImportBinaires'
# Attente de la fin des importations / création des fichiers binaires
while true
do
	if [ -e "$flagBoilerBoxCreationBinaire" ] || [ -e "$flagBoilerBoxImportationBinaire" ]
	then
		nbScriptsImport=$(ps -ef | grep "php.*BoilerBox.*import.*bin" | grep -v grep | wc -l)
		if  [ $nbScriptsImport -eq 1 ]
		    sleep 2
		elif [ $nbScriptsImport -eq 0 ]
		    echo "Le Flag d'importation existe mais aucun script d'importation n'a été trouvé"
		    break
		fi
	else
		break
	fi
done


flagBoilerBoxDownloadFtp='/tmp/.flagBoilerBoxDownloadFtp'
# Attente de la fin des transferts Ftp
while true
do
        if [ -e "$flagBoilerBoxDownloadFtp" ]
        then
                nbScriptsTransfert=$(ps -ef | grep "php.*BoilerBox.*transfert.*ftp" | grep -v grep | wc -l)
                if  [ $nbScriptsTransfert -eq 1 ]
                    sleep 2
                elif [ $nbScriptsTransfert -eq 0 ]
                    echo "Le Flag de transfert ftp existe mais aucun script de transfert n'a été trouvé"
                    break
                fi
        else
                break
        fi
done


# Arrêt du scripts d'importation et suppression du flag
${BOILERBOX}/web/sh/Developpement/arretDesScriptsDImports.sh
flagBoilerBox='/tmp/.flagBoilerBox*';
rm $flagBoilerBox 1>>$fichierlog 2>&1
echo `date +"%Y-%m-%d %T"`";Arrêt du serveur demandé;Fin d'arrêt des scripts BoilerBox" >> $fichierlog
exit 0
