#!/bin/sh
if test -z "$1"
then
    echo "Veuillez indiquer le nom du dossier BoilerBox"
	exit 1
fi

dossier=$1

# Suppression des fichiers spécifiques à un site
rm -rf ${BOILERBOX}/../$dossier/web/uploads/csv/bddipc/*
rm -rf ${BOILERBOX}/../$dossier/web/uploads/fichiers_binaires/*
rm -rf ${BOILERBOX}/../$dossier/web/uploads/fichiers_errors/*
rm -rf ${BOILERBOX}/../$dossier/web/uploads/fichiers_origines/*
rm -rf ${BOILERBOX}/../$dossier/web/uploads/fichiers_tmpencours/*
rm -rf ${BOILERBOX}/../$dossier/web/uploads/fichiers_tmpftp/*
rm -rf ${BOILERBOX}/../$dossier/web/uploads/fichiers_traites/*
rm -rf ${BOILERBOX}/../$dossier/web/uploads/interventions/*
rm -rf ${BOILERBOX}/../$dossier/web/uploads/requetes/graphique/*
rm -rf ${BOILERBOX}/../$dossier/web/uploads/requetes/listing/*
rm -rf ${BOILERBOX}/../$dossier/app/cache/*
rm -rf ${BOILERBOX}/../$dossier/app/logs/*
rm -rf ${BOILERBOX}/../$dossier/web/logs/backup/*
rm -rf ${BOILERBOX}/../$dossier/web/etats/*
rm -rf ${BOILERBOX}/../$dossier/.git
rm ${BOILERBOX}/../$dossier/.gitignore
find ${BOILERBOX}/../$dossier/web/logs/ -type f -exec rm {} \;

