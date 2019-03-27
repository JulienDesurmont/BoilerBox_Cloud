<?php 
//src/Ipc/ProgBundle/Services/Configuration/ServiceConfiguration
//	Service effectuant le transfert des fichiers ftp des localisations du site courant

namespace Ipc\ProgBundle\Services\Configuration;

use Ipc\ProgBundle\Entity\Donnee;
use Ipc\ProgBundle\Entity\Site;
use Ipc\ProgBundle\Entity\Configuration;
use Ipc\ProgBundle\Entity\DonneeDoublon;



class ServiceConfiguration {
protected $dbh;
protected $securityContext;
protected $srv_session;
protected $affaire_site_courant;

public function __construct($srv_connexion, $securityContext, $srv_session) {
	$this->dbh = $srv_connexion->getDbh();
	$this->em = $srv_connexion->getManager();
	$this->srv_session = $srv_session;
	$this->securityContext = $securityContext;
	$this->srv_session = $srv_session;
}

//      Recherche et retourne la derniere date avant laquelle une donnée est trouvée dans un intervalle d'une journée
public function rechercheLastValue($idModule, $idLocalisation, $strLastDate, $strLimitFirstDate) {
	// Création de la variable de type Date correspondante à la date limite minimum 
	$limitFirstDate	= new \Datetime($strLimitFirstDate);
	// Analyse jour par jour et récupération de la dernière valeur
	// Mise au format sql, pour les requêtes, de la date avant laquelle rechercher une valeur
	$dateFinTmp = date('Y-m-d H:i:s', strtotime($strLastDate));
	// Création de la variable de date de référence, => date à partir de laquelle on recherche une données. 
	// Initialiement elle est = à la date avant laquelle rechercher une valeur
	$tmp_datedebut = $dateFinTmp;	//date('Y-m-d H:i:s', strtotime($dateFinTmp));
	// Recherche d'une donnée depuis la date de référence jusqu'à la date de fin d'intervalle
	$fin = false;
	$tmp_donnee	= new Donnee();
	$tmpNbFirstValues = 0;
	// La boucle est parcourue tant qu'aucune données n'est trouvée et tant que l'on à pas atteind la date limite minimale
	while(($tmpNbFirstValues == 0) && ($fin==false)) {
		// Récupération de la date de référence comme date de début
		$dateTmp = new \Datetime($tmp_datedebut);
		// Soustraction d'un mois pour obtenir une nouvelle date de début
		$dateTmp->sub(new \DateInterval('P1M'));
		// Si la date de debut est < que la date de début-autorisée date de début = date de début-autorisée & Fin de recherche
		if ($dateTmp < $limitFirstDate) {
			$fin = true;
			$dateTmp = $limitFirstDate;
		}
		// Récupération de la nouvelle date de début au format AAAA-MM-JJ HH:ii:ss
		$dateBeforeDebut = $dateTmp->format('Y-m-d H:i:s');
		// Requête de recherche d'une valeur
		$tmpNbFirstValues = $tmp_donnee->SqlGetNbLast($this->dbh, $dateBeforeDebut, $tmp_datedebut, $idModule, $idLocalisation);
		// Si la recherche ne retourne pas de valeur, on recommence la boucle avec la nouvelle date de début comme date de référence	
		if ($tmpNbFirstValues == 0) {
			$tmp_datedebut = $dateBeforeDebut;
		}
	}
	// Lorsqu'une donnée est trouvée la recherche est faite en parcourant le mois par intervalle de 6 jours
	$fin = false;
	$tmpNbFirstValues = 0;
	while (($tmpNbFirstValues == 0) && ($fin==false)) {
		$dateTmp = new \Datetime($tmp_datedebut);
		$dateTmp->sub(new \DateInterval('P6D'));
		if ($dateTmp < $limitFirstDate) {
			$fin = true;
			$dateTmp = $limitFirstDate;
		}
		$dateBeforeDebut = $dateTmp->format('Y-m-d H:i:s');
		$tmpNbFirstValues=$tmp_donnee->SqlGetNbLast($this->dbh, $dateBeforeDebut, $tmp_datedebut, $idModule, $idLocalisation);
		if ($tmpNbFirstValues == 0) {
			$tmp_datedebut = $dateBeforeDebut;
		}
	}
	// Lorsqu'une donnée est trouvée la recherche est faite en parcourant le mois par intervalle de 1 journée
	$fin = false;
	$tmpNbFirstValues = 0;
	while (($tmpNbFirstValues == 0) && ($fin==false)) {
		$dateTmp = new \Datetime($tmp_datedebut);
		$dateTmp->sub(new \DateInterval('P1D'));
		if ($dateTmp < $limitFirstDate) {
			$fin = true;
			$dateTmp = $limitFirstDate;
		}
		$dateBeforeDebut = $dateTmp->format('Y-m-d H:i:s');
		$tmpNbFirstValues = $tmp_donnee->SqlGetNbLast($this->dbh, $dateBeforeDebut, $tmp_datedebut, $idModule, $idLocalisation);
		$tmp_datedebut = $dateBeforeDebut;
		// Si la date de début est < que la date limit la date de début = la date limite
		if ($dateTmp < $limitFirstDate) {
			$tmp_datedebut = $limitFirstDate;
		}
	}
	return($tmp_datedebut);
}


// * * * * * * * * * * * * * * * * * * * * * * * * * * 				FONCTIONS SUR LES DATES				* * * * * * * * * * * * * * * * * * * * * * * * * * 
// Fonction retournant la date et l'heure de l'ipc
public function maj_date() {
	$tab_mois = array("Jan", "Fév", "Mar", "Avr", "Mai", "Jun", "Jul", "Aou", "Sep", "Oct", "Nov", "Déc");
	$date_actuelle = new \Datetime();
	$tab_de_date['heure'] = $date_actuelle->format('H:i:s');
	$tab_de_date['jour'] = $date_actuelle->format('d').' '.$tab_mois[intval($date_actuelle->format('m')) - 1 ].' '.$date_actuelle->format('Y');
	$tab_de_date['timestamp'] = $date_actuelle->getTimestamp();
	return($tab_de_date);
}


// Récupére une date au format 2016-01-14 07:00:00 et retourne la date sous forme JAN 16
public function datefr($dateSource, $typeFormat) {
    $datefr = null;
    if ($typeFormat == "Mfr") {
        $Mois = array("JAN", "FEV", "MAR", "AVR", "MAI", "JUN", "JUL", "AOU", "SEP", "OCT", "NOV", "DEC");
        $pattern = '/^(\d{4})-(\d{2})-(\d{2})/';
        if (preg_match($pattern, $dateSource, $tabSortie)) {
            $datefr = $Mois[intval($tabSortie[2])-1];
        }
    }
    return($datefr);
}








//  Récupération des X derniers messages du numéro de genre passé en paramètre pour la localisation passée en paramètre sur une période de X mois
/**
 * getXMessageByGenre
 *
 * condition_valeur = 'ex : valeur 1 ='
 * valeur           = '1'
*/
public function getXMessagesByGenreExtend($numero_genre, $id_localisation, $nombre_messages, $nombre_mois, $condition_valeur, $valeur) {
	// Récupération de la valeur définie dans la condition
	$date_fin = date('Y-m-d H:i:s', strtotime('+6 days'));
	$duree = '-'.$nombre_mois.' months';
	$date_debut = date('Y-m-d H:i:s', strtotime($duree, strtotime($date_fin)));
	$liste_id_modules = $this->getListeModulesByNumeroGenreExtend($numero_genre, $id_localisation);
	$donnee = new Donnee();
	if (($condition_valeur == null) || ($valeur == null)) {
		$condition_sql = null;
	} else {
		$condition_sql = $condition_valeur.$valeur;
	}
	$tab_donnees = $donnee->sqlGetXLast($this->dbh, $date_debut, $date_fin, $liste_id_modules, $id_localisation, $nombre_messages, $condition_sql);
	if ($tab_donnees != null) {
		// Récupération des noms des modules dont l'identifiant est récupéré
		foreach ($tab_donnees as $key => $donnee) {
			$id_module = $donnee['module_id'];
			// Récupération du message du module en modifiant les caractères spéciaux
			$entity_module = $this->em->getRepository('IpcProgBundle:module')->find($id_module);
			$entity_message_module = $entity_module->getMessage();
			$entity_genre_module = $entity_module->getGenre();
			$nom_module = $entity_module->getIntituleModule();
			// Récupération de la valeur du message
			$valeur1 = $donnee['valeur1'];
			$valeur2 = $donnee['valeur2'];
			$tmp_message = $this->gestionCaracteresSpeciaux($entity_message_module, $entity_genre_module->getNumeroGenre(), $valeur1, $valeur2); 
			$tab_donnees[$key]['cycle'] = $this->setCycleFormat($tab_donnees[$key]['cycle']);
			$tab_donnees[$key]['message'] = $this->extractionMessage($tmp_message);
			$tab_donnees[$key]['module'] = $this->extractionModule($tmp_message);
			$tab_donnees[$key]['moisFr'] = $this->datefr($tab_donnees[$key]['horodatage'],'Mfr');
			$tab_donnees[$key]['couleur'] = $entity_genre_module->getCouleur();
		}
	}
	return($tab_donnees);
}

public function extractionHeureFichier($nomFichier) {
    $configuration  = new Configuration();
    $siecle = $configuration->SqlGetParam($this->dbh, 'siecle');
	$annee = intval($siecle) - 1;
	$pattern_heureFichier = '/(\d{2})(\d{2})(\d{2})_(\d{2})-(\d{2})-(\d{2})\.lci\.bin$/';
	if (preg_match($pattern_heureFichier, $nomFichier, $tabFichier) ) {
		//$dateFichier = $annee.$tabFichier[1].'-'.$tabFichier[2].'-'.$tabFichier[3].'t'.$tabFichier[4].':'.$tabFichier[5].':'.$tabFichier[6];
		//return strtotime($dateFichier);
		$dateFichier = $annee.$tabFichier[1].'-'.$tabFichier[2].'-'.$tabFichier[3].' '.$tabFichier[4].':'.$tabFichier[5].':'.$tabFichier[6];
		return ($dateFichier);
	}
	return null;
}

// Fonction qui recupère l'information du module dans le message
private function extractionModule($message) {
	$pattern_extraction = '/^(.+?)\s-.*$/';
	if (preg_match($pattern_extraction,$message,$tab_extraction)) {
		return(trim($tab_extraction[1]));
	}
	return $message;
}



// Fonction qui retire le nom du module au message
private function extractionMessage($message) {
	$pattern_extraction = '/^.+?\s-(.*)$/';		
	if (preg_match($pattern_extraction, $message, $tab_extraction)) {
		return (trim($tab_extraction[1]));
	}
	return $message;
}


// Idem ci dessus sauf que la famille de genre est prise en compte (ex Genre 1, 100, 103 pour la famille du genre 1)  : Fonction qui retourne la liste des modules associés à la famille de genre
public function getListeModulesByNumeroGenreExtend($numero_genre, $id_localisation) {
	$em = $this->em;
	$entity_automateCourant = $em->getRepository('IpcProgBundle:localisation')->find($id_localisation);
	$entity_modeCourant = $entity_automateCourant->getMode();
	$liste_id_modules = '';
	$numero_famille_genre = $numero_genre;
	if ($entity_modeCourant != NULL) {
		// Récupération de l'entité du genre
		$tab_id_genres = array();
		$entities_genre = $em->getRepository('IpcProgBundle:genre')->findAll();
		// Récupération du numéro du genre de la famille. Un genre n'ayant qu'un caractère correspond au genre de la famille. Un genre ayany 3 caractère a son numéro de famille sur le deuxième caractère
		// Exemple genre 1 => Famille genre 1 ; genre 135 Famille genre 3
		if (strlen($numero_genre) == 3) {
			$numero_famille_genre = substr($numero_genre,1,1);
		}
		foreach ($entities_genre as $entity_genre) {
			if (strlen($entity_genre->getNumeroGenre()) == 1) {
				if ($entity_genre->getNumeroGenre() == $numero_famille_genre) {
					array_push($tab_id_genres, $entity_genre->getId());
				}
			} elseif (strlen($entity_genre->getNumeroGenre()) == 3) {
				if (substr($entity_genre->getNumeroGenre(),1,1) == $numero_famille_genre) {
					array_push($tab_id_genres, $entity_genre->getId());
				}
			}
		}
		// Recherche des modules dont les genres et le mode sont ceux récupérés
		$entities_module = $em->getRepository('IpcProgBundle:module')->myFindExtendByModeAndGenre($entity_modeCourant->getId(),$tab_id_genres);
		// Récupération de la liste des identifiants des modules du genre et du mode définie
		foreach($entities_module as $entity_module) {
			$liste_id_modules .= $entity_module->getId().',';
		}
		$liste_id_modules = substr($liste_id_modules,0,-1);
	}
	return($liste_id_modules);
}


// Récupération des X derniers messages du numéro de genre passé en paramètre pour la localisation passée en paramètre sur une période de X mois
/** 
 * getXMessageByGenre
 *
 * condition_valeur = 'ex : valeur 1 ='
 * valeur		= '1'
*/
public function getXMessagesByGenre($numero_genre, $id_localisation, $nombre_messages, $nombre_mois, $condition_valeur, $valeur) {
	// Récupération de la valeur définie dans la condition
	$date_fin = date('Y-m-d H:i:s');
	$duree = '-'.$nombre_mois.' months';
	$date_debut = date('Y-m-d H:i:s', strtotime($duree, strtotime($date_fin)));
	$liste_id_modules = $this->getListeModulesByNumeroGenre($numero_genre, $id_localisation);
	$donnee = new Donnee();
	if (($condition_valeur == null) || ($valeur == null)) {
		$condition_sql = null;
	} else {
		$condition_sql = $condition_valeur.$valeur;
	}
	$tab_donnees = $donnee->sqlGetXLast($this->dbh, $date_debut, $date_fin, $liste_id_modules, $id_localisation, $nombre_messages, $condition_sql);
	if ($tab_donnees != null) {
		// Récupération des noms des modules dont l'identifiant est récupéré
		foreach ($tab_donnees as $key => $donnee) {
			$id_module = $donnee['module_id'];
			// Récupération du message du module en modifiant les caractères spéciaux
			$entity_module = $this->em->getRepository('IpcProgBundle:module')->find($id_module);
			$entity_message_module = $entity_module->getMessage();
			$entity_genre_module = $entity_module->getGenre();
			$nom_module	= $entity_module->getIntituleModule();
			// Récupération de la valeur du message
			$valeur1 = $donnee['valeur1'];
			$valeur2 = $donnee['valeur2'];
			$tab_donnees[$key]['cycle'] = $this->setCycleFormat($tab_donnees[$key]['cycle']);
			$tab_donnees[$key]['message'] = $this->gestionCaracteresSpeciaux($entity_message_module, $entity_genre_module->getNumeroGenre(), $valeur1, $valeur2);
			$tab_donnees[$key]['module'] = $this->gestionCaracteresSpeciaux($nom_module, $entity_genre_module->getNumeroGenre(), $valeur1, $valeur2);
			$tab_donnees[$key]['moisFr'] = $this->datefr($tab_donnees[$key]['horodatage'],'Mfr');
			$tab_donnees[$key]['couleur'] = $entity_genre_module->getCouleur();
		}
	}
	return($tab_donnees);
}

// Fonction retournant la liste des modules de genre défini
public function getListeModulesByNumeroGenre($numero_genre, $id_localisation) {
	$em = $this->em;
	$entity_automateCourant = $em->getRepository('IpcProgBundle:localisation')->find($id_localisation);
	$entity_modeCourant = $entity_automateCourant->getMode();
	$liste_id_modules = '';
	if ($entity_modeCourant != NULL) {
		// Récupération de l'entité du genre
		$entity_genre = $em->getRepository('IpcProgBundle:genre')->findOneByNumeroGenre($numero_genre);
		// Recherche des modules dont les genres et le mode sont ceux récupérés
		$entities_module = $em->getRepository('IpcProgBundle:module')->findBy(array('mode'=>$entity_modeCourant,'genre'=>$entity_genre));
		// Récupération de la liste des identifiants des modules du genre et du mode définie
		foreach ($entities_module as $entity_module) {
			$liste_id_modules .= $entity_module->getId().',';
		}
		$liste_id_modules = substr($liste_id_modules,0,-1);
	}
	return($liste_id_modules);
}

// Fonction qui retourne le cycle sous 3 chiffre
public function setCycleFormat($cycle) {
	// 1 Compte le nombre de chiffre du cycle
	$sizeCycle = strlen($cycle);
	for ($i = $sizeCycle; $i < 3; $i++) {
		$cycle = '0'.$cycle ;
	}
	return($cycle);	
}


private function gestionCaracteresSpeciaux($message, $numero_genre, $valeur_dollar, $valeur_livre) {
        // Récupération des caractères de remplacements
        $configuration  = new Configuration();
        $message_dollar = '';
        $arrondi = $configuration->SqlGetParam($this->dbh, 'arrondi');
        $valeur_dollar_affichee = round($valeur_dollar, $arrondi);
        $valeur_livre_affichee = round($valeur_livre, $arrondi);
        if ($valeur_dollar_affichee == 0) {
                $message_dollar = $configuration->SqlGetParam($this->dbh, 'dollar_0').' ';
        } elseif ($valeur_dollar == 1) {
                $message_dollar = $configuration->SqlGetParam($this->dbh, 'dollar_1').' ';
        }
        $message_retour = '';
        if ($numero_genre != 3) {
                $message_retour = preg_replace('/\$/', $message_dollar, $message);
        } else {
                $message_retour = preg_replace('/\$/', $valeur_dollar_affichee, $message);
        }
        $message_retour = preg_replace('/£/', $valeur_livre_affichee, $message_retour);
        return($message_retour);
}


//  Fonction qui permet de définir la variable de session infoLimitePeriode
public function setInfoLimitePeriode() {
    // Définition du tableau des périodes d'analyse
    // Récupération du tableau de limitation des requêtes en fonction des périodes d'analyse
    // Récupération du site courant
    $site = new Site();
    $idSiteCourant = $site->SqlGetIdCourant($this->dbh);
    $site = $this->em->getRepository('IpcProgBundle:Site')->find($idSiteCourant);
    // Récupération des localisations du site courant
    $liste_localisation = $site->getLocalisations();
    // Pour chaque localisation : Récupération de la période d'analyse et définition de la variable de session
    $tabPeriodeAnalyse = array();
    foreach ($liste_localisation as $localisation) {
        $periodeInfo = $this->em->getRepository('IpcProgBundle:infosLocalisation')->findBy(array('localisation' => $localisation, 'periodeCourante' => 1));
        if (! empty($periodeInfo)) {
            if ($periodeInfo[0]->getHorodatageDeb() != null) {
                $tabPeriodeAnalyse[$localisation->getId()]['dateDeb'] = $periodeInfo[0]->getHorodatageDeb()->format('d/m/Y H:i:s');
            } else {
                $tabPeriodeAnalyse[$localisation->getId()]['dateDeb'] = null;
            }
            if ($periodeInfo[0]->getHorodatageFin() != null) {
                $tabPeriodeAnalyse[$localisation->getId()]['dateFin'] = $periodeInfo[0]->getHorodatageFin()->format('d/m/Y H:i:s');
            } else {
                $tabPeriodeAnalyse[$localisation->getId()]['dateFin'] = null;
            }
        }
    }
    if (! empty($tabPeriodeAnalyse)) {
        $this->srv_session->set('infoLimitePeriode', $tabPeriodeAnalyse);
    }
    return(0);
}

// Déplacement des doublons
public function moveDoublons(){
    $entities_tmpdoublons =  $this->em->getRepository('IpcProgBundle:Donneetmp')->findBy(
                                                                            array('erreur' => 'DD'),
                                                                            array('horodatage' => 'ASC'));
    foreach($entities_tmpdoublons as $entity_tmpdoublon) {
        $entity_doublon = new DonneeDoublon();
        $entity_doublon->setErreur($entity_tmpdoublon->getErreur());
        $entity_doublon->setHorodatage($entity_tmpdoublon->getHorodatage());
        $entity_doublon->setCycle($entity_tmpdoublon->getCycle());
        $entity_doublon->setValeur1($entity_tmpdoublon->getValeur1());
        $entity_doublon->setValeur2($entity_tmpdoublon->getValeur2());
        $entity_doublon->setNumeroGenre($entity_tmpdoublon->getNumeroGenre());
        $entity_doublon->setCategorie($entity_tmpdoublon->getCategorie());
        $entity_doublon->setNumeroModule($entity_tmpdoublon->getNumeroModule());
        $entity_doublon->setNumeroMessage($entity_tmpdoublon->getNumeroMessage());
        $entity_doublon->setNomFichier($entity_tmpdoublon->getNomFichier());
        $entity_doublon->setAffaire($entity_tmpdoublon->getAffaire());
        $entity_doublon->setNumeroLocalisation($entity_tmpdoublon->getNumeroLocalisation());
        $entity_doublon->setProgramme($entity_tmpdoublon->getProgramme());
        $this->em->persist($entity_doublon);
        $this->em->remove($entity_tmpdoublon);
    }
    $this->em->flush();
}



// Fonction qui récupère la date de la dernière donnée importée en base
public function getLastDataTime() {
	$ent_donnee = $this->em->getRepository('IpcProgBundle:Donnee')->findLastData();
	$heure_recup = date('d/m/Y à H:i:s');

	if ($ent_donnee !== null) {
		$last_data = $ent_donnee->getHorodatage()->format('d/m/Y à H:i:s');
		$message_retour = "($heure_recup)<br />\nHeure de la donnée la plus récente : Le $last_data";
	} else {
		$message_retour = "($heure_recup)<br />\nAucune donnée en base";
	}
	return($message_retour);
}

}
