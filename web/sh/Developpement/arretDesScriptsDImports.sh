#!/bin/sh

log=${BOILERBOX}/web/logs/cronlog.log
sytemLog=${BOILERBOX}/web/logs/system.log

# Arrêt du processus de conversion en binaire
processusConversion=`ps -ef | grep "${BOILERBOX}/web/sh/GestionTransferts/TransfertsIpc/creationBin.sh" | grep -v grep | wc -l`
if [ $processusConversion -ne 0 ]
then
	processusinum=`ps -ef | grep "${BOILERBOX}/web/sh/GestionTransferts/TransfertsIpc/creationBin.sh" | grep -v grep | awk -F" " '{print $2}'`
	echo `date +"%Y-%m-%d %T"`";Arret du processus creationBin.sh "$processusinum >> $sytemLog
	kill -9 $processusinum 1>>$sytemLog 2>&1
fi

# Arrêt du processus d'import en base
processusImportBin=`ps -ef | grep "${BOILERBOX}/web/sh/GestionTransferts/TransfertsIpc/importBin.sh" | grep -v grep | wc -l`
if [ $processusImportBin -ne 0 ]
then
	processusinum=`ps -ef | grep "${BOILERBOX}/web/sh/GestionTransferts/TransfertsIpc/importBin.sh" | grep -v grep | awk -F" " '{print $2}'`
	echo `date +"%Y-%m-%d %T"`";Arret du processus importBin.sh "$processusinum >> $sytemLog
	kill -9 $processusinum 1>>$sytemLog 2>&1
fi


# Arrêt des processus de kill des requêtes
processusVerifSql=`ps -ef | grep "${BOILERBOX}/web/sh/GestionSql/suppHighRequest.sh" | grep -v grep | wc -l`
if [ $processusVerifSql -ne 0 ]
then
	processusinum=`ps -ef | grep "${BOILERBOX}/web/sh/GestionSql/suppHighRequest.sh" | grep -v grep | awk -F" " '{print $2}'`
	echo `date +"%Y-%m-%d %T"`";Arret du processus suppHighRequest.sh "$processusinum >> $sytemLog
        kill -9 $processusinum 1>>$sytemLog 2>&1
fi

processusRestartVerifSql=`ps -ef | grep "${BOILERBOX}/web/sh/GestionSql/restartSuppHighRequest.sh" | grep -v grep | wc -l`
if [ $processusRestartVerifSql -ne 0 ]
then
        processusinum=`ps -ef | grep "${BOILERBOX}/web/sh/GestionSql/restartSuppHighRequest.sh" | grep -v grep | awk -F" " '{print $2}'`
        echo `date +"%Y-%m-%d %T"`";Arret du processus restartSuppHighRequest : "$processusinum >> $sytemLog
        kill -9 $processusinum 1>>$sytemLog 2>&1
fi

processusTransfertFtp=`ps -ef | grep "${BOILERBOX}/web/sh/GestionTransferts/TransfertsFtp/transfertFtp.sh" | grep -v grep | wc -l`
if [ $processusTransfertFtp -ne 0 ]
then
        processusinum=`ps -ef | grep "${BOILERBOX}/web/sh/GestionTransferts/TransfertsFtp/transfertFtp.sh" | grep -v grep | awk -F" " '{print $2}'`
        echo `date +"%Y-%m-%d %T"`";Arret du processus transfertFtp : "$processusinum >> $sytemLog
        kill -9 $processusinum 1>>$sytemLog 2>&1
fi

