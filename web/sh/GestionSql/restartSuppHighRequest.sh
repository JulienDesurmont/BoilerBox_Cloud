#!/bin/sh

log=${BOILERBOX}/web/logs/cronlog.log

# Relance les processus si ceux-ci ne tournent plus
# Vérifie si une demande de relance du processus est effectuée : Demande faite par execution du script arretRelanceSuppHighRequest.sh

while true
do
	demandeArretRelance=`ps -ef | grep "${BOILERBOX}/web/sh/GestionSql/arretRelanceSuppHighRequest.sh" | grep -v grep | wc -l`
	if [ $demandeArretRelance -gt 0 ]
	then
		# Script qui interrompt les requêtes trop longues à l'execution
		processusInterrompSql=`ps -ef | grep "${BOILERBOX}/web/sh/GestionSql/suppHighRequest.sh" | grep -v grep | wc -l`
		if [ $processusInterrompSql -gt 0 ]
		then
			processusId=`ps -ef | grep "${BOILERBOX}/web/sh/GestionSql/suppHighRequest.sh" | grep -v grep | awk '{print $2}'`
			# Kill du processus
			date=`date "+%Y-%m-%d %H:%M:%S"`
			echo -e "Relance du processus suppHighRequest.sh le $date" >> $log
			kill -9 $processusId
        	${BOILERBOX}/web/sh/GestionSql/suppHighRequest.sh &
			sleep 1
		fi
	fi
done
