#!/bin/bash

if test -z "$1"
then
	echo "Aucun argument défini : Argument par défaut selectionné : 'all'"
	echo "pi : Liste des arguments possible : categorie, typeFamille, enTeteLive,  icone, all"
	argument="all"
else
	argument=$1
fi

if [ $argument = "categorie" ] || [ $argument = "all" ]
then
	# INSERTION DES DONNEES DES CATEGORIES
	declare -A tabCategoriesLive
	tabCategoriesLive["Général"]="Général;Toutes les sécurités, nombre d'alarmes, défauts, événement.;#0068D0;general"
	tabCategoriesLive["Brûleur"]="Brûleur;Tous les actionneurs en lien  avec le brûleur, sauf combustible et air comburant.;#FF9900;bruleur"
	tabCategoriesLive["Chaudière"]="Chaudière;Tout ce qui ne rentrerai pas dans les autres catégories et qui concerne la chaudière.;#F25C05;chaudiere"
	tabCategoriesLive["Surchauffeur"]="Surchauffeur;Informations du surchauffeur, charge brûleur surchauffeur.;#A50021;surchauffeur"
	tabCategoriesLive["Bâche"]="Bâche;Toutes les informations liées à la bâche alimentaire, niveau, pression, température, eau.;#548DFF;bache"
	tabCategoriesLive["Automate_des_communs"]="Automate des communs;Equipements sur l'automate des communs, ballon d'éclatement, vase.;#00CC99;automate"
	tabCategoriesLive["Purges"]="Purges;Conductivité, purges, chaudière, condensats.;#05666B;purges"
	tabCategoriesLive["Fumées"]="Fumées;Toutes les informations concernant les fumées. Température, 02, Nox etc.;#000000;fumees"
	tabCategoriesLive["Vapeur"]="Vapeur;Vapeur, vapeur surchauffée, débit vapeur.;#FF0000;vapeur"
	tabCategoriesLive["Air_comburant"]="Air comburant;Température, débit air.;#69A73E;air"
	tabCategoriesLive["Combustible"]="Combustible;Gaz, fuel, autre combustible.;#6F2E9C;combustible"
	tabCategoriesLive["Réseau"]="Réseau;Débit réseau, température, réseau.;#CC0099;reseau"
	tabCategoriesLive["Eau"]="Eau;Niveau d'eau chaudière, température départ-retour, débit.;#00B0F0;eau"
	for var in ${!tabCategoriesLive[*]} ; do
		designation=`echo ${tabCategoriesLive[${var}]} | cut -d ';' -f1`
		informations=`echo ${tabCategoriesLive[${var}]} | cut -d ';' -f2`
		couleur=`echo ${tabCategoriesLive[${var}]} | cut -d ';' -f3`
		classe=`echo ${tabCategoriesLive[${var}]} | cut -d ';' -f4`
		# Création du fichier de vérification de la table categoreFamilleLive -> Permet de vérifier si la catègorie n'existe pas déjà
		cat ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/check_table_categorieFamilleLive.sql | sed -e "s/%designation%/${designation}/" > ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/tmp_sed_table.sql
		# Recherche et récupération de l'identifiant de la catégorie
		idCategorie=`mysql -u cargo --password=adm5667 < ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/tmp_sed_table.sql`
		# Si la catégorie n'existe pas création de celle-ci
		if [ "$idCategorie" == "" ]
		then
			cat ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/miseEnPlace_table_categorieFamilleLive.sql | sed -e "s/%designation%/${designation}/" | sed -e "s/%informations%/${informations}/" | sed -e "s/%couleur%/${couleur}/" | sed -e "s/%classe%/${classe}/" > ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/tmp_sed_table.sql
			echo "Insertion de la catégorie ["${var}"]"
			mysql -u cargo --password=adm5667 < ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/tmp_sed_table.sql
		else
			idCategorie=`echo ${idCategorie} | awk '{print $2}'`
			cat ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/update_table_categorieFamilleLive.sql | sed -e "s/%identifiant%/${idCategorie}/" |  sed -e "s/%informations%/${informations}/" | sed -e "s/%couleur%/${couleur}/" | sed -e "s/%classe%/${classe}/" > ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/tmp_sed_table.sql
			echo "Mise à jour de la catégorie ["${var}"]"
			mysql -u cargo --password=adm5667 < ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/tmp_sed_table.sql
		fi 
	done
fi

