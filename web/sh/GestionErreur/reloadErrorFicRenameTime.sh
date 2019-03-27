#!/bin/bash

# script qui renomme les fichiers du dossier des fichiers en erreur à rejouer : /fichiers_errors/reloads et les déplace dans le dossiers des fichiers d'origines /fichiers_origines/
for file in ${BOILERBOX}/web/uploads/tmp/*; do
	numeroAmodifier=$(echo $file|cut -d '-' -f3|cut -d '.' -f1)
	nouveauNumero=$(($numeroAmodifier + 1))
	echo "Numero " $numeroAmodifier " vs " $nouveauNumero
	nouveauNom=$(echo $file|sed "s/..\.lci/$nouveauNumero.lci/")
	echo $nouveauNom
	#mv $file $nouveaunom
done
