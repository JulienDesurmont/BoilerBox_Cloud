#!/bin/sh

fichierlog=${BOILERBOX}/web/logs/system.log

# Log de l'arrêt demandé du sserveur
echo `date +"%Y-%m-%d %T"`";Relance du serveur Apache demandé" >> $fichierlog

# Attente de la fin des importations / création des fichiers binaires
#echo "Relance Apache"
# Vérification qu'aucun script BoilerBox n'est en cours d'execution
nbProcessBoilerBox=$(ps -ef | grep "BoilerBox" | grep -v "uppHighRequest" | grep -v "relanceApache.sh" | grep -v "grep" | wc -l)
#echo "Nb de process : $nbProcessBoilerBox"
#ps -ef | grep "BoilerBox" | grep -v "uppHighRequest" | grep -v "grep"
if [ $nbProcessBoilerBox -eq 0 ]
then
        # Vérification qu'aucun flag symfony n'est présent : Indique que tous les scripts sont bien terminés
        nbFlagsBoilerBox=$(ls /tmp/.flag* | grep "BoilerBox" | grep -v "/tmp/.flagBoilerBoxSessionLive" | wc -l)
        #echo "Nombre de flags : $nbFlagsBoilerBox"
        if  [ $nbFlagsBoilerBox -eq 0 ]
        then
                # Relance d'Apache
                /etc/init.d/apache2 restart
                # Log
                echo `date +"%Y-%m-%d %T"`";Relance du serveur Apache effectuée" >> $fichierlog
                echo `date +"%Y-%m-%d %T"`";Relance du serveur Apache effectuée"
        else
                echo `date +"%Y-%m-%d %T"`";Relance du serveur Apache non effectuée;Nombre de flags:$nbFlagsBoilerBox" >> $fichierlog
                echo `date +"%Y-%m-%d %T"`";Relance du serveur Apache non effectuée;Nombre de flags:$nbFlagsBoilerBox"
        fi
else
        echo `date +"%Y-%m-%d %T"`";Relance du serveur Apache non effectuée;Nombre de processus BoilerBox=$nbProcessBoilerBox" >> $fichierlog
        echo `date +"%Y-%m-%d %T"`";Relance du serveur Apache non effectuée;Nombre de processus BoilerBox=$nbProcessBoilerBox"
fi
exit 0
