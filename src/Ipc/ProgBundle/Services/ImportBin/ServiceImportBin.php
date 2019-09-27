<?php
// src/Ipc/ProgBundle/Services/ImportBin/ServiceImportBin.php
namespace Ipc\ProgBundle\Services\ImportBin;

use Ipc\ProgBundle\Entity\Fichier;
use Ipc\ProgBundle\Entity\Donneetmp;
use Ipc\ProgBundle\Entity\Donnee;
use Ipc\ProgBundle\Entity\Module;
use Ipc\ProgBundle\Entity\Site;
use Ipc\ProgBundle\Entity\Mode;
use Ipc\ProgBundle\Entity\InfosLocalisation;
use Ipc\ProgBundle\Entity\Localisation;
use Ipc\ProgBundle\Entity\Genre;
use Ipc\ProgBundle\Entity\Configuration;

use \PDO;
use \PDOException;

class ServiceImportBin {

protected $fichierLog;
protected $fichierDebug;
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
// Nombre de données correctement insérées
protected $compteurReussite;
// Nombre de donnees incorrectes
protected $compteurErreur;
// Nombre de doublons detectés
protected $compteurDoublons;
protected $annee;
// 1 fichier , 1 Site et 1 Localisation par fichier
protected $fichier;
protected $localisation;
protected $mode;
protected $site;
// 1 Entité Module
protected $module;
// Tableau des liens modules_id localisation_id pour toutes les localisations du site courant
protected $tab_liens_courants;	
protected $tab_liens_en_erreurs;
protected $srv_connexion;
protected $dbh;
protected $em;
protected $debug;

protected $tableau_modules_analyses;
protected $service_fillNumbers;


public function __construct($srv_connexion, $service_fillNumbers) {
    $this->srv_connexion = $srv_connexion;
    $this->dbh = $srv_connexion->getDbh();
	$this->em = $srv_connexion->getManager();

	$this->service_fillNumbers = $service_fillNumbers;
    $configuration = new Configuration();
    date_default_timezone_set($configuration->SqlGetParam($this->dbh, 'timezone'));
    $this->fichier= new Fichier();
    $this->fichier->setDateTraitement(new \Datetime());
	$this->fichierLog = 'importBin.log';
	$this->fichierDebug = 'importBin.csv';
	// Listes des modules_id dont le mode = le mode du fichier binaire en cours d'analyse
	$this->tab_liens_courants = array();
	$this->tab_liens_en_erreurs	= array();
	$this->tab_donneestraites = array();
	$this->tableau_modules_analyses = array();
	$this->tab_modules = array();
	$this->liste_donneeserreur	= "";
	$this->liste_insertion = "";
	$this->compteurErreur = 0;
	$this->compteurReussite = 0;
	$this->compteurDoublons	= 0;
	$this->tab_genres= array();
	$this->site	= new Site();
	// !Lors du passage en 2100 à 00h00 : récupèration du centenaire de l'année courante : 21.
	// Erreur de Lecture des fichiers de 2099 23h.
	$this->localisation	= new Localisation();
	$this->module = new Module();								
	$this->mode	= array();
	// Soustraction des deux premiers chiffres de l'année courante
	$this->annee = substr(date('Y'), 0, 2);
	$this->debug = false;
}

// Retourne le répertoire des logs
protected function getLogDir() 	{ return __DIR__.'/../../../../../web/logs/'; } 


// Fonction de log d'un message
public function setLog($message, $modeMessage) {
	$affiche = true;
	if ($modeMessage == 'debug') {
		if ($this->debug == true) {
			$affiche = true;
		} else {
			$affiche = false;
		}
	}
	if ($affiche == true) {
		if ($modeMessage == 'debug') {
			$ficlog = $this->getLogDir().$this->fichierDebug;
		} else {
			$ficlog = $this->getLogDir().$this->fichierLog;
		}
		$message = date("d/m/Y;H:i:s;").$message."\n";
		$fp = fopen($ficlog, "a");
		fwrite($fp, $message);
		fclose($fp);
	}
}


// Fonction qui vérifie les informations contenues dans le nom du fichier
public function verifFichier() {
	// Code qui indique si le fichier est en erreur ou pas
	$code_erreur = null;
	// Recherche du nom du fichier en base : Si il est présent c'est qu'il a déjà été importé -> Fin de recherche
	$arraytmp_fichier = $this->fichier->SqlGetInfosBase($this->dbh, 1, $this->fichier->getNom());
	// Récupére l'id et la date de traitement du fichier
	if ($arraytmp_fichier != null) {
		$code_erreur = 'FAR';
		$this->setLog($this->fichier->getNom().";[ERROR];$code_erreur;Fichier déjà été importé;Le ".$arraytmp_fichier['date_traitement'], 'info');
	}
	$this->setLog("Vérification de fichier : Nom de fichier ok",'debug');
	if ($code_erreur == null) {
		// Si le mode du fichier est indiqué dans le nom du fichier mais n'existe pas en base pas d'importation
		$pattern = '/^(.+?)_(.+?)_#(.+?)#_(.+?)\..*.bin$/';
		if (preg_match($pattern, $this->fichier->getNom(), $contenu)) {
			$affaire = $contenu[1];
			$numero_localisation = $contenu[2];
			$mode_designation = $contenu[3];
			$date_fichier = $contenu[4];
			$this->mode	= $this->em->getRepository('IpcProgBundle:Mode')->findOneByDesignation($mode_designation);
			if (empty($this->mode)) {
				$code_erreur = 'MNF';
				$this->setLog($this->fichier->getNom().";[ERROR];$code_erreur;Mode $mode_designation non trouvé en base", 'info');
			}
		} else {
			// Récupération des trois informations contenues dans le nom du fichier : Le Site - La Localisation - La date
			// Si les informations ne sont pas accessibles c'est que le fichier est mal nommé -> Fin de recherche
			$pattern = '/^(.+?)_(.+?)_(.+?)\..*.bin$/';
			if (! preg_match($pattern, $this->fichier->getNom(), $contenu)) {
				// Erreur : Fichier  - Wrong Name
				$code_erreur = 'FWN';
				$this->setLog($this->fichier->getNom().";[ERROR];$code_erreur;Nom du fichier incorrect", 'info');
			} else {
				$affaire = $contenu[1];
				$numero_localisation = $contenu[2];
				$date_fichier = $contenu[3];
			}
			// Si aucun mode défini pour le fichier alors nous somme en fonctionnement 'noprog' (sans programme)
			$this->mode = $this->em->getRepository('IpcProgBundle:Mode')->findOneByDesignation('noprog');
			if (empty($this->mode)) {
				$code_erreur = 'MNF';
				$this->setLog($this->fichier->getNom().";REIMPORT [ERROR];$code_erreur;Mode 'noprog' non trouvé en base",'info');
			}
		}
	}
	$this->setLog("Vérification de fichier : Nomenclature 1 ok", 'debug');
	if ($code_erreur == null) {
		// Recherche du site en base dont l'affaire est celle désignée dans le nom du fichier
		$this->site = $this->em->getRepository('IpcProgBundle:Site')->findOneByAffaire($affaire);
		if (empty($this->site)) {
			$code_erreur = 'SNF';
			$this->setLog($this->fichier->getNom().";[ERROR];$code_erreur $affaire;Le site n'est pas enregistré en base de donnée;$affaire",'info');
		}
	}
	$this->setLog("Vérification de fichier : Site ok",'debug');
	//	Recherche de la localisation dont le numéro est celui indiqué dans le nom du fichier et dont le site est celui récupéré en base gràce à l'intitulé de l'affaire indiquée dans le nom du fichier
	if ($code_erreur == null) {
		$this->localisation = $this->em->getRepository('IpcProgBundle:Localisation')->findOneBy(array('site' => $this->site,'numeroLocalisation' => $numero_localisation));
		if (empty($this->localisation)) {
			$code_erreur = 'LNF';
			$this->setLog($this->fichier->getNom().";[ERROR];$code_erreur $affaire [$numero_localisation];La localisation du site n'est pas enregistrée en base de donnée;$affaire [$numero_localisation]",'info');
		}
	}
	$this->setLog("Vérification de fichier : Localisations ok",'debug');
	// Si un mode est désigné dans le nom du fichier, vérification que ce mode existe pour la localisation du fichier
	if ($code_erreur == null) {
		if (! empty($this->mode)) {
			$infosLocalisation = new InfosLocalisation();
			$infosLocalisationId = $infosLocalisation->sqlCheckLink($this->dbh, $this->mode->getId(), $this->localisation->getId());
			if ($infosLocalisationId == null) {
				$code_erreur = 'MNF'; 
				$this->setLog($this->fichier->getNom().";[ERROR];$code_erreur;Mode non trouvé pour cette localisation", 'info');
			}
		}
	}
	$this->setLog("Vérification de fichier : Mode ok", 'debug');
	if ($code_erreur == null) {
		// Vérification de format de la date indiquée dans le nom du fichier
		// Si elle est incorrecte -> L'enregistrement de la donnée se fera dans la table des données temporaires
		$pattern = '/^(\d{2})(\d{2})(\d{2})_(\d{2})-(\d{2})-(\d{2})/';
		if (! preg_match($pattern, $date_fichier, $contenus_horodatage)) {
			$code_erreur = 'FWT';
			$this->setLog($this->fichier->getNom().";[ERROR];$code_erreur;L'horodatage est incorrectement formaté;$date_fichier", 'info');
		}
	}
	$this->setLog("Vérification de fichier : Horodatage ok",'debug');
	if ($code_erreur != null) {
		// Si une erreur s'est produite dans la vérification des données contenus dans le nom du fichier :
		// Déplacement du fichier dans le dossier des fichiers en erreur
		// Suppression du fichier binaire afin de pouvoir traiter le prochain fichier binaire de la liste
		$this->fichier->moveToError();
		$this->fichier->deleteBinary();
		return('exit');
	}
	if (! empty($this->mode)) {
		$liste_module       = $this->em->getRepository('IpcProgBundle:Module')->findBy(array('mode'=>$this->mode));
	} else {
		$liste_module       = $this->em->getRepository('IpcProgBundle:Module')->findBy(array('mode'=>null));
	}
	foreach ($liste_module as $module) {
		$this->tab_liens_courants[] = $module->getId();
	}
	return(null);
}

// Importe les fichiers binaires du dossier fichiers_binaire
// Prend en argument le fichier à insérer en base de donnée
public function importation($nomDuFichierBinaire, $tab_modules, $tab_localisations) {
	$this->dbh = $this->srv_connexion->connect();
	// Vérification qu'un flag de téléchargement Ftp n'existe pas avant de lancer le téléchargement
	$flagImportBinaire = "/tmp/.flagSymfonyImportBinaires";
	if (file_exists($flagImportBinaire)) {
		$this->setLog($this->fichier->getNom().";[INFO];L'importation de fichiers binaires est déjà en cours d'execution", 'info');
		$this->dbh = $this->srv_connexion->disconnect();
		return(-1);
	} else {
		$this->setLog($this->fichier->getNom().";Debut Importation", 'debug');
		// Création du flag indiquant qu'une importation est en cours : Permet d'éviter le traitement en doublon des fichiers
		$commande = "touch $flagImportBinaire";
		exec($commande);
		$commande = "chmod 666 $flagImportBinaire";
		exec($commande);
		//$commande = "chown wwwrun $flagImportBinaire";
		//exec($commande);
		// Suppression de la limite de temps d'execution du script car il fait plus de 300 secondes d'execution
		set_time_limit(0); 
		ignore_user_abort(1);
		$code_erreur = null;
		$this->tab_modules = $tab_modules;
		$this->fichier->setNom($nomDuFichierBinaire);
		// 1) Vérification des informations du fichier : Affaire / N°Localisation / Horodatage / Programme
		$code_erreur = $this->verifFichier($this->dbh, $this->fichier->getNom());
		$this->setLog("Vérification de fichier ok",'debug');
		// Si le code erreur est exit c'est qu'une erreur sur le fichier est trouvée => fin d'importation
		if ($code_erreur == 'exit') {
			// Libération du flag
			$commande = "rm $flagImportBinaire";
			exec($commande);
			return(1);
		}
		// 2) Si les informations du fichier sont correctes 
		// Récupération de l'id du futur fichier qui sera inséré
		$this->fichier->setId($this->fichier->SqlGetNextId($this->dbh));
		$this->setLog("Récupération du nouvel id de fichier",'debug');
		// 3) Récupération des données du fichier : Retourne un tableau vide si aucune donnée
		// -> Instancie les paramètres du fichier : nombreVides et nombreMessages
		$contenu_du_fichier = $this->fichier->getContenu();
		$this->setLog("Récupération de contenu",'debug');
		// 4) Si le nombre de données est > 0 on enregistre le dernier timestamp en base pour la localisation du fichier courant
		if (count($contenu_du_fichier) > 0) {
			$dernier_horodatage_de_localisation = $this->annee.$contenu_du_fichier[count($contenu_du_fichier)-1]["datedonnee"];
			if ($this->verifDate($dernier_horodatage_de_localisation) == true) {
				//$this->localisation->SqlUpdateLastHorodatage($this->dbh, new \Datetime($dernier_horodatage_de_localisation));
				$this->em->getRepository('IpcProgBundle:Localisation')->SqlUpdateLastHorodatage($this->dbh, $this->localisation, new \Datetime($dernier_horodatage_de_localisation));
			}
		}


		// 5) Vérification et Insertion des données sans vérification des doublons	
		// Tentative d'insertion des données sans vérifications : Retourne false si des erreurs sont rencontrées
		$bool_insertion = $this->verifContenu($contenu_du_fichier, false);
		$this->setLog("Insertion sans doublon", 'debug');
		// Si le fichier est détecté comme étant corrompu il est déplacé dans le dossier des fichiers en erreur et l'insertion est annulée
		if ((gettype($bool_insertion) == 'string') && ($bool_insertion == 'CORROMPU')) {
			$this->fichier->moveToError();
			$this->fichier->deleteBinary();
			$this->setLog($this->fichier->getNom().";[ERROR];CORROMPU;Fichier corrompu - Une erreur dans un horodatage de donnée a été détectée.",'info');
			// Libération du flag
			$commande = "rm $flagImportBinaire";
			exec($commande);
			return(1);
		}
		// 6) Si l'insertion sans vérification des doublons a échouée : Vérification et Insertion des données avec vérification des doublons.
		if ($bool_insertion == false) {
			// Recherche avec analyse des doublons
			$bool_insertion = $this->verifContenu($contenu_du_fichier, true);	
		}
		$this->setLog("Insertion avec doublon", 'debug');
		// 7) Si l'insertion sans et avec vérification des doublons est en échec, une erreur imprévue est survenue => Log de l'echec
		if ($bool_insertion == false) {
			// 7.1 ) 
			// Déplacement du fichier dans le dossier des fichier en erreur
			$this->fichier->moveToError();
			// 7.2 ) 
			$this->setLog($this->fichier->getNom().";[ERROR CRITIQUE];UE;Erreur non prévue lors de l'importation du fichier - ! Contactez votre administrateur !",'info');
		} else {
			// 8 )
			// 8.1 ) Insertion des données concernant le fichier en base
			$this->fichier->myInsert($this->dbh, $this->localisation->getId());
			// 8.2 ) Logs des liens module_id - localisation_id non trouvés
			foreach ($this->tab_liens_en_erreurs as $idModuleErreur) {
				$leModule = '';
				$laLocalisation = '';
				// Récupération de l'identifiant du module dans le lien non trouvé
				// Parcours de la liste des modules à la recherche du trigramme
				if (in_array($idModuleErreur, $tab_modules)) {
					$cleDuModule = array_search($idModuleErreur, $tab_modules);
					$pattern ='/^(..)(..)(..)(..)(..)$/';
					if (preg_match($pattern, $cleDuModule, $tabTempModule)) {
						$leModule = $tabTempModule[1].$tabTempModule[2].$tabTempModule[3]."(".$idModuleErreur.")";
					}
				}
				// Recherche de la localisation dans le lien non trouvé
				// Parcours de la liste des localisations à la recherche de la désignation de la localisation
				$laLocalisation = $this->localisation->getDesignation();
				// Ecriture du message de log
				$message = $this->fichier->getNom().";[ERROR];LINK;Le module '".$leModule."' ($idModuleErreur) et la localisation '".$laLocalisation."' (".$this->localisation->getId().") ne sont pas liés";
				$this->setLog($message,'info');
			}
			// 8.3 ) Déplacement du fichier dans le dossier des fichiers traités
			$this->fichier->moveToProcessed(); 
			// 8.4 )  log de la fin d'importation du fichier
			$this->setLog($this->fichier->getNom().";[INFO];;Fin d'importation du fichier ( ".$this->fichier->getNombreVides().' ligne(s) vide(s) - '.$this->fichier->getNombreMessages().' message(s) ) avec '.$this->compteurErreur.' erreur(s), '.$this->compteurReussite.' ligne(s) insérée(s) et '.$this->compteurDoublons.' doublon(s)','info');
		}
		// 9 ) Suppression du fichier binaire
		$this->fichier->deleteBinary(); 
		// 10 ) Libération du flag
		$commande = "rm $flagImportBinaire";
		exec($commande);
		$this->setLog("Fin de service",'debug');
		$this->dbh = $this->srv_connexion->disconnect();
		return(0);
	}
}


// bis1 )	Préparation de l'insertion d'une donnée dans la table temporaire ( = table de données en erreur )
public function prepareInsertTmp($contenu, $code_erreur, $verifdoublon) {
	// bis1.1 )	Création de l'entité donneetmp à insérer en base
	$donneetmp = new Donneetmp();
	// bis1.2 )	Initialisation du paramètre nomDeFichier
	$donneetmp->setNomFichier($this->fichier->getNom());
	// bis1.3 ) 	Vérification de la nomenclature de la date de la donnée
	if ($this->verifDate($this->annee.$contenu["datedonnee"]) == false) {
		// Si une erreur sur une date est rencontrée, le fichier est considéré comme corrompu
		return(false);
	}
	// bis1.4 )
	$donneetmp->setHorodatage(new \Datetime($this->annee.$contenu["datedonnee"]));
	$donneetmp->setCycle($contenu["nu_cycle"]);
	$donneetmp->setValeur1($contenu["valeur1"]);
	$donneetmp->setValeur2($contenu["valeur2"]);
	$donneetmp->setCategorie($contenu["ty_categorie"]);
	$donneetmp->setNumeroModule($contenu["nu_module"]);
	$donneetmp->setNumeroMessage($contenu["nu_message"]);
	$donneetmp->setNumeroGenre($contenu["nu_genre"]);
	$donneetmp->setAffaire($this->site->getAffaire());
	$donneetmp->setNumeroLocalisation($this->localisation->getNumeroLocalisation());
	$donneetmp->setErreur($code_erreur);
	// bis1.5 ) 
	if (! empty($this->mode)) {
		$donneetmp->setProgramme($this->mode->getDesignation());
	}
	// bis1.6) En cas de vérification de donnée en doublon, une recherche d'une donneetmp identique en base est effectuée
	if ($verifdoublon == true) {
		// bis1.6.1)
		$donneetmp_id = $donneetmp->checkDoublon($this->dbh);
		// bis1.6.2 ) Si aucun doublon n'est trouvé : Préparation de l'enregistrement de la donnée dans la table des données temporaires
		if ($donneetmp_id == null) {
			$this->compteurErreur ++;
			$this->liste_donneeserreur .= "('".
				$donneetmp->getErreur()."','".
				$donneetmp->getHorodatageStr()."','".
				$donneetmp->getCycle()."','".
				$donneetmp->getValeur1()."','".
				$donneetmp->getValeur2()."','".
				$donneetmp->getNumeroGenre()."','".
				$donneetmp->getCategorie()."','".
				$donneetmp->getNumeroModule()."','".
				$donneetmp->getNumeroMessage()."','".
				$donneetmp->getNomFichier()."','".
				$donneetmp->getAffaire()."','".
				$donneetmp->getProgramme()."','".
				$donneetmp->getNumeroLocalisation()."'),";
		} else {
			// bis1.6.3 ) !!! Si un doublon d'une donnée erroné est trouvé : La donnée n'est pas stockée en base !!! 
			// Log du doublon
			$ligne = 'Heure: '.$donneetmp->getHorodatageStr().':'.$donneetmp->getCycle().' [ Valeurs: '.$donneetmp->getValeur1().' - '.$donneetmp->getValeur2().' ] Code: '.$donneetmp->getCategorie().$donneetmp->getNumeroModule().$donneetmp->getNumeroMessage().' Genre: '.$donneetmp->getNumeroGenre().' Site: '.$donneetmp->getAffaire().' Localisation: '.$donneetmp->getNumeroLocalisation();
			$this->setLog($this->fichier->GetNom().";[ERROR];DD;Un doublon est détecté dans le tableau des données en erreur !! Pas d'enregistrement effectué pour la ligne : $ligne - !! Contactez votre administrateur !! ",'info');
		}
	} else {
		// bis1.7 ) Si la recherche de doublons n'est pas demandée : Préparation de l'enregistrement de la donnée dans la table des données temporaires
		$this->compteurErreur ++;
		$this->liste_donneeserreur .= "('".
			$donneetmp->getErreur()."','".
			$donneetmp->getHorodatageStr()."','".
			$donneetmp->getCycle()."','".
			$donneetmp->getValeur1()."','".
			$donneetmp->getValeur2()."','".
			$donneetmp->getNumeroGenre()."','".
			$donneetmp->getCategorie()."','".
			$donneetmp->getNumeroModule()."','".
			$donneetmp->getNumeroMessage()."','".
			$donneetmp->getNomFichier()."','".
			$donneetmp->getAffaire()."','".
			$donneetmp->getProgramme()."','".
			$donneetmp->getNumeroLocalisation()."'),";
	}
	return(true);
}


// bis2 ) Préparation de l'insertion d'une donnée dans la table finale
public function prepareInsertDonnee($contenu, $verifdoublon) {
	$donnee = new Donnee();
	// bis2.2 ) Vérification de la nomenclature de la date de la donnée
	// 	Si une erreur sur une date est rencontrée, le fichier est considéré comme corrompu
	if ($this->verifDate($this->annee.$contenu["datedonnee"]) == false) {
		return(false);
	}
	$donnee->setHorodatage(new \Datetime($this->annee.$contenu["datedonnee"]));
	$donnee->setCycle($contenu["nu_cycle"]);
	$donnee->setValeur1($contenu["valeur1"]);
	$donnee->setValeur2($contenu["valeur2"]);
	// bis2.3 ) Si le module de la donnée n'est pas trouvé dans le tableau des modules du mode du fichier binaire : Insertion du module_id dans le tableau des liens en erreurs
	if (! in_array($this->module->getId(), $this->tab_liens_courants)) {
		if (in_array($this->module->getId(), $this->tab_liens_en_erreurs) == false) {
			$this->tab_liens_en_erreurs[] = $this->module->getId();
		}
	}
	// bis2.4 ) En cas de vérification de donnée en doublon, une requête est effectuée pour chaque donnée
	if ($verifdoublon == true) {
		// Recherche d'une donnée similaire en base de donnée
		// 1.1) Si la donnée est en doublon : Enregistrement dans la table des données temporaires avec vérification de doublons + Création d'une ligne de log
		// 1.2) Sinon Préparation de la donnée à stocker dans la table des données finales + Vérification du liens modules - localisation
		$donnee_id = $donnee->checkDoublon($this->dbh, $this->module->getId(), $this->localisation->getId());
		if ($donnee_id != null) {
			// 1.1
			$code_erreur = "DD";
			$this->compteurDoublons ++;	
			$this->prepareInsertTmp($contenu, $code_erreur, true);
			$ligne = 'ModuleId: '.$this->module->getId().' LocalisationId: '.$this->localisation->getId().' Heure:'.$donnee->getHorodatageStr().':'.$donnee->getCycle().' [ Valeurs: '.$donnee->getValeur1().' - '.$donnee->getValeur2().' ] ';
			$this->setLog($this->fichier->GetNom().";[ERROR];DD;Doublon détecté pour la donnée : $ligne", 'info');
		} else {
			// 1.2
			$this->compteurReussite ++;
			$this->liste_insertion .= "('".
				$donnee->getHorodatageStr()."','".
				$donnee->getCycle()."','".
				$donnee->getValeur1()."','".
				$donnee->getValeur2()."','".
				$this->module->getId()."','".
				$this->fichier->getId()."','".
				$this->localisation->getId()."'),";
			$this->verification_liens_localisations_modules(); 
		}
	} else {
		// bis2.5 ) Si le doublon de la donnée ne doit pas être recherché : Préparation de la donnée à stocker dans la table des données finales
		$this->compteurReussite ++;
		$this->liste_insertion .= "('".
			$donnee->getHorodatageStr()."','".
			$donnee->getCycle()."','".
			$donnee->getValeur1()."','".
			$donnee->getValeur2()."','".
			$this->module->getId()."','".
			$this->fichier->getId()."','".
			$this->localisation->getId()."'),";
		$this->verification_liens_localisations_modules();
	}
	// bis2.6 )
	return(true);
}




// Véfifie le contenu d'un fichier et insert les données en base : retourne true si aucune erreur n'est trouvée
public function verifContenu($contenu_du_fichier, $verifdoublon) {
	// Retour de la fonction : false si aucune erreur
	$bool_verif = true;	
	$this->compteurErreur = 0;
	$this->compteurReussite	= 0;
	$this->compteurDoublons	= 0;
	$this->tab_donneestraites = array();
	$this->liste_donneeserreur = "";
	$this->liste_insertion = "";
	$insert_donnees = null;
	$insert_erreurs = null;
	$insert_total = null;
	// 5.1)	Pour chaque donnée du fichier (une donnée = une ligne d'information)
	foreach($contenu_du_fichier as $contenu) {
		$this->setLog("[DEBUG];DEBUG;".implode(';',$contenu), 'debug');
		$code_erreur = null;
		// 5.1.1) Si les informations du fichier sont correctes :
		// Vérification des informations de la donnée : Genre / Module ( avec Module = Categorie+N°Module+N°Message )
		// infos : Création d'un tableau des nouveaux genres rencontrés afin de ne pas surcharger les requêtes faites vers la base de données
		$genre = new Genre();
		$genre->setNumeroGenre($contenu["nu_genre"]);
		// Tentative de récupèration de l'identifiant du genre dans le tableau des genres analysés
		if (array_key_exists($genre->getNumeroGenre(), $this->tab_genres)) {
			$genre->setId($this->tab_genres[$genre->getNumeroGenre()]);
		} else {
			// Si il n'y est pas présent, tentative de récupération du genre dans la base de donnée
			$genre->setId($genre->SqlGetId($this->dbh));
			// et insertion d'une nouvelle ligne dans le tableau des genres analysés
			$this->tab_genres[$genre->getNumeroGenre()] = $genre->getId();
		}
		// Si le numéro de genre de la données n'est pas en base de données : Une erreur du genre de la donnée est rencontré
		// Si une erreur du genre de la donnée est rencontré : Préparation à l'insertion de la donnée dans la table temporaire
		// Log de l'erreur
		if ($genre->getId() == null) {
			$code_erreur = 'GNF';
			$this->setLog($this->fichier->GetNom().";[ERROR];$code_erreur;Le genre ".$genre->getNumeroGenre()." n'existe pas en base de donnée", 'info');
			if ($this->prepareInsertTmp($contenu, $code_erreur, $verifdoublon) == false) {
				$this->setLog("[DEBUG];DEBUG;Corrompu 1", 'debug');
				return('CORROMPU');
			}
		} else {
			// 5.1.2 ) Recherche de l'identifiant du module dont la categorie, les numéros de module et de message et le genre sont ceux indiqués dans la donnée
			// Récupération de l'id du module 
			$tmp_idMode = null;
			if (! empty($this->mode)) {
				$tmp_idMode = $this->mode->getId();
			}
			$this->module->setId($this->verifModule(
									$genre->getId(),
									$contenu["ty_categorie"],
									$contenu["nu_module"],
									$contenu["nu_message"],
									$tmp_idMode,
									$this->tab_modules));
			// Si aucun module n'est trouvé en base avec les paramètres donnés : Préparation à l'insertion de la données dans la table temporaire
			// Log de l'erreur
			if ($this->module->getId() == null) {
				$code_erreur = 'DGMNF';
				$this->setLog($this->fichier->GetNom().";[ERROR];$code_erreur;".
					"La donnée a un module ou un genre incorrect;".
					$this->annee.
					$contenu["datedonnee"].'.'.
					$contenu["nu_cycle"].
					' ['.
					$contenu["nu_genre"].$contenu["ty_categorie"].$contenu["nu_module"].$contenu["nu_message"].
					']', 'info'
				);
				if ($this->prepareInsertTmp($contenu, $code_erreur, $verifdoublon) == false) {
					$this->setLog("[DEBUG];DEBUG;Corrompu 2", 'debug');
					return('CORROMPU');
				}
			} else {
				// 5.1.3 ) Si aucune erreur est rencontrée dans l'analyse de la donnée : Préparation à l'insertion de la donnée dans la table des données.
				if ($this->prepareInsertDonnee($contenu, $verifdoublon) == false) {
					$this->setLog("[DEBUG];DEBUG;Corrompu 3", 'debug');
					return('CORROMPU');
				}
			}
		}
	}
	// 5.2 ) Commence une transaction, désactivation de l'auto-commit 
	$this->dbh->beginTransaction();
	// 5.3 ) Si des données doivent être insérées dans la table des données temporaires : insertion
	// Insertion des données dont les informations du fichier sont erronées
	if ($this->liste_donneeserreur != "") {	
		$donneetmp = new DonneeTmp();
		// Insertion des données en base de donnée : Données en erreur
		$this->liste_donneeserreur = substr($this->liste_donneeserreur,0,-1);
		// Vérification et suppression des doublons dans la liste
		// 5.3.1 ) Suppression des doublons dans la requête des données erronées
		$this->liste_donneeserreur = $this->verifListeDoublon($this->liste_donneeserreur);
		// 5.3.2 ) Requête d'insertion
		$insertion = $donneetmp->myInsert($this->dbh, $this->liste_donneeserreur);
		if ($insertion == null) {
			// Une erreur d'insertion est apparut dans l'insertion des données
			$this->setLog("[DEBUG];DEBUG;Error insertion datatmp", 'debug');
			$this->setLog("[DEBUG];REQUEST;INSERT INTO t_donneetmp ( erreur, horodatage, cycle, valeur1, valeur2, numero_genre, categorie, numero_module, numero_message, nom_fichier, affaire, programme, numero_localisation ) VALUES ".$this->liste_donneeserreur, 'debug');
			$bool_verif = false;
		}
	}
	// 5.4 ) Si des données doivent être insérées dans la table des données : insertion
	if ($this->liste_insertion != "") {
		$donnee = new Donnee();
		// Supression de la virgule en fin de liste
		$this->liste_insertion = substr($this->liste_insertion, 0, -1);
		// 5.4.1 ) Vérification et suppression des doublons dans la liste
		$this->liste_insertion = $this->verifListeDoublon($this->liste_insertion);
		// 5.4.2 ) Insertion des données en base de donnée
		$insertion = $donnee->myInsert($this->dbh, $this->liste_insertion);
		// Si une erreur d'insertion est apparut dans l'insertion des données
		if ($insertion == null) {
			$this->setLog("[DEBUG];DEBUG;Error insertion data", 'debug');
			$bool_verif = false;
		}
	}
	// 5.5 ) Si aucune erreur d'insertion n'a eu lieu : Commit des requêtes sinon RollBack
	if ($bool_verif == true) {
		$this->dbh->commit();
		//	Enregistrement des nouvelles valeurs des champs 'hasDonnees' des modules
		$this->em->flush();

        // Récupération de l'id de la derniere valeur insérée en base.
        $donnee = new Donnee();
        $max_insert_id = $donnee->getMaxId($this->dbh);
        $parametre_last_insert_id = 'localisation_'.$this->localisation->getId()."_last_id";
        $entity_config_last_id = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre($parametre_last_insert_id);
        if (! $entity_config_last_id){
            $entity_configuration = new Configuration();
            $entity_configuration->setParametre($parametre_last_insert_id);
            $entity_configuration->setDesignation('Identifiant de la dernières donnée insérée pour la localisation '.$this->localisation->getNumeroLocalisation());
            $entity_configuration->setValeur($max_insert_id);
            $entity_configuration->setParametreAdmin(true);
            $this->em->persist($entity_configuration);
        } else {
            $entity_config_last_id->setValeur($max_insert_id);
        }
        //  Enregistrement de la nouvelle valeur du parametre last_insert_id de la localisation
        $this->em->flush();
	} else {
		$this->dbh->rollback();	
	}
	//	Retourne le résultat des insertions : false si une des insertions a échouée/true si les insertions sont en succés.
	return($bool_verif);
}

// Fonction qui recoit une chaine de caractère représentant les données à insérer, vérifie qu aucun doublon n est présent dans la liste et retourne une chaine sans les doublons
protected function verifListeDoublon($liste_initiale) {
	$liste_finale = "";
	$liste_tmp = "";
	// création d'un tableau comportant les différentes données
	$tab_list = explode(')',$liste_initiale);
	// ajout d'une virgule sur le premier champs pour qu'il soit identique aux champs suivants
	$tab_list[0] = ','.$tab_list[0]; 
	// création d'un tableau unique
	$tab_unique	= array_unique($tab_list);
	// Si le nombre de champs du tableau unique et différent du nombre de champs du tableau initiale, retour de la requête basée sur le tableau unique
	if (count($tab_list) != count($tab_unique)) {
		// retire la virgule en début de requete
		$tab_unique[0] = substr($tab_unique[0],1);
		$liste_finale = implode(')',$tab_unique);
	} else {
		$liste_finale = $liste_initiale;
	}
	return($liste_finale);
}

// Vérifie que le module est présent en base de donnée : Retourne un tableau des paramêtres du modules ou null si aucun module n'est trouvé
public function verifModule($idGenre, $categorie, $numeroModule, $numeroMessage, $modeId, $listeModules) {
	$keyToSearch = $categorie.$numeroModule.$numeroMessage.$this->service_fillNumbers->fillNumber($idGenre,2).$this->service_fillNumbers->fillNumber($modeId,2);
	if (array_key_exists($keyToSearch, $listeModules)) {
		return($listeModules[$keyToSearch]);
	}
	return(null);
}

// Fonction qui récupére une date au format AAAAMMJJHHmmss et indique si elle est correcte
protected function verifDate($date) {
	$pattern = '/^(....)(..)(..)(..)(..)(..)$/';
	if (preg_match($pattern, $date, $tabDate)) {
		$annee = $tabDate[1];
		$mois = $tabDate[2];
		$jour = $tabDate[3];
		$heure = $tabDate[4];
		$minute = $tabDate[5];
		$seconde =$tabDate[6];
		if (checkdate($mois, $jour, $annee) == false) {
			$this->setLog($this->fichier->GetNom().";[INFO];Erreur dans la date de la donnée [$date]", 'info');
			return(false);
		}
		if (($heure > 23) || ($minute > 59) || ($seconde > 59)) {
			$this->setLog($this->fichier->GetNom().";[INFO];Erreur dans l'horodatage de la donnée [$date]", 'info');
			return(false);
		}
	} else {
		$this->setLog($this->fichier->GetNom().";[INFO];Erreur dans le format de l'horodatage de la donnée [$date]", 'info');
		return(false);
	}
	return(true);
}

public function setNewLink($mode_id_link) {
	$mode_link = $this->em->getRepository('IpcProgBundle:Mode')->find($mode_id_link);
	$this->setLog("New Link : Récupération mode",'debug');
	// Si aucune erreur s'est produite dans la vérification des informations contenu dans le nom du fichier
	// Récupération des modules liés au mode
	$liste_module = $this->em->getRepository('IpcProgBundle:Module')->findBy(array('mode' => $mode_link));
	$this->setLog("New Link : Récupération des nouveaux liens",'debug');
	$tabLiens = array();
	foreach ($liste_module as $module) {
		$tabLiens[] = $module->getId();
	}
	$this->setLog("New Link : Création du tableau des liens ok",'debug');
	// Enregistrement en base des liens modules/localisation
	$this->setLog("New Link : Enregistrement en base","debug");
	return($tabLiens);
}


private function verification_liens_localisations_modules(){
	// Si le module a déjà été analyser on le ré-analyse pas
	if (! in_array($this->module->getId(), $this->tableau_modules_analyses)) {
		$entity_module = $this->em->getRepository('IpcProgBundle:Module')->find($this->module->getId());
		// Si l'entité a sa propriétés hasDonnées à false, on la passe à true
    	if ($entity_module->getHasDonnees() == false) {
			$entity_module->setHasDonnees(true);
		}
		$this->tableau_modules_analyses[] = $this->module->getId();
	}
}

}
