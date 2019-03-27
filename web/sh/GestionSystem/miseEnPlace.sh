#!/bin/sh


echo "Modification des droits sur les dossiers"
${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/miseEnPlace_0_GestionDesDroits.sh

echo "Creation de la base de donnée"
mysql -u cargo --password=adm5667 < ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/miseEnPlace_1_CreationDataBase.sql

echo "Création des tables"
${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/miseEnPlace_2_CreationBaseTables.sh

echo "Création des partitions et sous partitions"
mysql -u cargo --password=adm5667 < ${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/miseEnPlace_3_CreationBasePartitions.sql

echo "Création des comptes Admin, Client et Technicien"
${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/miseEnPlace_4_CreationUsers.sh

echo "Gestion des liens pour les démarrages et redémarrages serveur"
${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/miseEnPlace_5_init.d.sh

echo "Vérification et mise en place des modules pour utilisation gzip"
${BOILERBOX}/web/sh/GestionSystem/miseEnPlace/miseEnPlace_6_modules.sh
