#!/bin/sh

chemin_console_php=${BOILERBOX}/app/console

echo "Chemin : ${chemin_console_php}"

php ${chemin_console_php} fos:user:create Admin Assistance_IBC@lci-group.fr '@dm|n5667'
php ${chemin_console_php} fos:user:promote Admin ROLE_ADMIN
php ${chemin_console_php} fos:user:create Superviseur Superviseur@lci-group.fr 'coucouCnous'
php ${chemin_console_php} fos:user:promote Superviseur ROLE_SUPERVISEUR
php ${chemin_console_php} fos:user:create Client noneClient@lci-group.fr 'Cl|ent'
php ${chemin_console_php} fos:user:create Technicien noneTechnicien@lci-group.fr 'tech5667'
php ${chemin_console_php} fos:user:promote Technicien ROLE_TECHNICIEN
php ${chemin_console_php} fos:user:create Admintmp noneAdmin@lci-group.fr tempo
php ${chemin_console_php} fos:user:promote Admintmp ROLE_ADMINTMP

# Commandes de gestion des comptes
# Création d'une nouveau compte		php app/console fos:user:create 	 nomUtilisateur adresseEmail motDePasse
# Activation d'un compte		php app/console fos:user:activate 	 nomUtilisateur
# Désactivation d'un compte		php app/console fos:user:deactivate 	 nomUtilisateur
# Ajout d'un droit 			php app/console fos:user:promote 	 nomUtilisateur ROLE_ADMIN
# Suppression d'un droit		php app/console fos:user:demote 	 nomUtilisateur ROLE_ADMIN
# Changer un mot de passe		php app/console fos:user:change-password nomUtilisateur nouveauMotDePasse
