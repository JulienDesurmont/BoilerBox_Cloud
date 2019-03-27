#!/bin/bash


fichierlog=${BOILERBOX}/web/logs/parametresSystem.log
tailleFilesystem=`df -kh ${BOILERBOX} | grep -v "Sys." | tr -s ' '`
var_sf=`echo $tailleFilesystem | cut -d ' ' -f 1`
var_taille=`echo $tailleFilesystem | cut -d ' ' -f 2`
var_utilise=`echo $tailleFilesystem | cut -d ' ' -f 3`
var_dispo=`echo $tailleFilesystem | cut -d ' ' -f 4`
var_pourcUtilise=`echo $tailleFilesystem | cut -d ' ' -f 5`
var_fs=`echo $tailleFilesystem | cut -d ' ' -f 6`
tailleRepMysql=`du -sh /var/lib/mysql | cut -f 1`
tailleRepBoilerBox=`du -sh ${BOILERBOX} | cut -f 1`
date > $fichierlog
echo "EnteteDF;Système de fichier;Taille;Utilisation;Disponibilite;Pourcentage Utilisation;FileSystem" >> $fichierlog
echo "ResultatDF;$var_sf;$var_taille;$var_utilise;$var_dispo;$var_pourcUtilise;$var_fs" >> $fichierlog
echo "TailleMysql:$tailleRepMysql" >> $fichierlog
echo "TailleBoilerBox:$tailleRepBoilerBox" >> $fichierlog
