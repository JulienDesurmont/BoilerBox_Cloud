<?php
//src/Ipc/ProgBundle/Services/TransformeTexte/ServiceTransformeTexte.php

namespace Ipc\ProgBundle\Services\TransformeTexte;

class ServiceTransformeTexte {

// Fonction qui supprime les espaces consécutifs dans une chaine de caractère.
public function supprimerEspaces($texte) {
	return preg_replace('/\s+/', ' ', $texte);
}


// Foncion qui retourne lettre par lettre le mot passé en argument.
public function afficheListeDesLettres($texte) {
	foreach(str_split($texte) as $key => $value) {
        echo $key." => ".$value."<br />";
    }
}

}

