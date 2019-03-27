<?php
//src/Ipc/ProgBundle/Services/FillNumbers/ServiceFillNumbers.php

namespace Ipc\ProgBundle\Services\FillNumbers;

use Ipc\ProgBundle\Entity\FichierIpc;
use Ipc\ProgBundle\Entity\Genre;
use Ipc\ProgBundle\Entity\Module;

class ServiceFillNumbers {

// Retourne le nombre passé en paramètre avec le nombre de caractères demandé parmis 2 ou 3 caractère (précede le nombre du nombre de 0 adéquat)
public function fillNumber($num, $nbcar) {
	$pattern = '/^(.)$/';
	if (preg_match($pattern, $num)) {
		if ($nbcar == 2) {
			$num = "0".$num;
		} elseif ($nbcar == 3) {
			$num = "00".$num;
		}
	}
	$pattern = '/^(..)$/';
	if (preg_match($pattern, $num) && ($nbcar == 3)) {
		$num = "0".$num;
	}
	return($num);
}

// Fonction qui recoit une date en entrée et inverse l'année et le jour : ex -> (entrée) 2014-05-10 12:23:34 <- (sortie) 10-05-2014 12:23:34
public function formaterDate($dateToBeTransformed, $format) {
    $dateTransformed = null;
    if ($format == 'mySql') {
        $pattern = '/^(\d{2})[-\/](\d{2})[-\/](\d{4}) (\d{2}:\d{2}:\d{2})$/';
    } else {
        $pattern = '/^(\d{4})[-\/](\d{2})[-\/](\d{2}) (\d{2}:\d{2}:\d{2})$/';
    }
    if (preg_match($pattern, $dateToBeTransformed, $tabDate)) {
        $dateTransformed = $tabDate[3].'/'.$tabDate[2].'/'.$tabDate[1].' '.$tabDate[4];
    }
    return($dateTransformed);
}




// Fonction qui recoit une date en entrée et inverse l'annee et le jour pour recherche Sql
public function reverseDate($horodatage) {
	$pattern = '/^(\d{2})([-\/]\d{2}[-\/])(\d{4})(.*)$/';
	if (preg_match($pattern, $horodatage, $tabdate)) {
		$retour_heure = $tabdate[3].$tabdate[2].$tabdate[1].$tabdate[4];
		return($retour_heure);
	} else {
		return($horodatage);
	}
}

// Fonction qui recoit une date en entrée au format YYYY[-/]mm[-/]dd et inverse l'annee et le jour 
public function reverDate($horodatage) {
	$pattern = '/^(\d{4})([-\/]\d{2}[-\/])(\d{2})(.*)$/';
	if (preg_match($pattern, $horodatage, $tabdate)) {
		$retour_heure = $tabdate[3].$tabdate[2].$tabdate[1].$tabdate[4];
		return($retour_heure);
	} else {
		return($horodatage);
	}
}

public function changeFormatDate($horodatage, $format, $type) {
    switch($type) {
		case 'setHour':
				// La demande concerne l'ajout de la partie horodatage.
				if ($format == 'sql') {
					// La date est passée au format sql (YYYY[-/]mm[-/]dd).
					$pattern = '/^(\d{4})[-\/](\d{2})[-\/](\d{2})$/';
					if (preg_match($pattern, $horodatage, $tabdate)) {	
						// Retourne la date avec comme séparateur '/' et avec ajout de l'horodatage : '00:00:00'
						$nouvel_horodatage = $tabdate[1].'/'.$tabdate[2].'/'.$tabdate[3].' 00:00:00';
					}
				}	
			break;
	}
	return $nouvel_horodatage;
}


// Accepte en entrée un date au format : jj-mm-yyyy hh:ii[:ss] et retourne la partie Date ou Heure en fonction du paramètre 1 ($type)
public function getDateFromHorodatage($type, $horodatage) {
	$pattern = '/^(\d{2}[-\/]\d{2}[-\/]\d{4})\s(.*)$/';
    if (preg_match($pattern, $horodatage, $tabdate)) {
		if ($type == 'date') {
        	$retour_heure = $tabdate[1];
		} else {
			$retour_heure = $tabdate[2];
		}
        return($retour_heure);
    } else {
        return($horodatage);
    }
}

}