if [ $argument = "icone" ] || [ $argument = "all" ]
then
	# INSERTION DES DONNEES DES ICONES
	declare -A tabIcones
	tabIcones["Alarme"]="Alarme;bundles\/ipcsupervision\/svg\/tu\/tuiles-alarme.svg;Al"
	tabIcones["Automate_Chaufferie"]="Automate Chaufferie;url Déclinaison du logo accueil;Ac"
	tabIcones["Bruleur"]="Bruleur;bundles\/ipcsupervision\/svg\/tu\/tuiles-bruleur.svg;Br"
	tabIcones["Conductivité"]="Conductivité;url Eclair;Co"
	tabIcones["Consigne_locale_glissante"]="Consigne locale glissante;bundles\/ipcsupervision\/svg\/tu\/tuiles-consigne-locale.svg;Cg"
	tabIcones["Consigne_distante"]="Consigne distante;bundles\/ipcsupervision\/svg\/tu\/tuiles-consigne-distante.svg;Cd"
	tabIcones["Compteur"]="Compteur;bundles\/ipcsupervision\/svg\/tu\/tuiles-compteur.svg;Co"
	tabIcones["Défaut"]="Défaut;bundles\/ipcsupervision\/svg\/tu\/tuiles-defaut.svg;De"
	tabIcones["Débit"]="Débit;bundles\/ipcsupervision\/svg\/tu\/tuiles-debit.svg;De"
	tabIcones["Energie"]="Energie\/Puissance;bundles\/ipcsupervision\/svg\/tu\/tuiles-puissance.svg;En"
	tabIcones["Echangeur_Thermique"]="Echangeur Thermique;url Echangeur Thermique;Ec"
	tabIcones["Evenement"]="Evenement;bundles\/ipcsupervision\/svg\/tu\/tuiles-evenement.svg;Ev"
	tabIcones["Flamme_combustion"]="Flamme combustion;url Flamme Combustion;Fc"
	tabIcones["Fumées"]="Fumées;url Fumées;Fu"
	tabIcones["Générateur"]="Générateur;url Logo accueil;Ge"
	tabIcones["Information_TOR"]="Information TOR;url Icone TOR;It"
	tabIcones["Information_analogique"]="Information analogique;url Vague;Ia"
	tabIcones["Niveau"]="Niveau;bundles\/ipcsupervision\/svg\/tu\/tuiles-niveau.svg;Nv"
	tabIcones["O2"]="O2;bundles\/ipcsupervision\/svg\/tu\/tuiles-O2.svg;O2"
	tabIcones["Pompe"]="Pompe;bundles\/ipcsupervision\/svg\/tu\/tuiles-pompe.svg;Po"
	tabIcones["Pression"]="Pression;bundles\/ipcsupervision\/svg\/tu\/tuiles-pression.svg;Pr"
	tabIcones["Réglage"]="Réglage;url Roue dentée;Re"
	tabIcones["Sécurité"]="Sécurité;bundles\/ipcsupervision\/svg\/tu\/tuiles-securite.svg;Se"
	tabIcones["Température"]="Température;bundles\/ipcsupervision\/svg\/tu\/tuiles-thermometre.svg;Tp"
	tabIcones["Temporisation"]="Temporisation;url Chronomètre;Te"
	tabIcones["Volet"]="Volet;url Volet;Vo"
	tabIcones["Vanne"]="Vanne;bundles\/ipcsupervision\/svg\/tu\/tuiles-vanne.svg;Va"
	tabIcones["Ventilateur"]="Ventilateur;url Ventilateur;Ve"
	for var in ${!tabIcones[*]} ; do
		designation=`echo ${tabIcones[${var}]} | cut -d ';' -f1`
		url=`echo ${tabIcones[${var}]} | cut -d ';' -f2`
		alt=`echo ${tabIcones[${var}]} | cut -d ';' -f3`
		# Création du fichier de vérification de la table icone -> Permet de vérifier si l'icone n'existe pas déjà
		cat ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/check_table_icone.sql | sed -e "s/%designation%/${designation}/" > ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/tmp_sed_table.sql
		# Recherche et récupération de l'identifiant de l'icone
		idIcone=`mysql -u cargo --password=adm5667 < ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/tmp_sed_table.sql`
		# Si l'icone n'existe pas création de celui-ci
		if [ "$idIcone" == "" ]
		then
			cat ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/miseEnPlace_table_icone.sql | sed -e "s/%designation%/${designation}/" | sed -e "s/%url%/${url}/" | sed -e "s/%alt%/${alt}/" > ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/tmp_sed_table.sql
			echo "Insertion de l'icone ["${var}"]"
			mysql -u cargo --password=adm5667 < ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/tmp_sed_table.sql
		else
			idIcone=`echo ${idIcone} | awk '{print $2}'`
			cat ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/update_table_icone.sql | sed -e "s/%designation%/${designation}/;s/%url%/${url}/;s/%alt%/${alt}/;s/%identifiant%/${idIcone}/" > ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/tmp_sed_table.sql
			echo "Mise à jour de l'icone ["${var}"]"
			mysql -u cargo --password=adm5667 < ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/tmp_sed_table.sql
		fi
	done
