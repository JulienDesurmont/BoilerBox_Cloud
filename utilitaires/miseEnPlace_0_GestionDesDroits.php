<?php
$commande = getenv("DOCUMENT_ROOT").'/../utilitaires/system/miseEnPlace_0_GestionDesDroits.sh';
$cmd_modification_droits = shell_exec($commande);
if($cmd_modification_droits === null) {
	echo "Erreur lors de la commande : $commande";
} else {
	echo "Cache réinitialisé";
}
