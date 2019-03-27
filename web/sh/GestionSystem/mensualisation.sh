#!/bin/sh
# Script qui analyse les fichiers d'un dossier de fichier lci : nommage Affaire_localisation_YYmmdd_HH-ii-ss.lci
# pour les fichiers n'étant pas du mois courant, il crée et déplace ces fichiers dans un dossier ayant comme nommage le mois et l'année du fichier

# mensualisation.sh ${BOILERBOX}/web/uploads/fichiers_traites

if [ $# != 1 ]
then
	echo "Appel du script : ./mensualisation.sh dossierAAnalyser"
	echo "exemple : ./mensualisation.sh ${BOILERBOX}/web/uploads/fichiers_traites"
	exit 1
fi

log=${BOILERBOX}/web/logs/system.log

dossier_analyse=$1
mois_courant=`date "+%y%m"`	#1402
annee=`date "+%Y"`		#2014
annee=${annee:0:2}		#20

for fichier_source in `find $dossier_analyse -maxdepth 1 -type f -name '*.lci'`;do
	# Récupération du nom du fichier
	fichier=${fichier_source##*/}
	echo "fichier : $fichier"

	# On récupère le mois du fichier 
	mois_du_fichier=${fichier%_*}		# C649_01_140101
	mois_du_fichier=${mois_du_fichier##*_}	# 131231
	mois_du_fichier=${mois_du_fichier:0:4}  # 1312
	annee_du_fichier=${mois_du_fichier:0:2}	# 13

	# Si le fichier est d'un mois diffèrent du mois courant
	if [ "$mois_du_fichier" != "$mois_courant" ]
	then
	        # mois du fichier ~ 1312' => Recherche du dossier 122013
	        dossier_mensualisation=${mois_du_fichier:2:2}${annee}${annee_du_fichier}

		# On regarde si un dossier de mensualité existe pour le mois du fichier (dossier de type 012014 MMYYYY)
		dossier_mensuel=`find $dossier_analyse -maxdepth 1 -type d -name $dossier_mensualisation | wc -l`

		# Si le dossier du mois du fichier n'existe pas : Création de celui-ci
		if [ $dossier_mensuel == 0 ]
		then
			mkdir ${dossier_analyse}/${dossier_mensualisation}	
		fi
		#Déplacement du fichier dans le dossier mensuel
		#mv $fichier_source ${dossier_analyse}/${dossier_mensualisation}/$fichier
		echo "copie cp -p $fichier_source ${dossier_analyse}/${dossier_mensualisation}/$fichier"
		cp -p $fichier_source ${dossier_analyse}/${dossier_mensualisation}/$fichier
		# Comparaison des deux fichiers
		taille1=$(stat -c %s "$fichier_source")
		taille2=$(stat -c %s "${dossier_analyse}/${dossier_mensualisation}/$fichier")
		echo "compare $taille1 et $taille2"
		# Si les tailles sont identiques, suppression du fichier original
		if [ $taille1 -eq $taille2 ]
		then
			rm -f $fichier_source
			echo "suppression du fichier $fichier_source"
		else
			echo "Le fichier $fichier_source a une taille différente du fichier ${dossier_analyse}/${dossier_mensualisation}/$fichier. Veuillez vérifier svp"
		fi
	fi
done
