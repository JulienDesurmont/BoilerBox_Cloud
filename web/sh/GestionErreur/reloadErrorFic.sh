#!/bin/bash

# script qui renomme les fichiers du dossier des fichiers en erreur à rejouer : /fichiers_errors/reloads et les déplace dans le dossiers des fichiers d'origines /fichiers_origines/
for file in ${BOILERBOX}/web/uploads/fichiers_errors/reloads/*; do
	nouveaunom=$(echo $file|sed 's/\.error//'|sed 's/fichiers_errors\/reloads/fichiers_origines/')
	mv $file $nouveaunom
done
