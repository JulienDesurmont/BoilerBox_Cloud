#!/bin/bash
fichierlog=${BOILERBOX}/web/logs/dataError.csv

nbError=$(echo "SELECT COUNT(*) FROM ipc.t_donneetmp WHERE erreur = 'DD'" | mysql --skip-column-names -ucargo -padm5667)
echo "" > $fichierlog
echo "Nombre de data en erreur : $nbError" >> $fichierlog
echo "Fichier;Mise en base date;Mise en base heure;Fichier en erreur;Valeur 1;Valeur 2;Localisation;Horodatage erreur date;Horodatage erreur heure;Cycle;Module" >> $fichierlog
offset_interval=$(echo "$nbError * 20 / 100" | /usr/bin/bc)
last_offset=$(echo "$nbError - 1" | /usr/bin/bc)
offset=0;
for i in `seq 0 $last_offset`
do
	if [ $offset -eq $offset_interval ] || [ $offset -eq  0 ] || [ $offset -eq $last_offset ]; then
	donnee_erronee=($(echo "
		SELECT *
		FROM ipc.t_donneetmp dt 
		WHERE erreur = 'DD' 
		ORDER BY horodatage ASC 
		LIMIT 1 
		OFFSET $i
	" | mysql --skip-column-names -ucargo -padm5667 ))

        id_donnee_erronee=${donnee_erronee[0]}
        erreur_donnee_erronee=${donnee_erronee[1]}
        date_donnee_erronee=${donnee_erronee[2]}
        heure_donnee_erronee=${donnee_erronee[3]}
        cycle_donnee_erronee=${donnee_erronee[4]}
        valeur1_donnee_erronee=${donnee_erronee[5]}
        valeur2_donnee_erronee=${donnee_erronee[6]}
        genre_donnee_erronee=${donnee_erronee[7]}
        categorie_donnee_erronee=${donnee_erronee[8]}
        numodule_donnee_erronee=`printf "%02d\n" ${donnee_erronee[9]}`
        numessage_donnee_erronee=`printf "%02d\n" ${donnee_erronee[10]}`
        nomfichier_donnee_erronee=${donnee_erronee[11]}
        affaire_donnee_erronee=${donnee_erronee[12]}
        localisation_donnee_erronee=${donnee_erronee[13]}
        programme_donnee_erronee=${donnee_erronee[14]}
	horodatage_donnee_erronee="$date_donnee_erronee $heure_donnee_erronee"
	module_donnee_erronee="$categorie_donnee_erronee$numodule_donnee_erronee$numessage_donnee_erronee"
	echo "Affaire : $affaire_donnee_erronee - localisation : $localisation_donnee_erronee - horodatage = $horodatage_donnee_erronee module=$module_donnee_erronee"
	
	site_id_erreur=$(echo "
                SELECT s.id
                FROM ipc.t_site s
                WHERE s.affaire = '$affaire_donnee_erronee'
        " | mysql --skip-column-names -ucargo -padm5667 )
        localisation_id_erreur=$(echo "
                SELECT l.id
                FROM ipc.t_localisation l
		WHERE l.site_id = $site_id_erreur
		AND l.numero_localisation = $localisation_donnee_erronee
        " | mysql --skip-column-names -ucargo -padm5667 )

	echo "site : $site_id_erreur - Localisation : $localisation_id_erreur"

	processes=$(echo "
	SELECT
                nom,
                date_traitement
        FROM ipc.t_fichier
        WHERE id =
        (
                SELECT fichier_id
                FROM ipc.t_donnee
                WHERE horodatage = '$horodatage_donnee_erronee'
		AND cycle = '$cycle_donnee_erronee'
                AND module_id =
                (
                        SELECT module_id
                        FROM ipc.localisation_module
                        WHERE module_id IN (
                                SELECT m.id
                                FROM ipc.t_module m
                                JOIN (
                                        SELECT dt.categorie, dt.numero_module, dt.numero_message
                                        FROM ipc.t_donneetmp dt
                                        WHERE erreur = 'DD'
                                        ORDER BY horodatage ASC
                                        LIMIT 1
                                        OFFSET $i
                                ) tdt
                                ON m.categorie = tdt.categorie AND  m.numero_module = tdt.numero_module AND m.numero_message = tdt.numero_message
                        )
                        AND localisation_id = $localisation_id_erreur
                )
		AND localisation_id = $localisation_id_erreur
	)" | mysql --skip-column-names -ucargo -padm5667 )
	nouvelleLigne="$processes $nomfichier_donnee_erronee $valeur1_donnee_erronee $valeur2_donnee_erronee $localisation_donnee_erronee $horodatage_donnee_erronee $cycle_donnee_erronee $module_donnee_erronee"
	echo $nouvelleLigne | sed -e 's/\s/;/g' >> $fichierlog
        offset=0
        fi
	offset=$(echo "$offset + 1" | /usr/bin/bc)
done
