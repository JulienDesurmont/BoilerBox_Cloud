#!/bin/sh

fichierlog=${BOILERBOX}/web/logs/system.log

# Log du démarrage du serveur
echo `date +"%Y-%m-%d %T"`";Démarrage du serveur" >> $fichierlog


# Suppression de tous les flags BoilerBox n'ayant pas été supprimés avant le démarrage du system
# 1) Si le flag du script d'importation des fichiers en base existe suppression de celui-ci
flagBoilerBoxImportBinaires='/tmp/.flagBoilerBoxImportBinaires'
flagBoilerBoxScriptImportBinaires='/tmp/.flagBoilerBoxScriptImportBinaires'
flagBoilerBoxDownloadFtp='/tmp/.flagBoilerBoxDownloadFtp'
flagBoilerBoxArretServeur='/tmp/.flagBoilerBoxArretServeur'

if  [ -e "$flagBoilerBoxImportBinaires" ]
then
	echo "Flag [ $flagBoilerBoxImportBinaires ] trouvé : Suppression du flag" >> $fichierlog
	rm $flagBoilerBoxImportBinaires
fi
if  [ -e "$flagBoilerBoxScriptImportBinaires" ]
then
        echo "Flag [ $flagBoilerBoxScriptImportBinaires ] trouvé : Suppression du flag" >> $fichierlog
        rm $flagBoilerBoxScriptImportBinaires
fi
if  [ -e "$flagBoilerBoxDownloadFtp" ]
then
        echo "Flag [ $flagBoilerBoxDownloadFtp ] trouvé : Suppression du flag" >> $fichierlog
        rm $flagBoilerBoxDownloadFtp
fi
if  [ -e "$flagBoilerBoxArretServeur" ]
then
        echo "Flag [ $flagBoilerBoxArretServeur ] trouvé : Suppression du flag" >> $fichierlog
        rm $flagBoilerBoxArretServeur
fi
exit 0
