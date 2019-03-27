<?php
// src/Ipc/ProgBundle/Services/ImportBin/ServiceImportBin.php
namespace Ipc\ProgBundle\Services\RattrapImportBin;

use Ipc\ProgBundle\Entity\Fichier;
use Ipc\ProgBundle\Entity\Donneetmp;
use Ipc\ProgBundle\Entity\Donnee;
use Ipc\ProgBundle\Entity\Module;
use Ipc\ProgBundle\Entity\Site;
use Ipc\ProgBundle\Entity\Localisation;
use Ipc\ProgBundle\Entity\Genre;

use \PDO;
use \PDOException;

class ServiceRattrapImportBin {
protected $fichierLog;
// Nombre de lignes vides récupérées dans le fichier lu
protected $nombreVides;	
// Nombre de lignes non vides récupérées dans le fichier lu
protected $nombreMessages;
// Liste des données analysées
protected $tab_donneestraites;
// Tableau des modules présents en base de donnée
protected $tab_modules;							
// Tableau des identifiants de genre
protected $tab_genres;
// Liste des donnees temporaires en erreur à inserer en base
protected $liste_donneeserreur;	
// Liste des données à insérer
protected $liste_insertion;	
protected $liste_id_donneeserronees;
// Nombre de données correctement insérées
protected $compteurReussite;
// Nombre de donnees incorrectes
protected $compteurErreur;
// Nombre de doublons detectés
protected $compteurDoublons;
// 1 fichier , 1 Site et 1 Localisation par fichier
protected $fichier;	
protected $localisation;
protected $site;
// 1 Entité Module
protected $module;
protected $dbh;
protected $log;
protected $erreurDeLaDonnee;
protected $mode;
protected $em;
protected $tab_liens_courants;
protected $tab_liens_en_erreurs;
protected $service_fillNumbers;

public function __construct($srv_connexion, $log, $service_fillNumbers) {
	$this->fichierLog = 'importBin.log';
	$this->tab_donneestraites = array();
	$this->tab_modules = array();
	$this->liste_donneeserreur	= "";
	$this->liste_insertion = "";
	$this->liste_id_donneeserronees = "";
	$this->compteurErreur = 0;
	$this->compteurReussite = 0;
	$this->compteurDoublons = 0;
	$this->service_fillNumbers = $service_fillNumbers;
	$this->tab_genres = array();
	$this->fichier = new Fichier();
	//! Lors du passage en 2100 à 00h00 : récupèration du centenaire de l'année courante : 21.
	$this->site	= new Site();
	// Erreur de Lecture des fichiers de 2099 23h.
	$this->localisation	= new Localisation();
	$this->module = new Module();								
	$this->dbh = $srv_connexion->getDbh();
	$this->log = $log;
	$this->mode = array();
	$this->em = $srv_connexion()->getManager();
	$this->tab_liens_courants = array();
	$this->tab_liens_en_erreurs	= array();
}


// Fonction qui vérifie les informations contenues dans le nom du fichier
public function verifFichier() {
	// Code qui indique si le fichier est en erreur
	$code_erreur = null;	
	// Récupération des trois informations contenues dans le nom du fichier : Le Site - La Localisation - La date
	// Si le nom du fichier comporte le nom du programme, récupération du mode
	$pattern_mode = '/^(.+?)_(.+?)_#(.+?)#_(.+?)\..*.bin$/';
	if (preg_match($pattern_mode, $this->fichier->getNom(), $contenu_mode)) {
		$affaire = $contenu_mode[1];
		$numero_localisation = $contenu_mode[2];
		$mode_designation = $contenu_mode[3];
		$date_fichier = $contenu_mode[4];
		$this->mode = $this->em->getRepository('IpcProgBundle:Mode')->findOneByDesignation($mode_designation);
		if (empty($this->mode)) {
			$code_erreur = 'MNF';
			$this->log->setLog($this->fichier->getNom().";REIMPORT [ERROR];$code_erreur;Mode $mode_designation non trouvé en base", $this->fichierLog);
		}
	} else {
		$pattern = '/^(.+?)_(.+?)_(.+?)\..*.bin$/';
		if (preg_match($pattern, $this->fichier->getNom(), $contenu)) {
			$affaire = $contenu[1];
			$numero_localisation = $contenu[2];
			$date_fichier = $contenu[3];
		} else {
			$code_erreur = 'FWN';
			$this->log->setLog($this->fichier->getNom().";REIMPORT [ERROR];$code_erreur;Fichier incorrectement nommé", $this->fichierLog);
		}		
		// Si aucun mode défini pour le fichier alors nous somme en fonctionnement 'noprog' (sans programme)
		$this->mode = $this->em->getRepository('IpcProgBundle:Mode')->findOneByDesignation('noprog');
		if (empty($this->mode)) {
			$code_erreur = 'MNF';
			$this->log->setLog($this->fichier->getNom().";REIMPORT [ERROR];$code_erreur;Mode 'noprog' non trouvé en base", $this->fichierLog);
		}
	}
	// Recherche du Site en base de donnée
	if ($code_erreur == null) {
		$this->site = $this->em->getRepository('IpcProgBundle:Site')->findOneByAffaire($affaire);
		if (empty($this->site)) {
			$code_erreur = 'SNF';
			$this->log->setLog($this->fichier->getNom().";REIMPORT [ERROR];$code_erreur $affaire;Le site $affaire n'est pas enregistré en base de donnée;$affaire", $this->fichierLog);
		}
	}
	// Recherche de la localisation associée au site 
	if ($code_erreur == null) {
		$this->localisation = $this->em->getRepository('IpcProgBundle:Localisation')->findOneBy(array('site' => $this->site, 'numeroLocalisation' => $numero_localisation));
		if (empty($this->localisation)) {
			$code_erreur = 'LNF';
			$this->log->setLog($this->fichier->getNom().";REIMPORT [ERROR];$code_erreur $affaire [$numero_localisation];La localisation du site n'est pas enregistrée en base de donnée;$affaire [$numero_localisation]", $this->fichierLog);
		}
	}
	// Si le site et la localisation sont correct : Vérification de format de la date
	if ($code_erreur == null) {
		$pattern = '/^(\d{2})(\d{2})(\d{2})_(\d{2})-(\d{2})-(\d{2})/';	
		if (! preg_match($pattern, $date_fichier, $contenus)) {
			$code_erreur = 'FWT';
			$this->log->setLog($this->fichier->getNom().";REIMPORT [ERROR];$code_erreur;L'horodatage $date_fichier est incorrectement formaté", $this->fichierLog);
		}
	}
	// Recherche de la liste des modules dont le programme est celui indiqué dans le nom du fichier
	$liste_module = $this->em->getRepository('IpcProgBundle:Module')->findBy(array('mode' => $this->mode));
	foreach ($liste_module as $module) {
		$this->tab_liens_courants[] = $module->getId();
	}
	// Retourne null si aucune erreur, le code de l'erreur si une erreur est rencontrée
	return($code_erreur);
}


public function suppression($nomDuFichierBinaire, $erreurDeLaDonnee) {
	$this->liste_id_donneeserronees = "";
	$this->fichier->setNom($nomDuFichierBinaire);
	$donneetmp = new Donneetmp();
	$donneetmp->setNomFichier($nomDuFichierBinaire);
	$donneetmp->setErreur($erreurDeLaDonnee);
	$contenu_du_fichier	= $donneetmp->SqlGetByError($this->dbh);
	// Récupération de la liste des id des données à supprimer
	foreach ($contenu_du_fichier as $contenu) {
		$this->liste_id_donneeserronees .=$contenu["id"].',';
	}
	$this->liste_id_donneeserronees = substr($this->liste_id_donneeserronees, 0, -1);
	$suppression = $donneetmp->myDelete($this->dbh, $this->liste_id_donneeserronees);
	$this->log->setLog($nomDuFichierBinaire.";REIMPORT [INFO];;Les données du fichier ayant pour code erreur ".$erreurDeLaDonnee." ont étées supprimées", $this->fichierLog);
	// Affichage du contenu du fichier de log
	$texte_log = $this->log->rechercheLastTexte($this->fichierLog, $this->fichier->getNom());
	return($texte_log);
}



// Prend en argument le fichier à insérer en base de donnée
public function importation($nomDuFichierBinaire, $erreurDeLaDonnee, $tab_modules) {
	// Suppression de la limite de temps d'execution du script car il fait plus de 300 secondes d'execution
	set_time_limit(0); 
	ignore_user_abort(1);
	$this->erreurDeLaDonnee = $erreurDeLaDonnee;
	$code_erreur = null;
	$this->tab_modules = $tab_modules;
	$this->fichier->setNom($nomDuFichierBinaire);
	// 1) Vérification des informations contenues dans le nom du fichier : Affaire / N°Localisation / Horodatage
	// Instancie la localisation et le site
	$code_erreur = $this->verifFichier($this->dbh, $this->fichier->getNom());
	// 2) Si les informations du fichier sont corrects
	// Enregistrement du fichier en base de donnée si aucune erreur n'est rencontrée lors des vérifications
	if ($code_erreur == null) {	
		// Si les informations du fichier ne sont pas en base on les enregistres
		// Recherche du nom du fichier en base : Si il est présent c'est qu'il a déjà été importé -> Fin de recherche
		$arraytmp_fichier = $this->fichier->SqlGetInfosBase($this->dbh, 1, $this->fichier->getNom()); 
		// Récupére l'id et la date de traitement du fichier
		if ($arraytmp_fichier == null) {
			// Insertion des informations concernant le fichier
			$this->fichier->myInsert($this->dbh, $this->localisation->getId());
		}
		// Récupération de l'id du fichier à retraiter
		$this->fichier->setId($this->fichier->SqlGetId($this->dbh));
		$donneetmp = new Donneetmp();
		$donneetmp->setNomFichier($nomDuFichierBinaire);
		$donneetmp->setErreur($erreurDeLaDonnee);
		// Récupère les données de la table des données en erreur ayant le nom de fichier et le type d'erreur indiqué en paramètre de la fonction
		$contenu_du_fichier = $donneetmp->SqlGetByError($this->dbh);
		// Si le conteu du fichier est vide c'est que la requête à mis trop de temps et a été killée
		if ($contenu_du_fichier == null) {
			$this->log->setLog($this->fichier->getNom().";REIMPORT La requête a mis trop de temps à répondre. Kill de la requête", $this->fichierLog);
		} else {
			// 3) Vérification et Insertion des données sans vérification des doublons	
			$bool_insertion = $this->verifContenu($contenu_du_fichier, false);
			// 4) Si l'insertion sans vérification des doublons a échouée : Vérification et Insertion des données avec vérification des doublons.
			if ($bool_insertion == false) {
				// Recherche avec analyse des doublons
				$bool_insertion = $this->verifContenu($contenu_du_fichier, true);
			}
			// 5) Si l'insertion sans et avec vérification des doublons est en echec, une erreur imprévue est survenue => Log de l'echec
			if ($bool_insertion == false) {
				$this->log->setLog($this->fichier->getNom().";REIMPORT [ERROR CRITIQUE];UE;Erreur non prévue lors de l'importation du fichier - ! Contactez votre administrateur !", $this->fichierLog);
			} else {
				// 8.2 ) Logs des liens module_id - localisation_id non trouvés
				foreach ($this->tab_liens_en_erreurs as $idModuleErreur) {
					$leModule = '';
					$laLocalisation = '';
					// Récupération de l'identifiant du module dans le lien non trouvé
					// Parcours de la liste des modules à la recherche du trigramme
					if (in_array($idModuleErreur, $tab_modules)) {
						$cleDuModule = array_search($idModuleErreur, $tab_modules);
						$pattern = '/^(..)(..)(..)(..)(..)$/';
						if (preg_match($pattern, $cleDuModule, $tabTempModule)) {
							$leModule = $tabTempModule[1].$tabTempModule[2].$tabTempModule[3]."(".$idModuleErreur.")";
						}
					}
					// Recherche de la localisation dans le lien non trouvé
					// Parcours de la liste des localisations à la recherche de la désignation de la localisation
					$laLocalisation = $this->localisation->getDesignation();
					// Ecriture du message de log
					$message = $this->fichier->getNom().";REIMPORT [ERROR];LINK;Le module '".$leModule."' et la localisation '".$laLocalisation."' ne sont pas liés";
					$this->log->setLog($message, 'info');
				}
				// Sinon log de la fin d'importation du fichier
				$this->log->setLog($this->fichier->getNom().";REIMPORT [INFO];;Fin d'importation du fichier ( ".$this->fichier->getNombreVides().' ligne(s) vide(s) - '.$this->fichier->getNombreMessages().' message(s) ) avec '.$this->compteurErreur.' erreur(s), '.$this->compteurReussite.' ligne(s) insérée(s) et '.$this->compteurDoublons.' doublon(s)', $this->fichierLog);
			}
		}
	}
	// Affichage du contenu du fichier de log
	$texte_log = $this->log->rechercheTexte($this->fichierLog, $this->fichier->getNom(), 'lastFicInfo');
	return($texte_log);
}


// Préparation de l'insertion d'une donnée dans la table temporaire ( = table de données en erreur )
public function updateInsertTmp($contenu, $code_erreur) {
	if ($code_erreur != $this->erreurDeLaDonnee) {
		$donneetmp = new Donneetmp();
		$donneetmp->setId($contenu["id"]);
		$donneetmp->setErreur($code_erreur);
		$donneetmp->myUpdateError($this->dbh);
	}
	$this->compteurErreur ++;
}


//   Préparation de l'insertion d'une donnée dans la table finale
public function prepareInsertDonnee($contenu, $verifdoublon) {
	$donnee = new Donnee();
	$donnee->setHorodatage(new \Datetime($contenu["horodatage"]));
	$donnee->setCycle($contenu["cycle"]);
	$donnee->setValeur1($contenu["valeur1"]);
	$donnee->setValeur2($contenu["valeur2"]);
	// Si le lien n'existe pas : Insertion dans le tableau des liens en erreurs
	if (! in_array($this->module->getId(), $this->tab_liens_courants)) {
		if (in_array($this->module->getId(), $this->tab_liens_en_erreurs) == false) {
			$this->tab_liens_en_erreurs[] = $this->module->getId();
		}
	}
	// 1) En cas de vérification de donnée en doublon, une requête est effectué pour chaque donnée
	if ($verifdoublon == true) {
		// Recherche d'une donnée similaire en base de donnée
		$donnee_id = $donnee->checkDoublon($this->dbh, $this->module->getId(), $this->localisation->getId());
		// 1.1)	Si la donnée est en doublon : Mise à jour de la donnée erronée
		if ($donnee_id != null) {
			// Code : Donnée en doublon
			$code_erreur = "DD"; 
			// Incrémentation du compteur des doublons
			$this->compteurDoublons ++;
			$this->updateInsertTmp($contenu, $code_erreur);
		} else {
			// 1.2) Sinon Préparation de la donnée à stocker dans la table des données finales
			$this->compteurReussite ++;
			// Préparation du texte sql d'insertion des données
			$this->liste_insertion .= "('".
				$donnee->getHorodatageStr()."','".
				$donnee->getCycle()."','".
				$donnee->getValeur1()."','".
				$donnee->getValeur2()."','".
				$this->module->getId()."','".
				$this->fichier->getId()."','".
				$this->localisation->getId()."'),";
			// Préparation du texte sql des suppressions des données erronées
			$this->liste_id_donneeserronees .=$contenu["id"].',';
		}
	} else {
		// 2) Si le doublon de la donnée ne doit pas être recherché, : Préparation de la donnée à stocker dans la table des données finales
		$this->compteurReussite ++;
		// Préparation du texte sql d'insertion des données
		$this->liste_insertion .= "('".
			$donnee->getHorodatageStr()."','".
			$donnee->getCycle()."','".
			$donnee->getValeur1()."','".
			$donnee->getValeur2()."','".
			$this->module->getId()."','".
			$this->fichier->getId()."','".
			$this->localisation->getId()."'),";
		// Préparation du texte sql des suppressions des données erronées
		$this->liste_id_donneeserronees .=$contenu["id"].',';
	}
	return(0);
}

// Véfifie le contenu d'un fichier : retourne true si aucune erreur n'est trouvée
public function verifContenu($contenu_du_fichier, $verifdoublon) {
	$bool_verif = true;	
	$this->compteurErreur = 0;
	$this->compteurReussite = 0;
	$this->compteurDoublons	= 0;
	$this->tab_donneestraites = array();
	$this->liste_donneeserreur = "";
	$this->liste_insertion = "";
	$this->liste_id_donneeserronees = "";
	$insert_donnees = null;
	$insert_erreurs = null;
	$insert_total = null;
	// 1) Pour chaque donnée du fichier : (une donnée = une ligne d'information)
	foreach ($contenu_du_fichier as $contenu) {
		$code_erreur = null;
		$genre = new Genre();
		$genre->setNumeroGenre($contenu["numero_genre"]);
		// Tentative de récupèration de l'identifiant du genre dans le tableau des genres analysés
		if (array_key_exists($genre->getNumeroGenre(), $this->tab_genres)) {
			$genre->setId($this->tab_genres[$genre->getNumeroGenre()]);
		} else { 
			// Si il n'y est pas présent, tentative de récupération du genre dans la base de donnée
			$genre->setId($genre->SqlGetId($this->dbh));
			// et insertion d'une nouvelle ligne dans le tableau des genres analysés
			$this->tab_genres[$genre->getNumeroGenre()] = $genre->getId();
		}
		// Si le numéro de genre de la donnée n'est pas en base de données : Une erreur du genre de la donnée est rencontrée
		// 1.2.1) Si une erreur du genre de la donnée est rencontré : Préparation à l'insertion de la donnée dans la table temporaire
		// log de l'erreur
		if ($genre->getId() == null) {
			$code_erreur = 'GNF';
			$this->log->setLog($this->fichier->GetNom().";REIMPORT [ERROR];$code_erreur;Le genre ".$genre->getNumeroGenre()." n'existe pas en base de donnée", $this->fichierLog);
			$this->updateInsertTmp($contenu, $code_erreur);
		} else {
			$mode_id = empty($this->mode)?null:$this->mode->getId();
			// Recherche de l'identifiant du module dont la categorie, les numéros de module et de message et le genre sont ceux indiqués dans la donnée
			$this->module->setId($this->verifModule($genre->getId(),
				$contenu["categorie"],
				$contenu["numero_module"],
				$contenu["numero_message"],
				$mode_id,
				$this->tab_modules
			));
			// 1.2.2) Si une erreur du module est rencontrée : Mise à jour de la table des données erronées
			if ($this->module->getId() == null) {
				$code_erreur = 'DGMNF';
				$this->log->setLog($this->fichier->GetNom().";REIMPORT [ERROR];$code_erreur;".
					"La donnée a un module ou un genre incorrect;".
					$this->assembleDate($contenu["horodatage"]).';'.$contenu["cycle"].';'.
					$this->service_fillNumbers->fillNumber($contenu["numero_genre"], 2).$contenu["categorie"].$this->service_fillNumbers->fillNumber($contenu["numero_module"], 2).$this->service_fillNumbers->fillNumber($contenu["numero_message"], 2),$this->fichierLog
				);
				$this->updateInsertTmp($contenu, $code_erreur);
			} else {
				// 1.2.3) Si aucune erreur est rencontrée dans l'analyse de la donnée : Préparation à l'insertion de la donnée dans la table des données.
				$this->prepareInsertDonnee($contenu, $verifdoublon);
			}
		}
	}
	// 2) Si des données doivent être insérées dans la table des données: insertion
	if ($this->liste_insertion != "") {
		$donnee = new Donnee();
		$this->liste_insertion = substr($this->liste_insertion, 0, -1);
		$insertion = $donnee->myInsert($this->dbh, $this->liste_insertion);
		if ($insertion == null) {
			// Une erreur d'insertion est apparut dans l'insertion des données
			$bool_verif = false;
		} else {
			// Si l'insertion a réussit : Suppression des données de la table donneestmp
			$donneetmp = new DonneeTmp();
			$this->liste_id_donneeserronees = substr($this->liste_id_donneeserronees, 0, -1);
			$suppression = $donneetmp->myDelete($this->dbh, $this->liste_id_donneeserronees);
		}
	}	
	// Retourne le résultat des insertions : false si une des insertions a échouée/true si les insertions sont en succés.
	return($bool_verif);
}

//  Vérifie que le module est présent en base de donnée : Retourne un tableau des paramêtres du modules ou null si aucun module n'est trouvé
public function verifModule($idGenre, $categorie, $numeroModule, $numeroMessage, $modeId, $listeModules) {
	$keyToSearch = $categorie.$this->service_fillNumbers->fillNumber($numeroModule, 2).$this->service_fillNumbers->fillNumber($numeroMessage, 2).$this->service_fillNumbers->fillNumber($idGenre, 2).$this->service_fillNumbers->fillNumber($modeId, 2);
	if (array_key_exists($keyToSearch, $listeModules)) {
		return($listeModules[$keyToSearch]);
	}
	return(null);
}

// Date passée au format 2017-05-02 10:17:36
private function assembleDate($date_a_assembler){
        $pattern_date = '/^(.+?)-(.+?)-(.+?)\s(.+?):(.+?):(.+?)$/';
	if(preg_match($pattern_date, $date_a_assembler, $tab_nouvelle_date)){
		array_shift($tab_nouvelle_date);
		$nouvelle_date = implode($tab_nouvelle_date);
	}
	return($nouvelle_date);
}


}
