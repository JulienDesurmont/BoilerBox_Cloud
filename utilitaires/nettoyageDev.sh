#!/bin/bash

version='BoilerBox_2.4.0'

rm -rf ${BOILERBOX}/../$version/web/logs
rm -rf ${BOILERBOX}/../$version/web/uploads
rm -rf ${BOILERBOX}/../$version/web/etats
rm -rf ${BOILERBOX}/../$version/web/moral

rm -rf ${BOILERBOX}/../$version/app/cache/*
rm ${BOILERBOX}/../$version/app/logs/*

rm -rf ${BOILERBOX}/../$version/app/config/parameters.*
rm -rf ${BOILERBOX}/../$version/web/config_ipc.txt





chmod 444 ${BOILERBOX}/../$version/utilitaires/nettoyageDev.sh
