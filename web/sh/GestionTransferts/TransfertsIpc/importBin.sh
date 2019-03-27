#!/bin/sh
# /web/sh/GestionTransferts/TransfertsIpc/importBin.sh
# Importation des fichiers Binaires


if [ $# != 0 ]; then
    systemLog=$1/web/logs/system.log
    BOILERBOX=$1
else
    systemLog=${BOILERBOX}/web/logs/system.log
fi



flagShImportBinaire='/tmp/.flagSymfonyScriptImportBinaires'
flagCmdImportBinaire='/tmp/.flagSymfonyImportBinaires'
nbScriptImport=$(ps -ef | grep "php.*BoilerBox.*import.*bin" | grep -v grep | wc -l)
# Vérification qu'un flag d'importation de fichiers binaires n'existe pas
if [ -e "$flagShImportBinaire" ] || [ -e "$flagCmdImportBinaire" ]
then
        if [ $nbScriptImport -eq 1 ]
        then
                echo `date +"%Y-%m-%d %T"`";[importBin.sh] L'importation de fichiers binaires est déjà en cours" >> $systemLog
                exit 1
        fi
        if [ $nbScriptImport -eq 0 ]
        then
                echo `date +"%Y-%m-%d %T"`";[importBin.sh] Flag d'importation binaire trouvé mais aucun script en cours d'execution" >> $systemLog
                if [ -e "$flagShImportBinaire" ]
                then
                        echo ";[importBin.sh] Suppression du flag $flagShImportBinaire" >> $systemLog
                        rm $flagShImportBinaire
                fi
                if [ -e "$flagCmdImportBinaire" ]
                then
                        echo ";[importBin.sh] Suppression du flag $flagCmdImportBinaire" >> $systemLog
                        rm $flagCmdImportBinaire
                fi
        fi
fi
# Création du flag
touch $flagShImportBinaire
chmod 666 $flagShImportBinaire
chown wwwrun $flagShImportBinaire
# Appel de la commande qui importe en base la liste des fichiers présents dans le dossier fichiers_binaires
retour=`nice -0 php ${BOILERBOX}/app/console import:bin`
## Si des fichiers sont déjà en cours d'importation on termine la boucle de ce script
if [[ $retour == 1 ]]
then
        echo `date +"%Y-%m-%d %T"`";[importBin.sh] Fichiers en cours d'importation:Fin du script" >> $systemLog
        break
fi
# Libération du flag
rm $flagShImportBinaire
exit 0
