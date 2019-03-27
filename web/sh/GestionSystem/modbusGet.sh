#!/bin/sh
PATH="$PATH:/usr/bin:/bin"

# Récupération des informations modbus.
flagGetInfosModbus='/tmp/.flagBoilerBoxGetInfosModbus'
flagSessionLive='/tmp/.flagBoilerBoxSessionLive'
sytemLog=${BOILERBOX}/web/logs/system.log
modbusLog=${BOILERBOX}/web/logs/modbus.log
nbScriptModbus=$(ps -ef | grep "php.*BoilerBox.*modbus.*get" | grep -v grep | wc -l)

if [ -e "$flagSessionLive" ]
then
	# Si un flag de session existe et si la date du flag est > à 1 minute, suppression du flag + Arrêt du traitement modbus
	dateEnCours=$(date +%s)
	dateFlag=$(cat $flagSessionLive)
	dateLimit=$(($dateFlag + 60))
	if [ $dateLimit -lt $dateEnCours ]
	then
		#echo "date dépassée $dateLimit < $dateEnCours" >> $sytemLog
	   	#echo "Suppression du flag $flagSessionLive" >> $sytemLog 
		rm $flagSessionLive	
		exit 0
	fi
else
	# Si aucun flag de session pas de traitement modbus
	#echo "Aucune session live détectée" >> $sytemLog
	exit 0
fi

# Si un flag de session existe est que sa création date de moins de 'dateLimit' secondes

#echo "Lancement de la récupération modbus" >> $sytemLog
# Vérification qu'un flag n'existe pas déjà.
if [ -e "$flagGetInfosModbus" ]
then
	if [ $nbScriptModbus -ge 1 ]
	then
		#echo `date +"%Y-%m-%d %T"`"[modbusGet.sh] La récupération des informations modbus est déjà en cours" >> $sytemLog
		exit 1
	fi
	if [ $nbScriptModbus -eq 0 ]
	then
		echo `date +"%Y-%m-%d %T"`"[modbusGet.sh] Flag de récupération des informations modbus en cours mais aucun script en cours d'execution" >> $sytemLog
		echo "Suppression du flag $flagGetInfosModbus" >> $sytemLog
		rm $flagGetInfosModbus
	fi
fi

# Création du flag.
touch $flagGetInfosModbus
chmod 666 $flagGetInfosModbus
chown wwwrun $flagGetInfosModbus
# Appel de la commande qui va récupérer les informations modbus et inscrire les valeur en base de données.
while true
do
	nbScriptModbus=$(ps -ef | grep "php.*BoilerBox.*modbus.*get" | grep -v grep | wc -l)
	if [ $nbScriptModbus -ge 1 ]
	then
		exit 1
	fi
	if [ -e "$flagSessionLive" ]
	then
		echo `date +"%Y-%m-%d %T"`";Début de recherche modbus" >> $modbusLog
		retour=`nice -0 php ${BOILERBOX}/app/console modbus:get`
		#"Si il n'y a aucun flag Live : Arrêt des recherches modbus"
		if [ ! -e "$flagSessionLive" ]
		then
			echo `date +"%Y-%m-%d %T"`";Aucun Live détecté : Arrêt des traitements modbus" >> $modbusLog
			rm $flagGetInfosModbus
			exit 0
		fi
	else
		#echo "Aucun flag de session detecté : Arrêt du traitement modbus" >> $sytemLog
		sleep 5
	fi
done
# Libération du flag.
rm $flagGetInfosModbus
exit 0