fi

if [ $argument = "typeFamille" ] || [ $argument = "all" ]
then
	# INSERTION DES DONNEES DES TYPES DE FAMILLE
	declare -A tabTypeFamilleLive
	tabTypeFamilleLive["Generateur"]="enTeteGenerateur;générateur;1"
	tabTypeFamilleLive["Bruleur"]="enTeteBruleur;bruleur;2"
	tabTypeFamilleLive["Niveau"]="enTeteNiveau;niveau;3"
	tabTypeFamilleLive["TemperatureRetour"]="enTeteTemperatureRetour;température;3"
	tabTypeFamilleLive["Pression"]="enTetePression;pression;4"
	tabTypeFamilleLive["TemperatureDepart"]="enTeteTemperatureDepart;température;4"
	tabTypeFamilleLive["TemperatureBache"]="enTeteTemperatureBache;température;4"
	tabTypeFamilleLive["Etat"]="enTeteEtat;état;5"
	tabTypeFamilleLive["Combustible"]="enTeteCombustible;combustible;6"
	tabTypeFamilleLive["Conductivite"]="enTeteConductivite;conductivite;7"
	tabTypeFamilleLive["DebitVapeur"]="enTeteDebitVapeur;débit;8"
	tabTypeFamilleLive["DebitReseau"]="enTeteDebitReseau;débit;8"
	tabTypeFamilleLive["Divers"]="divers;divers;9"
	for var in ${!tabTypeFamilleLive[*]} ; do
		designation=`echo ${tabTypeFamilleLive[${var}]} | cut -d ';' -f1`
		informations=`echo ${tabTypeFamilleLive[${var}]} | cut -d ';' -f2`
		disposition=`echo ${tabTypeFamilleLive[${var}]} | cut -d ';' -f3`
		# Création du fichier de vérification de la table typeFamilleLive -> Permet de vérifier si le type de la famille n'existe pas déjà
		cat ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/check_table_typeFamilleLive.sql | sed -e "s/%designation%/${designation}/" > ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/tmp_sed_table.sql
		# Recherche et récupération de l'identifiant de l'icone
		idTypeFamille=`mysql -u cargo --password=adm5667 < ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/tmp_sed_table.sql`
		# Si le type de la famille n'existe pas création de celui-ci
		if [ "$idTypeFamille" == "" ]
		then
			cat ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/miseEnPlace_table_typeFamilleLive.sql | sed -e "s/%designation%/${designation}/" | sed -e "s/%informations%/${informations}/" | sed -e "s/%disposition%/${disposition}/" > ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/tmp_sed_table.sql
			echo "Insertion du type de famille ["${var}"]"
			mysql -u cargo --password=adm5667 < ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/tmp_sed_table.sql
		else
			idTypeFamille=`echo ${idTypeFamille} | awk '{print $2}'`
			cat ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/update_table_typeFamilleLive.sql | sed -e "s/%designation%/${designation}/;s/%informations%/${informations}/;s/%disposition%/${disposition}/;s/%identifiant%/${idTypeFamille}/" > ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/tmp_sed_table.sql
			echo "Mise à jour du type de famille ["${var}"]"
			mysql -u cargo --password=adm5667 < ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/tmp_sed_table.sql
		fi
	done
fi

