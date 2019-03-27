#!/bin/bash
fichierlog=${BOILERBOX}/web/logs/dataErrorDGMNF.csv
# Nombre d'erreur de type DGMNF
nbErrors=$(echo "SELECT COUNT(*) FROM ipc.t_donneetmp WHERE erreur = 'DGMNF'" | mysql --skip-column-names -ucargo -padm5667)
echo "" > $fichierlog
echo "Nombre de data en erreur DGMNF : $nbErrors" >> $fichierlog

tailleTabModulesIncorrects=$(echo "SELECT COUNT(*) FROM (SELECT DISTINCT CONCAT(numero_genre, ' ', categorie, right('00' + numero_module, 2), right('00' + numero_message, 2), ' ', programme) FROM ipc.t_donneetmp WHERE erreur = 'DGMNF') as t1" | mysql --skip-column-names -ucargo -padm5667)
echo "Nombre de modules incorrects $tailleTabModulesIncorrects" >> $fichierlog
last_offset=$(echo "$tailleTabModulesIncorrects - 1" | /usr/bin/bc)
# Titre des colonnes
echo "Module erreur;Genre erreur;Programme erreur;Genre base" >> $fichierlog
# Pour chaque module, recherche du module et du genre associé en base de donnée
for i in `seq 0 $last_offset`
do
	tabModulesIncorrects=($(echo "SELECT DISTINCT CONCAT(numero_genre, ' ', categorie, ' ', numero_module, ' ', numero_message, ' ', programme) FROM ipc.t_donneetmp WHERE erreur = 'DGMNF' LIMIT 1 OFFSET $i" | mysql --skip-column-names -ucargo -padm5667))
	numero_genre_donnee=$(echo ${tabModulesIncorrects[0]})
	categorie_donnee=$(echo ${tabModulesIncorrects[1]})
	module_donnee=`printf "%02d\n" ${tabModulesIncorrects[2]}`
	message_donnee=`printf "%02d\n" ${tabModulesIncorrects[3]}`
	programme_donnee=$(echo ${tabModulesIncorrects[4]})
	genre_donnee=$(echo "SELECT CONCAT(numero_genre, '_', intitule_genre) as genre FROM ipc.t_genre WHERE numero_genre = $numero_genre_donnee" | mysql --skip-column-names -ucargo -padm5667)
	echo "Analyse du module ${categorie_donnee}${module_donnee}${message_donnee}";
	# Recherche de l'identifiant du programme
	id_programme=$(echo "SELECT id FROM ipc.t_mode WHERE designation = '$programme_donnee'" | mysql --skip-column-names -ucargo -padm5667)
	# Recherche du module en base
	id_genre_module_base=$(echo "SELECT genre_id FROM ipc.t_module WHERE categorie = '$categorie_donnee' AND numero_module = '$module_donnee' AND numero_message = '$message_donnee' AND mode_id = $id_programme" | mysql --skip-column-names -ucargo -padm5667)
	# Si le module n'existe pas : 
	if [ ! $id_genre_module_base ]; then
		echo "${categorie_donnee}${module_donnee}${message_donnee};$genre_donnee;$programme_donnee;Module non trouvé" >> $fichierlog
		echo "Module non trouvé"
	else
		
		numero_genre_base=$(echo "SELECT CONCAT(numero_genre, '_', intitule_genre) as genre FROM ipc.t_genre WHERE id = $id_genre_module_base" | mysql --skip-column-names -ucargo -padm5667)
		echo "${categorie_donnee}${module_donnee}${message_donnee};$genre_donnee;$programme_donnee;$numero_genre_base" >> $fichierlog
		echo "Genre de la donnée : $genre_donnee <=> Genre en base : $numero_genre_base"
	fi
done
echo "Données enregistrées dans le fichier $fichierlog"
