#!/bin/bash

version='BoilerBox_2.4.0'

rm -rf ${BOILERBOX}/../$version/web/highstock
rm -rf ${BOILERBOX}/../$version/vendor
rm -rf ${BOILERBOX}/../$version/utilitaires/Symfony_boostrap
rm -rf ${BOILERBOX}/../$version/.git
rm -rf ${BOILERBOX}/../$version/.gitignore

chmod 444 ${BOILERBOX}/../$version/utilitaires/nettoyagePourVersionMin.sh
