<?php
// src/Ipc/ProgBundle/Services/RequeteType/ServiceRequeteType
namespace Ipc\ProgBundle\Services\RequeteType;

use Ipc\ProgBundle\Entity\RequeteType;
use Ipc\ProgBundle\Entity\Configuration;
use Ipc\ProgBundle\Entity\Localisation;
use Ipc\ProgBundle\Entity\Fichier;
use Ipc\ProgBundle\Entity\Module;
use Ipc\ProgBundle\Entity\Genre;

use \PDO;
use \PDOException;

class ServiceRequeteType {
private $dbh;
private $configuration;
private $em;
private $srv_fillNumbers;

public function __construct($srv_connexion, $srv_fillNumbers) {
	$this->dbh = $srv_connexion->getDbh();
	$this->em = $srv_connexion->getManager();
	$this->configuration = new Configuration();
	$this->srv_fillNumbers = $srv_fillNumbers;
}


// Fonction - qui recupère les requêtes type en base de donnée
// - analyse les dates de début et de fin : 
// - appelle la fonction de calcul pour les requêtes dont les dates sont < à la date du jour	
public function index() {
	$datejour = strtotime(date('Y/m/d H:i:s'));
	$requeteType = new RequeteType();
	$tabDesRequetes = $requeteType->sqlGetAll($this->dbh);
	// Récupération de la liste des genres
	$tab_des_genres = array();
	$genre = new genre();
	$tab_genres = $genre->SqlGetAllGenre($this->dbh);
	foreach ($tab_genres as $key => $genre) {
		$tab_des_genres[$genre['id']]['intitule']= $genre['intitule_genre'];
		$tab_des_genres[$genre['id']]['numero']  = $genre['numero_genre'];
	}
	// Récupération des localisations
	$tab_des_localisations = array();
	$localisation = new Localisation();
	$tab_localisations = $this->em->getRepository('IpcProgBundle:Localisation')->SqlGetAllLocalisation($this->dbh);
	foreach ($tab_localisations as $key => $localisation) {
		$tab_des_localisations[$localisation['id']]['designation'] = $localisation['designation'];
		$tab_des_localisations[$localisation['id']]['numero'] = $localisation['numero_localisation'];
	}
	// Récupération de la liste des modules
	$tab_des_modules = array();
	$module = new Module();
	$tabModules = $module->SqlGetModulesGenreAndUnit($this->dbh);
	foreach ($tabModules as $key => $module) {
		$tab_des_modules[$module['id']]['genre']   = $module['idGenre']; 
		$tab_des_modules[$module['id']]['module']  = $module['categorie'].$this->srv_fillNumbers->fillNumber($module['numeroModule'], 2).$this->srv_fillNumbers->fillNumber($module['numeroMessage'], 2);
		$tab_des_modules[$module['id']]['unite']   = $module['unite'];
		$tab_des_modules[$module['id']]['message'] = $module['message'];
	}
	foreach ($tabDesRequetes as $requete) {
		$dateDebReq = strtotime($requete['date_debut_rapport']);
		$dateFinReq = strtotime($requete['date_fin_rapport']);
		if (($dateDebReq < $datejour) && ($dateFinReq < $datejour)) {
			$this->calculRequete($requete, $tab_des_modules, $tab_des_genres, $tab_des_localisations);
		}
	}
}

public function calculRequete($requete, $tab_des_modules, $tab_des_genres, $tab_des_localisations) {
	$requeteType = new RequeteType();
	$requeteType->setNumero($requete['numero']);
	$requeteType->setTypeRequete($requete['type_requete']);
	// Chemin et nom du fichier de la requête
	$fichier = new Fichier();
	$chemin  = $fichier->getEtatsDir();
	$nomFichier = $chemin.$requete['type_requete'].'_'.$requete['numero'];
	// Récupération des dates de début et de fin de période
	$dateDebutRapport = strtotime($requete['date_debut_rapport']);
	$dateFinRapport = strtotime($requete['date_fin_rapport']);
	// Calcul du nombre de secondes de la période
	$nbSecDiff = $dateFinRapport - $dateDebutRapport;
	// Calcul du nombre d'heure de la période
	$nbHeureDiff = $nbSecDiff/60/60;
	// Initialisation de la variable indiquant le nombre d'heures restant à analyser
	$nbHeuresRestantes = $nbHeureDiff;
	// Calcul du nombre de jours entre le début et la fin de la période
	$nbJoursDiff = $nbSecDiff/60/60/24;
	// Calcul de la prochaine date à laquelle l'Etat devra être calculé
	// Calcul : Si l'indicateur du nombe de période est != 0
	// Sinon : Suppression de la requête
	$nbPeriode = $requete['nb_periode'];
	if ($nbPeriode != 0) {
		$periodique	= $requete['periodique'];
		$nbPeriodique = $requete['nb_periodique'];
		$periode = $requete['periode'];
		$tmpEcartDate = "+ $nbPeriodique $periodique";
		$tmpDateDebuto = $requete['date_debut_rapport'];
		// Prochaine date de début
		$nextDateDebut	= new \Datetime(date('Y-m-d H:i:s', strtotime($tmpDateDebut." $tmpEcartDate")));
		$requeteType->setDateDebutRapport($nextDateDebut);
		// Prochaine date de fin
		$tmpDateDebut = $requeteType->getDateRapportStrFormat('debut');
		$nextDateFin  = new \Datetime($this->calculFinRapport($tmpDateDebut, $nbPeriode, $periode));
		$requeteType->setDateFinRapport($nextDateFin);
		$requeteType->sqlUpdateNJAR($this->dbh);
	} else {
		$requeteType->sqlDelete($this->dbh);
	}
	// Requête faite pour chaque heure
	// Premières dates pour les recherches
	$dateTmpDeb = date('Y-m-d H:i:s', $dateDebutRapport);
	$dateTmpFin = null;
	// Compteur des heures de la journée
	$heureJournee = 0;
	$fp = fopen($nomFichier, "w");
	$message = 'Titre;'.$requete['intitule']."\n";
	if ($requete['type_requete'] == 'graphique') {
		// Recherche de l'identifiant du module
		$pattern = '/^.+?d.module_id IN \((.+?)\).+?$/';
		if (preg_match($pattern, $requete['requete'], $tabRequete)) {
			$moduleId = trim($tabRequete[1]);
		}
		// Recherche de l'identifiant de la localisation
		$pattern = '/^.+?d.localisation_id IN \((.+?)\).+?$/';
		if (preg_match($pattern, $requete['requete'], $tabRequete)) {
			$localisationId = trim($tabRequete[1]);
		}
		$typeGenre = null;
		$intituleModule = null;
		$uniteModule = null;
		$codeModule = null;
		$designationLocalisation = $tab_des_localisations[$localisationId]['designation'];
		foreach ($tab_des_modules as $keyId => $module) {
			if ($keyId == $moduleId) {
				$typeGenre = $tab_des_genres[$module['genre']]['intitule'];
				$intituleModule = $module['message'];
				$uniteModule = $module['unite'];
				$codeModule = $module['module'];
			}
		}
		$message .= "Module;".$intituleModule."\n";
		$message .= "Unite;".$uniteModule."\n";
		$message .= "Localisation;".$designationLocalisation."\n";
	}
	fwrite($fp, $message);
	for ($nbHeure = 0; $nbHeure < $nbHeureDiff; $nbHeure++) {
		// Pour la première heure de la recherche nous prenons la date et l'heure de début de recherche.
		// A chaque heure suivante la date et heure de début est incrémentée d'1h
		// La date et heure de fin correspond toujours à 1h après la date et heure de début
		if ($nbHeure != 0) {
			$dateTmpDeb = date('Y-m-d H:i:s', strtotime($dateTmpDeb.' + 1 hour'));
		}
		$dateTmpFin = date('Y-m-d H:i:s', strtotime($dateTmpDeb.' + 1 hour'));
		// Modification de la requête avec les heures de début et de fin calculés
		$requeteSql = $requete['requete'];
		$pattern = '/^(.+?d.horodatage >= \').+?(\'.+?AND d.horodatage <= \').+?(\'.+?)$/';
		if (preg_match($pattern, $requeteSql, $tabRequete)) {
			$newRequeteSql = $tabRequete[1].$dateTmpDeb.$tabRequete[2].$dateTmpFin.$tabRequete[3];
		}
		$donnees = null;
		if (($reponse = $this->dbh->query($newRequeteSql)) != false) {
			$donnees = $reponse->fetchAll();
			$reponse->closeCursor();
		}
		// Pour les graphiques  : Un seul module est recherché on inscrit l'horodatage et la valeur
		$message = '';
		if ($requete['type_requete'] == 'graphique') {
			if ($donnees != null) {
				foreach ($donnees as $donnee) {
					$message = $donnee['horodatage'].';'.$donnee['cycle'].';'.$donnee['valeur1']."\n";
					fwrite($fp, $message);
				}
			}
		} else if ($requete['type_requete'] == 'listing') {
			if ($donnees != null) {
				foreach ($donnees as $donnee) {
					$typeGenre = null;
					$intituleModule = null;
					$uniteModule = null;
					$codeModule = null;
					$designationLocalisation = null;
					foreach ($tab_des_modules as $keyId => $module) {
						if ($keyId == $donnee['module_id']) {
							$typeGenre = $tab_des_genres[$module['genre']]['intitule'];
							$intituleModule = $module['message'];
							$uniteModule = $module['unite'];
							$codeModule = $module['module'];
							$designationLocalisation = $tab_des_localisations[$donnee['localisation_id']]['designation'];
						}
					}
					$message = $codeModule.';'.$designationLocalisation.';'.$donnee['horodatage'].';'.$donnee['cycle'].';'.$donnee['valeur1'].';'.$typeGenre.';'.$uniteModule.';'.$intituleModule."\n";
					fwrite($fp, $message);
				}
			}
		}
	}
	fclose($fp);
}

// Calcul de la prochaine date de fin du rapport = Date du jours + Periodique de l'etat
// La date de début et passée en tant que chaine de caractère au format:  Y-m-d 00:00:00
protected function calculFinRapport($dateDebut, $nbPeriodique, $periodique) {
	$modifDate = ' + '.$nbPeriodique.' '.$periodique;
	$datefin = date('Y-m-d H:i:s', strtotime($dateDebut." $modifDate "));
	return($datefin);
}

}
