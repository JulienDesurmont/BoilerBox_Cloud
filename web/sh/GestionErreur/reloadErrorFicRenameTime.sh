#!/bin/bash

# script qui renomme les fichiers du dossier des fichiers en erreur à rejouer : /fichiers_errors/reloads et les déplace dans le dossiers des fichiers d'origines /fichiers_origines/
for file in ${BOILERBOX}/web/uploads/tmp/*; do
	numeroAmodifier=$(echo $file|cut -d '-' -f3|cut -d '.' -f1)
	echo $numeroAmodifier
	nouveauNumero=$((10#$numeroAmodifier + 1))
	nb=$(echo $nouveauNumero | wc -c)
	echo $nb
	if [ $nb -eq 2 ]
	then
		nouveauNumero="0"$nouveauNumero
	fi
	echo "Numero " $numeroAmodifier " vs " $nouveauNumero
	echo $file
	nouveauNom=$(echo $file|sed "s/..\.lci/$nouveauNumero.lci/"|sed "s/\/tmp\//\/tmp\/fichiersModifies\//")
	echo $nouveauNom
	mv $file $nouveauNom
done