if [ $argument = "enTeteLive" ] || [ $argument = "all" ]
then
	# INSERTION DES DONNEES DES TYPES DE GENERATEURS
	declare -A tabEnTeteLive
	tabEnTeteLive["Bruleur"]="bruleur;Marche brûleur;enTeteBruleur"
	tabEnTeteLive["Combustible"]="combustible;Combustible choisi;enTeteCombustible"
	tabEnTeteLive["ConductiviteCh"]="conductiviteCh;Conductivité chaudière;enTeteConductivité"
	tabEnTeteLive["DebitVapeur"]="debitVapeur;Débit vapeur;enTeteDebitVapeur"
	tabEnTeteLive["DebitReseau"]="debitReseau;Débit réseau;enTeteDebitReseau"
	tabEnTeteLive["EtatGen"]="etatGen;Etat du générateur;enTeteEtat"
	tabEnTeteLive["ExploitationGen"]="exploitationGen;Mode d'exploitation;enTeteGenerateur"
	tabEnTeteLive["Niveau"]="niveau;Niveau;enTeteNiveau"
	tabEnTeteLive["Niveau"]="NiveauEau;Niveau d'eau;enTeteNiveau"
	tabEnTeteLive["Pression"]="pression;Pression;enTetePression"
	tabEnTeteLive["Tuile"]="tuile;Tuile;tuile"
	tabEnTeteLive["TemperatureDepart"]="temperatureDepart;Température de départ;enTeteTemperatureDepart"
	tabEnTeteLive["TemperatureRetour"]="temperatureRetour;Température de retour;enTeteTemperatureRetour"
	tabEnTeteLive["TemperatureBache"]="temperatureBache;Température de la bâche;enTeteTemperatureBache"
	for var in ${!tabEnTeteLive[*]} ; do
		designation=`echo ${tabEnTeteLive[${var}]} | cut -d ';' -f1`
		description=`echo ${tabEnTeteLive[${var}]} | cut -d ';' -f2`
		designationFamille=`echo ${tabEnTeteLive[${var}]} | cut -d ';' -f3`
		# Création du fichier de vérification de la table enTeteLive -> Permet de vérifier si l'entête live n'existe pas déjà
		cat ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/check_table_moduleEnTeteLive.sql | sed -e "s/%designation%/${designation}/" > ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/tmp_sed_table.sql
		# Recherche et récupération de l'identifiant de l'en-tête
		idEnTeteLive=`mysql -u cargo --password=adm5667 < ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/tmp_sed_table.sql`
		# Recherche et récupération de l'identifiant de la famille associée à l'en-tête
		cat ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/check_table_familleModuleEnTeteLive.sql | sed -e "s/%designationFamille%/${designationFamille}/" > ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/tmp_sed_table.sql
		idFamille=`mysql -u cargo --password=adm5667 < ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/tmp_sed_table.sql | tail -1`
		# Si le type de l'en tête live n'existe pas création de celui-ci
		if [ "$idEnTeteLive" == "" ]
		then
			if [ "$idFamille" != "" ]
			then
				cat ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/miseEnPlace_table_moduleEnTeteLive.sql | sed -e "s/%designation%/${designation}/" | sed -e "s/%description%/${description}/" | sed -e "s/%idFamille%/${idFamille}/"  > ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/tmp_sed_table.sql
				echo "Insertion des en têtes live ["${var}"]"
				mysql -u cargo --password=adm5667 < ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/tmp_sed_table.sql
			fi
		else
			idEnTeteLive=`echo ${idEnTeteLive} | awk '{print $2}'`
			if [ "$idFamille" != "" ]
			then
				cat ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/update_table_moduleEnTeteLive.sql | sed -e "s/%designation%/${designation}/" | sed -e "s/%description%/${description}/" | sed -e "s/%idFamille%/${idFamille}/" | sed -e "s/%identifiant%/${idEnTeteLive}/" > ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/tmp_sed_table.sql
				echo "Mise à jour de la table t_moduleEnteteLive : ["${var}"]"
				mysql -u cargo --password=adm5667 < ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/tmp_sed_table.sql
			fi
		fi
	done
fi

if [ $argument = "typeGenerateur" ] || [ $argument = "all" ]
then
	# INSERTION DES DONNEES DES TYPES DE GENERATEURS
	declare -A tabTypeGenerateur
	tabTypeGenerateur["VP"]="VP;Chaudière Vapeur"
	tabTypeGenerateur["ES"]="ES;Eau surchauffée"
	tabTypeGenerateur["SU"]="SU;Surchauffeur"
	tabTypeGenerateur["AC"]="AC;Automate des communs"
	for var in ${!tabTypeGenerateur[*]} ; do
		mode=`echo ${tabTypeGenerateur[${var}]} | cut -d ';' -f1`
		description=`echo ${tabTypeGenerateur[${var}]} | cut -d ';' -f2`
		# Création du fichier de vérification de la table type Générateur -> Permet de vérifier si le type du générateur n'existe pas déjà
		cat ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/check_table_typeGenerateur.sql | sed -e "s/%mode%/${mode}/" > ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/tmp_sed_table.sql
		# Recherche et récupération de l'identifiant du type du générateur
		idTypeGenerateur=`mysql -u cargo --password=adm5667 < ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/tmp_sed_table.sql`
		# Si le type du générateur n'existe pas création de celui-ci
		if [ "$idTypeGenerateur" == "" ]
		then
			cat ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/miseEnPlace_table_typeGenerateur.sql | sed -e "s/%mode%/${mode}/" | sed -e "s/%description%/${description}/" > ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/tmp_sed_table.sql
			echo "Insertion des types de générateurs ["${var}"]"
			mysql -u cargo --password=adm5667 < ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/tmp_sed_table.sql
		fi
	done
fi
