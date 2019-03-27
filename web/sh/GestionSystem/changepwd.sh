#!/bin/sh

# Changer un mot de passe                       php app/console fos:user:change-password nomUtilisateur nouveauMotDePasse
# Changement du mot de passe toutes les heures
heurej=`date "+%Y-%m-%d %H"`

jour=${heurej:5:2}
mois=${heurej:8:2}
heure=${heurej:10}
pass=`echo "( 5667 * ($heure + 4 ) * 100 ) / ( $mois + $jour )" | bc`
pass=${pass:0:5}

php ${BOILERBOX}/app/console fos:user:change-password Admintmp $pass
