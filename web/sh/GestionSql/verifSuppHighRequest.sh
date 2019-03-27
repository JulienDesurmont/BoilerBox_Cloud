#!/bin/sh

log=${BOILERBOX}/web/logs/cronlog.log

processusInterrompSql=`ps -ef | grep "${BOILERBOX}/web/sh/GestionSql/suppHighRequest.sh" | grep -v grep | wc -l`
if [ $processusInterrompSql -eq 0 ]
then
    ${BOILERBOX}/web/sh/GestionSql/suppHighRequest.sh &
fi


processusDemandeArretRelance=`ps -ef | grep "${BOILERBOX}/web/sh/GestionSql/restartSuppHighRequest.sh" | grep -v grep | wc -l`
if [ $processusDemandeArretRelance -eq 0 ]
then
    ${BOILERBOX}/web/sh/GestionSql/restartSuppHighRequest.sh &
fi
