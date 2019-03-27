<?php
namespace Ipc\SupervisionBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Ipc\ConfigurationBundle\Entity\ConfigModbus;
use Ipc\ProgBundle\Entity\Site;
use Ipc\ProgBundle\Entity\Localisation;
use Ipc\ProgBundle\Entity\Donnee;
use Ipc\ProgBundle\Entity\Fichier;

class SupervisionController extends Controller {

private $session;
private $code_defauts;
private $code_alarmes;
private $code_evenements;
private $evenement_defauts;
private $evenement_alarmes;
private $evenement_evenements;
private $nombre_liste_evenements;
private $nombre_mois_evenements;
private $tab_couleur_genres;
private $em;
private $activation_modbus;
private $flagSessionLive = '/tmp/.flagSymfonySessionLive';
private $tabZoom;
private $last_time_evenement = null;
private $fichier_log = 'system.log';
private $sessionAside;
private $initialisation = false;

public function constructeur() {
	$this->em = $this->get('ipc_prog.connectbd')->getManager();
}

public function initialisation() {
	$this->constructeur();

	if ($this->initialisation == false) {
		$this->initialisation = true;
		$this->session = $this->getRequest()->getSession();
		$this->code_defauts	= 1;
		$this->evenement_defauts = 'derniers défauts';
		$this->code_alarmes = 2;
		$this->evenement_alarmes = 'dernières alarmes';
		$this->code_evenements = 3;
		$this->evenement_evenements	= 'derniers évènements';
		$this->nombre_mois_evenements  = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('live_nb_mois')->getValeur();
		$this->nombre_liste_evenements = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('live_nb_evenements')->getValeur();
		$this->activation_modbus = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('activation_modbus')->getValeur();
		$this->tabZoom = $this->session->get('tabZoom', array());
		$this->sessionAside = $this->session->get('sessionAside', false);
	
		if(empty($this->tabZoom) || ($this->tabZoom['debut'] === null ) || ($this->tabZoom['fin'] === null)) {
			$this->initialisationTabZoom();
		}
		// Récupération du tableau des codes couleurs des genres
		$entities_genres = $this->em->getRepository('IpcProgBundle:Genre')->findAll();
		$tab_couleur_genres	= array();
		foreach ($entities_genres as $entity_genre) {
			$tab_couleur_genres[$entity_genre->getNumeroGenre()] = $entity_genre->getCouleur(); 
			if (($entity_genre->getIntituleGenre() == 'Défaut') && ($entity_genre->getNumeroGenre() != $this->code_defauts)) {
				$this->get("session")->getFlashBag()->add('info', "Erreur : Le genre Défaut n'a pas le numéro ".$this->code_defauts.': Veuillez revoir le genre');
				return(1);
			}
			if (($entity_genre->getIntituleGenre() == 'Alarme') && ($entity_genre->getNumeroGenre() != $this->code_alarmes)) {
				$this->get("session")->getFlashBag()->add('info', "Erreur : Le genre Alarme n'a pas le numéro ".$this->code_alarmes.': Veuillez revoir le genre');
				return(1);
			}
			if (($entity_genre->getIntituleGenre() == 'Etat') && ($entity_genre->getNumeroGenre() != $this->code_evenements)) {
				$this->get("session")->getFlashBag()->add('info', "Erreur : Le genre Etat n'a pas le numéro ".$this->code_evenements.': Veuillez revoir le genre');
				return(1);
			}
		}
		$this->tab_couleur_genres = $tab_couleur_genres;
	}
	return(0);
}

public function accueilAction() {
	$this->constructeur();

	$timezone = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('timezone');
	if ($timezone != null) {
		date_default_timezone_set($timezone->getValeur());
	}
	setlocale (LC_TIME, 'fr_FR.utf8','fra');
	//	Création du flag indiquant qu'une session est ouverte si il n'en n'existe pas déjà un.
	if (! file_exists($this->flagSessionLive)) {
		file_put_contents($this->flagSessionLive, time());
	}
	$identifiantLive = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('live_url')->getValeur();
	$liveRefreshListing	= $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('live_refresh_listing')->getValeur();
	$liveRefreshGraphique = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('live_refresh_graphique')->getValeur();
	$timeoutAutomate = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('live_timeout_automate')->getValeur();
	$ping_timeout = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('ping_timeout')->getValeur();
	$ping_intervalle = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('ping_intervalle')->getValeur();

	$this->tabLiveEnTetes = array();
	$this->initialisation();
	// Variables utilisées pour l'affichage des graphiques Live 
	// A placer dans des variables de session pour amélioration de l'interface
	// *************************************	PARAMETRES DE LA FONCTION 	****************************************
	// Recherche des points sur les X ( $nombre_mois ) derniers mois
	// Recherche des défauts sur les Y ( $this->nombre_mois_evenements ) derniers mois
	// Recherche d'un maximum de Z ( $limit_sql_messages ) points
	$nombre_mois = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('live_graph_nb_mois')->getValeur();
	$limit_sql_messages = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('live_graph_nb_points_max')->getValeur();
	//	****************************************************************************************************************
	//	Nombre de défauts à afficher dans la liste des défauts
	if (! isset($_GET['id'])) {
		return new Response('Bad Url');
	}
	$user='no label';
	if (isset($_GET['user'])) {
		$user = $_GET['user'];
	}
	$rand_connexions = $this->session->get('randConnexions', 0);
	if ($rand_connexions == 0) {
		//	A l'ouverture de la session : 
		//	Création de l'identiant unique de la session
		//	Log de l'ouverture de session Live
		$rand_connexions = time().'_'.rand();
		$this->session->set('randConnexions', $rand_connexions);
		$service_log = $this->container->get('ipc_prog.log');
        $service_log->setLog("Connexion au Live;$user;".$_SERVER["REMOTE_ADDR"], $this->fichier_log);
	}

	//	Identifiant unique de connexion
	$rand_identifiant = $identifiantLive.'_'.$rand_connexions;
	$reloadUrl = false;
	$identifiant = $_GET['id'];
	// Identifiant unique pour le site
	$pattern = "/^$identifiantLive.+/";
	if ($identifiant == $identifiantLive) {
		$reloadUrl = true;
	} elseif (! preg_match($pattern, $identifiant)) {
		return new Response("Bad Url 2");
	}
	if ($reloadUrl == true) {
		return $this->render('IpcSupervisionBundle:Supervision:accueil0.html.twig', array(
			'reloadUrl' => $reloadUrl,
			'rand_connexions' => $rand_connexions
		));
	}

	$connexion = $this->get('ipc_prog.connectbd');
	$service_configuration = $this->get('ipc_prog.configuration');
	$dbh = $connexion->getDbh();

	$valScrollTop = $this->session->get('scrollTopLive', 0);
	$infosBulles = $this->session->get('infosBulles', 'unchecked');
	$infosAside = $this->session->get('infosAside', 'unchecked');
	$alarmeScrollTop = $this->session->get('alarmeScrollTopLive', 0);
	$indexAutomate = $this->session->get('indexAutomate', array());			//	Index de l'automate montré dans la partie Live
	$tab_derniersEvenements	= $this->session->get('tabEvenements', array());
	$page_live = $this->session->get('pageLive', 'Listing');			//	Si pas de variable de session définie : Affichage du live des Listing
	$premier_horodatage = $this->session->get('premierHorodatage', array());
	$code_derniers_evenements = $this->session->get('codeDerniersEvenements', array());
	//	Par défaut on affiche les défauts
	if (empty($code_derniers_evenements)) {
		$code_derniers_evenements = $this->code_defauts;
	}
	$derniers_evenements = null;
	switch ($code_derniers_evenements) {
		case ($this->code_defauts) :
			$derniers_evenements = $this->evenement_defauts;	
			break;
		case ($this->code_alarmes) :
			$derniers_evenements = $this->evenement_alarmes;
			break;
		case ($this->code_evenements) :
			$derniers_evenements = $this->evenement_evenements;
			break;
	}

	$tmp_site = new Site();
	$site_id = $tmp_site->SqlGetIdCourant($dbh);
	$site = $this->em->getRepository('IpcProgBundle:site')->find($site_id);
	$entities_localisation = $this->em->getRepository('IpcProgBundle:Localisation')->findBy(array('site' => $site_id), array('numeroLocalisation' =>  'asc'));
	$nom_affaire = $site->getIntitule();
	$num_affaire = $site->getAffaire();
	// Paramètres de début et de fin de recherche graphique
	$date_fin = date('Y-m-d H:i:s', strtotime('+6 days'));
	$duree = '-'.$nombre_mois.' months';
	$date_debut = date('Y-m-d H:i:s', strtotime($duree, strtotime($date_fin)));
	$tab_localisation_live = array();
	$tab_des_categories_live = array();
	$tabLiveEnTetes = array();
	$tab_des_ModBus = array();
	$tab_titre_series_live = array();
	$tab_donnees_retraitees = array();
	$dernierPointRecupere = "";
	$liveModules = "";
	$description_liveModules = "";
	$nb_automates = 0;

	// Pour la localisation en cours d'affichage : Recherche des titres à afficher pour le live
	// Récupération du nombre d'automate affichés dans la partie Live ( Peut être différent du nombre d'automate sur le site courant )
	$live_automate_nb_test = count($entities_localisation);
	$live_automate_nb = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('live_automate_nb')->getValeur();
	// Recherche des informations live de l'automate courant
	$tab_series_live = array();

	for ($numAutomate = 1; $numAutomate <= $live_automate_nb; $numAutomate++) {
		$live_automate_info		= $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('live_automate_'.$numAutomate)->getValeur();
		$tab_live_automate_info	= explode(';', $live_automate_info);
		// L'automate affiché par défaut est l'automate présent dans la variable live_automate_1
		if (($numAutomate == 1) && (gettype($indexAutomate) == 'array')) {
			foreach ($entities_localisation as $key => $entity_localisation) {
				if ($entity_localisation->getAdresseIp() == $tab_live_automate_info[0]) {
					$indexAutomate = $key;
				}
			}
		}
		// Si l'index de l'automate n'est pas défini c'est que les paramètres de configuration Live pour la localisation sont manquant ou incorrect (live_automate_X)
		if (gettype($indexAutomate) == 'array') {
			// Affichage des titres des séries dans l'ordre décroissant
			$messageError = "Les paramètres de configuration Live des localisations du site courant sont manquants ou incorrects. Paramètre live_automate_x manquant ou incorrect.";
			return $this->render('IpcSupervisionBundle:Supervision:errorAccueil.html.twig', array(
				'messageError'	=> $messageError
			));
		}
		// Ajout d'une valeur au tableau des localisations à afficher
		$tab_localisation_live[] 	= $tab_live_automate_info[0];
		if ($tab_live_automate_info[0] == $entities_localisation[$indexAutomate]->getAdresseIp()) {
			// Récupération de la liste des séries à afficher ( = parametre 2, 3, 4, ... de la variable 'live_automate_x'
			$nbSeries = count($tab_live_automate_info);	
			for ($numSerie=1 ; $numSerie<$nbSeries ; $numSerie++) {
				$tab_series_live[] 	= $tab_live_automate_info[$numSerie];
			}
		}
	}
	$localisation = $entities_localisation[$indexAutomate];
	// Récupération du fichier des en-têtes
	// Selon le type de la chaudière : Récupération du fichiers correspondant
	$fichierInclude = "Supervision/enTeteChaudiereVapeur.inc.html.twig";

	// Création du tableau des titres des séries à afficher : Les titres sont la première partie de la designation (séparation = caractére ;)
	// Récupération du nombre de séries live à afficher
	$nb_series_live = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('live_modules_nb')->getValeur();
	$tab_titre_series_live = array();
	$nb_titre_series = 0;
	for ($numSerie=1;$numSerie<=$nb_series_live;$numSerie++) {
		//	Si le live module est à afficher : Récupération des informations 
		if (in_array($numSerie, $tab_series_live)) {
			$tmp_description_liveModules = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('live_modules'.$numSerie);
			if (! empty($tmp_description_liveModules)) {
				$tab_description_liveModules = explode(';', $tmp_description_liveModules->getDesignation());
				$tab_titre_series_live[$nb_titre_series] = array();
				$tab_titre_series_live[$nb_titre_series]['name'] = $tab_description_liveModules[0];
				$tab_titre_series_live[$nb_titre_series]['number']= $numSerie;
				$nb_titre_series ++;
			}
		}
	}
	// Récupération de la liste des modules à rechercher
	$liveModules =  $this->session->get('liveModules', array());
	if (empty($liveModules)) {
		// Si aucune liste n'est définie recherche de la première liste affichable
		$liveModules = 'live_modules'.$tab_series_live[0];
	}
	$tab_description_liveModules = explode(';', $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre($liveModules)->getDesignation());
	$description_liveModules = $tab_description_liveModules[0];
	$liste_live_modules = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre($liveModules)->getValeur();
	$tab_live_module = explode(';', $liste_live_modules);

	// Affichage du live en fonction de la variable deniers_evenements
	// Encart Liste des X[$this->nombre_liste_evenements] derniers évenements (genre 1 - recherche sur X[$this->nombre_mois_evenements] mois)
	if ($this->sessionAside == true) {
		if ($tab_derniersEvenements == null) {
			$tab_derniersEvenements = $service_configuration->getXMessagesByGenreExtend($code_derniers_evenements, $entities_localisation[$indexAutomate]->getId(), $this->nombre_liste_evenements, $this->nombre_mois_evenements, null, null);
			$this->session->set('tabEvenements', $tab_derniersEvenements);
		}
	}

	// Recherche de la dernière donnée importée en base
	$this->setLastEvenement();

	// Récupération des valeurs modbus pour chaque localisation ayant sa variable 'live_automate_x' définie
	$tab_des_ModBus = array();
	foreach ($entities_localisation as $entity_localisation) {
		$new_EntityConfigModbus = $this->readInfosModbus($entity_localisation);
		if ($new_EntityConfigModbus != null) {
			$tab_des_ModBus[] = $new_EntityConfigModbus;
		} else {
			$tab_des_ModBus[] = null;
		}
	}

	//	Création du tableau des différentes catégorie
	$tab_des_categories_live = array();
	if ($tab_des_ModBus != null) {
		if ($tab_des_ModBus[$indexAutomate]) {
			foreach ($tab_des_ModBus[$indexAutomate]->getDonneesLive() as $entity_donneeLive) {
				if ($entity_donneeLive->getPlacement() != 'enTete') {
					if (! in_array($entity_donneeLive->getCategorie()->getDesignation(), $tab_des_categories_live)) {
						$tab_des_categories_live[] = $entity_donneeLive->getCategorie()->getDesignation();
					}
				}
			}
		}
		// Trie par ordre alphabétique du tableau
		sort($tab_des_categories_live);
		// Création de la variable de session des différentes catégories
		$this->session->set('tabDesCategoriesLive', $tab_des_categories_live);
	}
	// Nombre d'Automates analysés
	$nb_automates = count($tab_des_ModBus);

	// Récupération du tableau des en têtes live
	$tabLiveEnTetes = $this->getTabLiveEnTetes($localisation->getId());

	if ($page_live == 'Graphique') {
		// Récupération des valeurs graphique si c'est le 1er accès à la page ou si la demande concerne l'affichage d'une nouvelle série graphique
		// La recherche des données graphiques n'a lieu que lors d'un clic sur une série à afficher ou après un rafraichissement des données
		// Variable indiquant le dernier point des courbes
		$tab_donnees_retraitees = $this->session->get('tabDonneesLive', array());
		$strDernierPointRecupere = strtotime('2000-01-01 00:00:00');
		$dernierPointRecupere = '2000-01-01 00:00:00';
		if (($tab_donnees_retraitees == 'reinit') || (count($tab_donnees_retraitees) > 1)) {
			$tab_donnees = array();
			foreach ($tab_live_module as $module_live) {
				$tab_donnees = $this->addDonneeToLiveTab($tab_donnees, $indexAutomate, $module_live, $dbh, $this->em, $date_debut, $date_fin, $entities_localisation, $limit_sql_messages);
			}
			// Récupération de l'horodatage le plus récent parmis les premiers horodatage de chaque courbe afin de déterminer le début du graphique
			// Parcours du 1er champs de chaque courbe
			// Les points sont enregistrés par valeur décroissantes : L'horodatage le plus récent de la courbe est sur le dernier point
			$timestampDebut = 0;
			foreach ($tab_donnees as $keyIdModule => $tab_donnee) {
				$dernierIndex = count($tab_donnee['donnees']);
				if ($dernierIndex != 0) {
					if (strtotime($tab_donnee['donnees'][$dernierIndex - 1]['horodatage']) >= $timestampDebut) {
						$premier_horodatage = $tab_donnee['donnees'][$dernierIndex - 1]['horodatage'];
						$timestampDebut = strtotime($tab_donnee['donnees'][$dernierIndex - 1]['horodatage']);
					}
				}
			}
			$this->session->set('premierHorodatage',$premier_horodatage);
			// Retraitement du tableau des données pour passer moins d'arguments en paramètre
			// Parcours des deux courbes
			$tab_donnees_retraitees = array();

			foreach ($tab_donnees as $keyIdModule => $tab_donnee) {
				$dernierIndex = count($tab_donnee['donnees']);
				if ($dernierIndex != 0) {
				$tab_donnees_retraitees[$keyIdModule] = array();
				$tab_donnees_retraitees[$keyIdModule]['donnees'] = array();
				$tab_donnees_retraitees[$keyIdModule]['unite'] = $tab_donnees[$keyIdModule]['unite'];
				$tab_donnees_retraitees[$keyIdModule]['message'] = $tab_donnees[$keyIdModule]['message'];
				// Parcours des données de chaque courbe
				$keyIndex = 0;
				// Parcours de la liste des points de chaque courbe
				// Lorsque le timestamp >= timestamp de début on récupère les points
				// Si le timestamp est > timestamp de début : On récupére également le points n-1 pour avoir le début du graph
				$tmpDebutRecup = false;
				foreach ($tab_donnee['donnees'] as $keyIndexDonnee=>$tabDonnee) {
					// On n'enregistre que les points > timestamp minimum
					$tmpTimeDebut = strtotime($tabDonnee['horodatage']);
					// Enregistrement de tous les points dont l'horodatage est > $timestampDebut
					if ($tmpTimeDebut > $timestampDebut) {
						// Enregistrement du point si il est > au dernier point courant récupéré
						if ($tmpTimeDebut > $strDernierPointRecupere) {
							$strDernierPointRecupere = $tmpTimeDebut;
							$dernierPointRecupere = $tabDonnee['horodatage'];
						}
						$tab_donnees_retraitees[$keyIdModule]['donnees'][$keyIndex] = array();
						foreach ($tabDonnee as $keyDesignationChamps => $donnee) {
							// On ne récupère que les champs qui contiennent une désignation (pour éviter les champs automatique d'index 0,1,2,3 ...)
							if (! preg_match('/^\d+$/',$keyDesignationChamps)) {
								$tab_donnees_retraitees[$keyIdModule]['donnees'][$keyIndex][$keyDesignationChamps] = $donnee;
							}
						}
						$keyIndex ++;
					} elseif ($tmpTimeDebut == $timestampDebut) {
						// Enregistrement du point si il est > au dernier point courant récupéré
						if ($tmpTimeDebut > $strDernierPointRecupere){
							$strDernierPointRecupere = $tmpTimeDebut;
							$dernierPointRecupere = $tabDonnee['horodatage'];
						}
						// Enregistrement du points lorsque l'horodatage est = $timestampDebut
						$tmpDebutRecup = true;
						$tab_donnees_retraitees[$keyIdModule]['donnees'][$keyIndex] = array();
						foreach ($tabDonnee as $keyDesignationChamps=>$donnee) {
							// On ne récupère que les champs qui contiennent une désignation (pour éviter les champs automatique d'index 0,1,2,3 ...)
							if (! preg_match('/^\d+$/',$keyDesignationChamps)) {
								$tab_donnees_retraitees[$keyIdModule]['donnees'][$keyIndex][$keyDesignationChamps] = $donnee;
							}
						}
					} elseif ($tmpDebutRecup == false) {
						// Enregistrement du point si il est > au dernier point courant récupéré
						if ($tmpTimeDebut > $strDernierPointRecupere) {
							$strDernierPointRecupere = $tmpTimeDebut;
							$dernierPointRecupere = $tabDonnee['horodatage'];
						}
						// Enregistrement du point dont l'horodatage est < $timestampDebut si pas d'horodatage = $timestampDebut n'est récupéré
						$tmpDebutRecup = true;
						$tab_donnees_retraitees[$keyIdModule]['donnees'][$keyIndex] = array();
						// On ne récupère que les champs qui contiennent une désignation (pour éviter les champs automatique d'index 0,1,2,3 ...)
						foreach ($tabDonnee as $keyDesignationChamps=>$donnee) {
							if (! preg_match('/^\d+$/',$keyDesignationChamps)) {
								$tab_donnees_retraitees[$keyIdModule]['donnees'][$keyIndex][$keyDesignationChamps] = $donnee;
							}
						}
					}else{
						// Sortie de boucle lorsque les horodatages sont < $timestampDebut
						break;
					}
				}
				}
			}
			$this->session->set('tabDonneesLive',$tab_donnees_retraitees);
		}
	}
	$this->session->set('indexAutomate',$indexAutomate);
	$automate_actif = $entities_localisation[$indexAutomate]->getDesignation();

	// Vérification que le fichier 'lien du live' existe. Si oui, on affichera un lien vers le téléchargement du fichier.
	// Il faut vérifier l'existence du fichier et les droits d'execution dessus.
	$lien_live = true;
    $dossier = "bundles/ipcsupervision/liens/live/";
    $files = scandir($dossier);
	// Si la taille du dossier = 2 c'est qu'il n'y a pas de fichier dedans
	if (sizeof($files) == 2) {
		$lien_live = false;
	} else {
		if ($fichier = @fopen($dossier.$files[2], 'r') == false) {
			$lien_live = false;
		} else {
			@fclose($fichier);
		}
	}
	// Affichage des titres des séries dans l'ordre décroissant 
	return $this->render('IpcSupervisionBundle:Supervision:accueil.html.twig',array(
		'infosBulles' => $infosBulles,
		'timeoutAutomate' => $timeoutAutomate,
		'pingTimeout' => $ping_timeout,
		'pingIntervalle' => $ping_intervalle,
		'last_time_evenement' => $this->last_time_evenement,
		'infosAside' => $infosAside,
		'valScrollTop' => $valScrollTop,
		'alarmeScrollTop' => $alarmeScrollTop,
		'activation_modbus'	=> $this->activation_modbus,
		'tabLiveEnTetes' => $tabLiveEnTetes,
		'tab_couleur_genres' => $this->tab_couleur_genres,
		'fichierInclude' => $fichierInclude,
		'tabLocalisationLive' => $tab_localisation_live,
		'tabDesCategoriesLive' => $tab_des_categories_live,
		'premierLive' => $liveModules,
		'premierHorodatage' => $premier_horodatage,
		'tabTitreSeries' => array_reverse($tab_titre_series_live),
		'derniersEvenements' => $derniers_evenements,
		'nombreEvenements' => $this->nombre_liste_evenements,
		'descriptionLiveModules' => $description_liveModules,
		'nom_affaire' => $nom_affaire,
		'num_affaire' => $num_affaire,
		'tabModbus' => $tab_des_ModBus,
		'pageLive' => $page_live,
		'indexAutomate'	=> $indexAutomate,
		'automateActif' => $automate_actif,
		'nbAutomates' => $nb_automates,
		'donneesGraphique' => $tab_donnees_retraitees,
		'dateDeFin'	=> $dernierPointRecupere,
		'tabEvenements' => $tab_derniersEvenements,
		'liveRefreshListing' => $liveRefreshListing,
		'liveRefreshGraphique' => $liveRefreshGraphique,
		'tabZoom' => $this->tabZoom,
		'sessionAside' => $this->sessionAside,
		'lienLive' => $lien_live
	));
}


// Recherche Ajax permettant la modification des variables de session pour affichage d'une nouvelle série
public function ajax_setNewLiveSerieAction() {
	// Réinitialisation de la variable de session tab_donnees_retraitees pour relancer la recherche graphique
	$this->initialisation();
	$this->initialisationTabZoom();
	$this->session->set('tabDonneesLive', 'reinit');
	// Mise à jour de la variable de session indiquant la liste des modules à rechercher pour l'affichage graphique
	$this->session->set('liveModules', $_POST['liveModules']);
	return new Response();
}

private function addDonneeToLiveTab($tab_donnees, $indexAutomate, $live_module1, $dbh, $entity_manager, $date_debut, $date_fin, $entities_localisation, $limit_sql_messages) {
	$donnee = new Donnee();
	$id_module_live_1 = null;
	// Recherche de l'identifiant du module 1 dont la désignation est donnée par les paramètres live
	if (preg_match('/^(..)(..)(..)$/', $live_module1, $tab_live_module1)) {
		$entities_liste_module_1 = $entity_manager->getRepository('IpcProgBundle:Module')->findBy(array(
			'categorie' => $tab_live_module1[1],
			'numeroModule' => $tab_live_module1[2],
			'numeroMessage' => $tab_live_module1[3]
		));
		// Pour chaque module ayant la désignation recherchée : Recherche d'une liaison module / localisation en cours d'analyse du site courant
		// Info : Il ne peut y avoir qu'un seul module de la désignation indiquée par localisation ( = unicité de la recherche )
		foreach ($entities_liste_module_1 as $entity_liste_module_1) {
			if ($entities_localisation[$indexAutomate]->getModules()->contains($entity_liste_module_1)) {
				$id_module_live_1 = $entity_liste_module_1->getId();
				$tab_donnees[$id_module_live_1] = array();
				// Recherche des points de la courbe
				$tab_donnees[$id_module_live_1]['donnees'] = $donnee->sqlGetForGraphiqueLive($dbh, $date_debut, $date_fin, $id_module_live_1, $entities_localisation[$indexAutomate]->getId(), $limit_sql_messages);
				$tab_donnees[$id_module_live_1]['unite'] = $entity_liste_module_1->getUnite();
				$tab_donnees[$id_module_live_1]['message'] = $entity_liste_module_1->getMessage();
				break;
			}
		}
	}
	return($tab_donnees);
}

// Lecture des paramètres modbus
private function readInfosModbus($automate) {
	// Récupération de l'Objet ConfigModbus
	$this->initialisation();
	$entity_ConfigModBus = $this->em->getRepository('IpcConfigurationBundle:ConfigModbus')->findOneByLocalisation($automate);
	if ($entity_ConfigModBus != null) {
		// Recherche des mots à récupérer en modbus
		// Nombre de mots à rechercher
		$parametre_modbus_found = false;
		// Récupération du service permettant la lecture modbus
		$service_modbus = $this->get('ipc_prog.modbus');
		// Lecture des données modbus
		$new_entity_ConfigModBus = $service_modbus->readInfosModbus($entity_ConfigModBus);
		if (gettype($new_entity_ConfigModBus) == 'object') {
			$entity_ConfigModBus = $service_modbus->trieFamilles($new_entity_ConfigModBus);
		} else {
			// Si la lecture des paramètres modbus échoue
			$entity_ConfigModBus->setMessage("Aucune donnée modbus récupérée pour l'Automate ".$automate->getDesignation()." (".$automate->getAdresseIp()." )");
		}
	}
	// Enregistrement des valeurs ModBus
	$this->em->flush();
	return($entity_ConfigModBus);
}


// Récupération et Lecture des paramètres modbus
private function readNewModbus($automate) {
	// Récupération de l'Objet ConfigModbus
	$this->initialisation();
	$entity_ConfigModBus = $this->em->getRepository('IpcConfigurationBundle:ConfigModbus')->findOneByLocalisation($automate);
	if ($entity_ConfigModBus != null) {
		// Recherche des mots à récupérer en modbus
		// Nombre de mots à rechercher
		$parametre_modbus_found = false;
		// Récupération du service permettant la lecture modbus
		$service_modbus = $this->get('ipc_prog.modbus');
		// Lecture des données modbus
		$new_entity_ConfigModBus = $service_modbus->readConfigModbus($entity_ConfigModBus);
		if (gettype($new_entity_ConfigModBus) == 'object') {
			$entity_ConfigModBus = $service_modbus->trieFamilles($new_entity_ConfigModBus);
		} else {
			// Si la lecture des paramètres modbus échoue
			$entity_ConfigModBus->setMessage("Aucune donnée modbus récupérée pour l'Automate ".$automate->getDesignation()." (".$automate->getAdresseIp()." )");
		}
	}
	// Enregistrement des valeurs ModBus
	$this->em->flush();
	return($entity_ConfigModBus);
}


// Fonction Ajax permettant de modifier l'index de l'automate à présenter dans la partie Live (Modifie la variable de session 'indexAutomate')
// Si la variable d'action est pageLive : Modification de la variable de session indiquant la page live à afficher (pageLive)  
// Si la variable d'action est automate : Modification de la variable de session indiquant la page live à afficher -> Réinitialisation en 'Listing' car affichage des listings par défaut lors de l'affichage d'un automate
// Modification de la variable de session indiquant l'automate affiché (indexAutomate)
// Réinitialisation de la variable tabEvenements pour que la recherche de la liste des défauts soit réeffectuée	
// Réinitialisation de la variable tabDonneesLive : suppression de la variable => Le recherche graphique ne sera effectuée que lors d'un clic sur le bouton Graphique ou sur un bouton Série 
public function ajax_setIndexautomateAction() {
	if (! file_exists($this->flagSessionLive)) {
		file_put_contents($this->flagSessionLive,time());
	}
	$this->initialisation();
	$index = $_POST['index'];
	$typeVariable = $_POST['variable'];
	if ($typeVariable == 'automate') {
		$this->session->set('pageLive','Listing');
		$this->session->set('indexAutomate',$index);
		$this->initialisationTabZoom();
		$this->reinitialisationVariableAction('deleteTabEvenements');
		$this->reinitialisationVariableAction('deleteTabDonneesLive');
	} elseif ($typeVariable == 'pageLive') {
		$this->session->set('pageLive',$index);
	}
	return new Response();
}


//Fonction Ajax permettant de réinitialiser une variable de session
public function reinitialisationVariableAction($evenementController = null) {
	//	Fonction appelée en Ajax
	if (isset($_GET['evenement'])) {
		$evenement = $_GET['evenement'];
	} else {
		//	Fonction appelée localement
		$evenement = $evenementController;
	}
	$this->initialisation();
	switch ($evenement) {
		case ('deleteTabEvenements'):
			$this->session->remove('tabEvenements');
			break;
		case ('deleteTabDonneesLive'):
			$this->session->remove('tabDonneesLive');
			$this->session->remove('liveModules');
			break;
		case ('tabDonneesLive'):
			$valScrollTop = $_GET['valScrollTop'];
			$alarmeScrollTop = $_GET['alarmeScrollTop'];
			$this->session->set('scrollTopLive',$valScrollTop);
			$this->session->set('alarmeScrollTopLive',$alarmeScrollTop);
			$this->session->set('tabDonneesLive','reinit');
			break;
		case ('reinit'):
			$this->session->remove('tabEvenements');
			$this->session->set('tabDonneesLive','reinit');
			//$this->session->remove('liveModules');
			break;
		case ('defauts'):
			//	Enregistrement du code de l'évenement à rechercher.
			$this->session->set('codeDerniersEvenements',$this->code_defauts);
			break;
		case ('alarmes'):
			$this->session->set('codeDerniersEvenements',$this->code_alarmes);
			break;
		case ('evenements'):
			$this->session->set('codeDerniersEvenements',$this->code_evenements);
			break;
	}
	return new Response();
}


// Fonction ajax appelée pour la mise à jour des données Live par appel Modbus
public function ajax_getInfosModbusAction() {
    if (! file_exists($this->flagSessionLive)) {
        file_put_contents($this->flagSessionLive,time());
    }
	$this->initialisation();
	$connexion = $this->get('ipc_prog.connectbd');
	$dbh = $connexion->getDbh();
	$tmp_site = new Site();
	$tmp_localisation = new Localisation();
	$site_id = $tmp_site->SqlGetIdCourant($dbh);
	$site = $this->em->getRepository('IpcProgBundle:site')->find($site_id);
	$entities_localisation = $this->em->getRepository('IpcProgBundle:Localisation')->findBy(array('site' => $site_id), array('numeroLocalisation' =>  'asc'));	
	$indexAutomate = $this->session->get('indexAutomate',array()); 
	// Récupération des valeurs modbus
	$tab_des_ModBus = array();
	foreach ($entities_localisation as $key => $entity_localisation) {
		if ($indexAutomate == $key) {
			$new_EntityConfigModbus = $this->readInfosModbus($entity_localisation);
			if ($new_EntityConfigModbus != null) {
				$tab_des_ModBus[] = $this->getTabLiveEnTetes($entity_localisation->getId());
				$tab_des_ModBus[] = $this->entityToArray($new_EntityConfigModbus);
			}
		}
	}
	echo json_encode($tab_des_ModBus);
	return new Response();
}


// Retourne un tableau des valeurs de l'entité
private function entityToArray($tabEntitiesModbus) {
	$tabConfigModbus = array();
	foreach ($tabEntitiesModbus->getDonneesLive() as $key => $entityDonneeLive) {
		$tabConfigModbus[$key] = array();
		$tabConfigModbus[$key]['label'] = $entityDonneeLive->getLabel();
		$tabConfigModbus[$key]['valeurEntreeVrai'] = $entityDonneeLive->getValeurEntreeVrai();
		$tabConfigModbus[$key]['valeurSortieVrai'] = $entityDonneeLive->getValeurSortieVrai();
		$tabConfigModbus[$key]['valeurSortieFaux'] = $entityDonneeLive->getValeurSortieFaux();
		$tabConfigModbus[$key]['unite'] = $entityDonneeLive->getUnite();
		$tabConfigModbus[$key]['famille'] = $entityDonneeLive->getFamille();
		$tabConfigModbus[$key]['couleur'] = $entityDonneeLive->getCouleur();
		$tabConfigModbus[$key]['valeur'] = $entityDonneeLive->getValeur();
		$tabConfigModbus[$key]['placement'] = $entityDonneeLive->getPlacement();
	}
	return($tabConfigModbus);
}


// Ecriture d'un mot par application modbus
public function writeModbus($mot) {
	$service_modbus = $this->get('ipc_prog.modbus');
	switch ($mot) {
		case 'closeFtp' :
			// Cloture des fichiers par appel modbus et téléchargement uniquement si un traitement identique n'est pas déjà en cours
			$retour = $service_modbus->writeModbus('activate','downloadFtp','all');
			break;
	}
	return(0);
}

public function modbusClotureFtpAction() {
	$this->writeModbus('closeFtp');
	return new Response();
}

public function getTabLiveEnTetes($idLocalisation) {
	$this->constructeur();

	// 1 Récupération du type de générateur de la localisation
	$entityLocalisation = $this->em->getRepository('IpcProgBundle:Localisation')->find($idLocalisation);
	$elTypeGenerateur = $entityLocalisation->getTypeGenerateur();
	// 2 Récupération des modules en-tête associés au type de générateur
	$elEntitiesModulesEnTete = $elTypeGenerateur->getModulesEnteteLive();
	// Création d'un tableau des identifiants de famille d'en tête associées au générateur de la localisation courante
	$tabElFamilleModulesEnTete = array();
	foreach ($elEntitiesModulesEnTete as $elEntityModulesEnTete) {
		$entitytmp_familleLive = $elEntityModulesEnTete->getTypeFamilleLive();
		if ($entitytmp_familleLive != null) {
			$tabElFamilleModulesEnTete[] = $entitytmp_familleLive->getId();
		}
	}
	$tabElFamilleModulesEnTete = array_unique($tabElFamilleModulesEnTete);
	$entitiesLiveEnTetes = $this->em->getRepository('IpcProgBundle:DonneeLive')->myFindByEntete($idLocalisation);
	// Création du tableau des en-tête en fonction des types et des valeurs
	$tabLiveEnTetes = array();
	$numeroEntete = 0;
	foreach ($entitiesLiveEnTetes as $entityEnTete) {
		// On vérifie que le type de famille de l'entité fait partie des familles associées au générateur de la localisation courante
		if (in_array($entityEnTete->getTypeFamille()->getId(), $tabElFamilleModulesEnTete))  {
			switch ($entityEnTete->getFamille()) {
				case 'enTeteGenerateur' :
					// Récupération des valeurs : Phase d'exploitation et Mode d'exploitation
					$tabValeurs = explode(';', $entityEnTete->getValeur());
					$tabLabels  = explode(';', $entityEnTete->getLabel());
					if ($entityEnTete->getValeur() != null) {
						$valPhExploit = $tabValeurs[0];
						$valModeExploit = $tabValeurs[1];
					} else {
						$valPhExploit = '-';
						$valModeExploit = '-';
					}
					$tabLiveEnTetes[$numeroEntete] = array();
					$tabLiveEnTetes[$numeroEntete]['famille'] = 'enTeteGenerateur';
					$tabLiveEnTetes[$numeroEntete]['id'] = 'enTeteGenerateur_'.$numeroEntete;
					$tabLiveEnTetes[$numeroEntete]['label']	= $tabLabels[0];
					if ($valPhExploit == '-') {
						$tabLiveEnTetes[$numeroEntete]['label1'] = '-';
					} elseif ($valPhExploit < 4) {
						$tabLiveEnTetes[$numeroEntete]['label1'] = $tabLabels[1];
					} elseif ($valModeExploit == 1) {
						$tabLiveEnTetes[$numeroEntete]['label1'] = $tabLabels[2];
					} else {
						$tabLiveEnTetes[$numeroEntete]['label1'] = $tabLabels[3];
					}
					break;
				case 'enTeteBruleur' :
					$tabValeurs = explode(';', $entityEnTete->getValeur());
					$tabLabels = explode(';', $entityEnTete->getLabel());
					if ($entityEnTete->getValeur() != null) {
						$valPrFlamme = $tabValeurs[0];
						if (isset($tabValeurs[1])) {
							$valChBruleur = $tabValeurs[1];
						} else {
							$valChBruleur = '-';
						}
					} else {
						$valPrFlamme = '-';
						$valChBruleur = '-';
					}
					$tabLiveEnTetes[$numeroEntete] = array();
					$tabLiveEnTetes[$numeroEntete]['famille'] = 'enTeteBruleur';
					$tabLiveEnTetes[$numeroEntete]['id'] = 'enTeteBruleur_'.$numeroEntete;
					$tabLiveEnTetes[$numeroEntete]['label']	= $tabLabels[0];
					if ($valPrFlamme == '-') {
						$tabLiveEnTetes[$numeroEntete]['label1'] = '-';
					} elseif ($valPrFlamme == 0) {
						$tabLiveEnTetes[$numeroEntete]['label1'] = $tabLabels[2];
					} else {
						$tabLiveEnTetes[$numeroEntete]['label1'] = $valChBruleur;
					}
					break;
				case 'enTeteBruleurBiFoyer' :
					$tabValeurs = explode(';', $entityEnTete->getValeur());
					$tabLabels = explode(';', $entityEnTete->getLabel());
					if ($entityEnTete->getValeur() != null) {
						$valPrFlamme = $tabValeurs[0];
						$valChBruleur = $tabValeurs[1];
						$valPrFlamme2 = $tabValeurs[2];
						$valChBruleur2 = $tabValeurs[3];
					}else{
						$valPrFlamme = '-';
						$valChBruleur = '-';
						$valPrFlamme2 = '-';
						$valChBruleur2 = '-';
					}
					$tabLiveEnTetes[$numeroEntete]              = array();
					$tabLiveEnTetes[$numeroEntete]['famille']   = 'enTeteBruleurBiFoyer';
					$tabLiveEnTetes[$numeroEntete]['id'] = 'enTeteBruleur_'.$numeroEntete;
					$tabLiveEnTetes[$numeroEntete]['label0'] = $tabLabels[0];
					$tabLiveEnTetes[$numeroEntete]['label'] = $tabLabels[1];
					if ($valPrFlamme == '-') {
						$tabLiveEnTetes[$numeroEntete]['label1'] = '-';
					} elseif ($valPrFlamme == 0) {
						$tabLiveEnTetes[$numeroEntete]['label1'] = $tabLabels[2];
					} else { 
						$tabLiveEnTetes[$numeroEntete]['label1'] = $valChBruleur;
					}
					if ($valPrFlamme2 == '-') {
						$tabLiveEnTetes[$numeroEntete]['label2'] = '-';
					} elseif ($valPrFlamme2 == 0) {
						$tabLiveEnTetes[$numeroEntete]['label2'] = $tabLabels[4];
					} else {
						$tabLiveEnTetes[$numeroEntete]['label2'] = $valChBruleur2;
					}
					break;
				case 'enTeteCombustible' :
					$tabValeurs = explode(';', $entityEnTete->getValeur());
					$tabLabels = explode(';', $entityEnTete->getLabel());
					$valCombustible = $tabValeurs[0];
					$tabLiveEnTetes[$numeroEntete] = array();
					$tabLiveEnTetes[$numeroEntete]['famille'] = 'enTeteCombustible';
					$tabLiveEnTetes[$numeroEntete]['id'] = 'enTeteCombustible_'.$numeroEntete;
					$tabLiveEnTetes[$numeroEntete]['label'] = $tabLabels[0];
					if ($valCombustible == 0) {
						$tabLiveEnTetes[$numeroEntete]['label1'] = $tabLabels[1];
					} elseif ($valCombustible == 1) {
						$tabLiveEnTetes[$numeroEntete]['label1'] = $tabLabels[2];
					} elseif ($valCombustible == -1) {
						$tabLiveEnTetes[$numeroEntete]['label1'] = $tabLabels[3];
					} else {
						$tabLiveEnTetes[$numeroEntete]['label1'] = "";
					}
					break;
				case 'enTeteCombustibleBiFoyer' :
					$tabValeurs = explode(';', $entityEnTete->getValeur());
					$tabLabels  = explode(';', $entityEnTete->getLabel());
					if ($entityEnTete->getValeur() != null) {
						$valCombustible = $tabValeurs[0];
						$valCombustible2 = $tabValeurs[1];
					} else {
						$valCombustible = '-';
						$valCombustible2 = '-';
					}
					$tabLiveEnTetes[$numeroEntete] = array();
					$tabLiveEnTetes[$numeroEntete]['famille'] = 'enTeteCombustibleBiFoyer';
					$tabLiveEnTetes[$numeroEntete]['id'] = 'enTeteCombustible_'.$numeroEntete;
					$tabLiveEnTetes[$numeroEntete]['label0'] = $tabLabels[0];
					$tabLiveEnTetes[$numeroEntete]['label'] = $tabLabels[1];
					if ($valCombustible == 1) {
						$tabLiveEnTetes[$numeroEntete]['label1'] = $tabLabels[2];
					} elseif ($valCombustible == 125) {
						$tabLiveEnTetes[$numeroEntete]['label1'] = $tabLabels[3];
					} elseif ($valCombustible == -1) {
						$tabLiveEnTetes[$numeroEntete]['label1'] = $tabLabels[4];
					} else {
						$tabLiveEnTetes[$numeroEntete]['label1'] = '-';
					}
					if ($valCombustible2 == 1){
						$tabLiveEnTetes[$numeroEntete]['label2'] = $tabLabels[2];
					} elseif ($valCombustible2 == 125) {
						$tabLiveEnTetes[$numeroEntete]['label2'] = $tabLabels[3];
					} elseif ($valCombustible2 == -1) {
						$tabLiveEnTetes[$numeroEntete]['label2'] = $tabLabels[4];
					} else {
						$tabLiveEnTetes[$numeroEntete]['label2'] = '-';
					}
					break;
				case 'enTeteEtat' :
					$tabValeurs = explode(';', $entityEnTete->getValeur());
					$tabLabels = explode(';', $entityEnTete->getLabel());
					$tabLiveEnTetes[$numeroEntete] = array();
					$tabLiveEnTetes[$numeroEntete]['famille'] = 'enTeteEtat';
					$tabLiveEnTetes[$numeroEntete]['id'] = 'enTeteEtat_'.$numeroEntete;
					$tabLiveEnTetes[$numeroEntete]['label'] = $tabLabels[0];
					if ($entityEnTete->getValeur() != null) {
						$tabLiveEnTetes[$numeroEntete]['label1'] = $tabValeurs[0];
						$tabLiveEnTetes[$numeroEntete]['label2'] = $tabValeurs[1];
						$tabLiveEnTetes[$numeroEntete]['label3'] = $tabValeurs[2];
					} else {
						$tabLiveEnTetes[$numeroEntete]['label1'] = '-';
						$tabLiveEnTetes[$numeroEntete]['label2'] = '-';
						$tabLiveEnTetes[$numeroEntete]['label3'] = '-';
					}
					break;
				case 'enTetePression' :
					$valPression = $entityEnTete->getValeur();
					$labelPression = $entityEnTete->getLabel();
					$tabLiveEnTetes[$numeroEntete] = array();
					$tabLiveEnTetes[$numeroEntete]['famille'] = 'enTetePression';
					$tabLiveEnTetes[$numeroEntete]['id'] = 'enTetePression_'.$numeroEntete;
					if ($entityEnTete->getValeur() != null) {
						$tabLiveEnTetes[$numeroEntete]['label1'] = $valPression;
					} else {
						$tabLiveEnTetes[$numeroEntete]['label1'] = '-';
					}
					$tabLiveEnTetes[$numeroEntete]['libelle'] = $labelPression;
					break;
				case 'enTeteDebit' :
					$valDebit = $entityEnTete->getValeur();
					$labelDebit = $entityEnTete->getLabel();
					$tabLiveEnTetes[$numeroEntete] = array();
					$tabLiveEnTetes[$numeroEntete]['famille'] = 'enTeteDebit';
					$tabLiveEnTetes[$numeroEntete]['id'] = 'enTeteDebit_'.$numeroEntete;
					if ($entityEnTete->getValeur() != null) {
						$tabLiveEnTetes[$numeroEntete]['label1'] = $valDebit;
					} else {
						$tabLiveEnTetes[$numeroEntete]['label1'] = '-';
					}
					$tabLiveEnTetes[$numeroEntete]['libelle'] = $labelDebit;
					break;
				case 'enTeteNiveau' :
					$valNiveau = $entityEnTete->getValeur();
					$labelNiveau = $entityEnTete->getLabel();
					$tabLiveEnTetes[$numeroEntete] = array();
					$tabLiveEnTetes[$numeroEntete]['famille'] = 'enTeteNiveau';
					$tabLiveEnTetes[$numeroEntete]['id'] = 'enTeteNiveau_'.$numeroEntete;
					if ($entityEnTete->getValeur() != null) {
						$tabLiveEnTetes[$numeroEntete]['label1'] = $valNiveau;
					} else {
						$tabLiveEnTetes[$numeroEntete]['label1'] = '-';
					}
					$tabLiveEnTetes[$numeroEntete]['libelle'] = $labelNiveau;
					break;
				case 'enTeteConductivite' :
					$valConductivite = $entityEnTete->getValeur();
					$labelConductivite = $entityEnTete->getLabel();
					$tabLiveEnTetes[$numeroEntete] = array();
					$tabLiveEnTetes[$numeroEntete]['famille'] = 'enTeteConductivite';
					$tabLiveEnTetes[$numeroEntete]['id'] = 'enTeteConductivite_'.$numeroEntete;
					if ($entityEnTete->getValeur() != null) {
						$tabLiveEnTetes[$numeroEntete]['label1'] = $valConductivite;
					} else {
						$tabLiveEnTetes[$numeroEntete]['label1'] = '-';
					}
					$tabLiveEnTetes[$numeroEntete]['libelle'] = $labelConductivite;
					break;
				case 'enTeteTemperatureDepart' :
					$valTemperature = $entityEnTete->getValeur();
					$labelTemperature = $entityEnTete->getLabel();
					$tabLiveEnTetes[$numeroEntete] = array();
					$tabLiveEnTetes[$numeroEntete]['famille'] = 'enTeteTemperatureDepart';
					$tabLiveEnTetes[$numeroEntete]['id'] = 'enTeteTemperatureDepart_'.$numeroEntete;
					if ($entityEnTete->getValeur() != null) {
						$tabLiveEnTetes[$numeroEntete]['label1'] = $valTemperature;
					} else {
					$tabLiveEnTetes[$numeroEntete]['label1'] = '-';
						}
					$tabLiveEnTetes[$numeroEntete]['libelle'] = $labelTemperature;
					break;
				case 'enTeteTemperatureRetour' :
					$valTemperature = $entityEnTete->getValeur();
					$labelTemperature = $entityEnTete->getLabel();
					$tabLiveEnTetes[$numeroEntete] = array();
					$tabLiveEnTetes[$numeroEntete]['famille'] = 'enTeteTemperatureRetour';
					$tabLiveEnTetes[$numeroEntete]['id'] = 'enTeteTemperatureRetour_'.$numeroEntete;
					if ($entityEnTete->getValeur() != null) {
						$tabLiveEnTetes[$numeroEntete]['label1'] = $valTemperature;
					} else {
						$tabLiveEnTetes[$numeroEntete]['label1'] = '-';
					}
					$tabLiveEnTetes[$numeroEntete]['libelle'] = $labelTemperature;
					break;
			case 'enTeteTemperatureBache' :
				$valTemperature = $entityEnTete->getValeur();
				$labelTemperature = $entityEnTete->getLabel();
				$tabLiveEnTetes[$numeroEntete] = array();
				$tabLiveEnTetes[$numeroEntete]['famille'] = 'enTeteTemperatureBache';
				$tabLiveEnTetes[$numeroEntete]['id'] = 'enTeteTemperatureBache_'.$numeroEntete;
				if ($entityEnTete->getValeur() != null) {
					$tabLiveEnTetes[$numeroEntete]['label1'] = $valTemperature;
				} else {
					$tabLiveEnTetes[$numeroEntete]['label1'] = '-';
				}
				$tabLiveEnTetes[$numeroEntete]['libelle'] = $labelTemperature;
				break;
			default :
				$tabLiveEnTetes[$numeroEntete] = array();
				$tabLiveEnTetes[$numeroEntete]['famille'] = 'enTeteElse';
				$tabLiveEnTetes[$numeroEntete]['id'] = 'enTeteTemperatureRetour_'.$numeroEntete;
				$tabLiveEnTetes[$numeroEntete]['label1'] = '-';
				$tabLiveEnTetes[$numeroEntete]['libelle'] = 'Autre en-tête : '.$entityEnTete->getFamille();
				break;
			}
			$numeroEntete ++;
		}
	}
	return($tabLiveEnTetes);
}

// Fonction ajax retournant la date du dernier fichier importé
public function ajax_getLastTimeEvenementAction() {
	$this->setLastEvenement();
	echo $this->last_time_evenement;
	return new Response();
}


// Fonction AJAX de recherche des nouveaux évenements
public function ajax_getEvenementsAction($nouvelEvenement) {
	if (! file_exists($this->flagSessionLive)) {
		file_put_contents($this->flagSessionLive, time());
	}
	$this->initialisation();
	switch ($nouvelEvenement) {
		case ('defauts') :
			$this->session->set('codeDerniersEvenements', $this->code_defauts);
			break;
		case ('alarmes') :
			$this->session->set('codeDerniersEvenements', $this->code_alarmes);
			break;
		case ('evenements') :
			$this->session->set('codeDerniersEvenements', $this->code_evenements);
			break;
	}
	$service_configuration = $this->get('ipc_prog.configuration');
	$code_derniers_evenements = $this->session->get('codeDerniersEvenements', array());
	// Par défaut on affiche les défauts
	if (empty($code_derniers_evenements)) {
		$code_derniers_evenements = $this->code_defauts;
	}
	$derniers_evenements = null;
	switch ($code_derniers_evenements) {
		case ($this->code_defauts) :
			$derniers_evenements = $this->evenement_defauts;
			break;
		case ($this->code_alarmes) :
			$derniers_evenements = $this->evenement_alarmes;
			break;
		case ($this->code_evenements) :
			$derniers_evenements = $this->evenement_evenements;
			break;
	}
	$site = $this->em->getRepository('IpcProgBundle:Site')->myFindCourant();
	$entities_localisation = $this->em->getRepository('IpcProgBundle:Localisation')->findBy(array('site' => $site->getId()), array('numeroLocalisation' =>  'asc'));
	$indexAutomate = $this->session->get('indexAutomate', array());
	// Affichage du live en fonction de la variable deniers_evenements
	// Encart Liste des X[$this->nombre_liste_evenements] derniers évenements (genre 1 - recherche sur X[$this->nombre_mois_evenements] mois)
	$tab_derniersEvenements   = $service_configuration->getXMessagesByGenreExtend($code_derniers_evenements, $entities_localisation[$indexAutomate]->getId(), $this->nombre_liste_evenements, $this->nombre_mois_evenements, null, null);
	$this->session->set('tabEvenements', $tab_derniersEvenements);
	echo json_encode($tab_derniersEvenements);
	return new Response();
}

public function ajaxSetTabZoomAction() {
	if (! file_exists($this->flagSessionLive)) {
		file_put_contents($this->flagSessionLive, time());
	}
	$tabZoom = array();
	$tabZoom['debut'] = $_GET['min'];
	$tabZoom['fin'] = $_GET['max'];
	$this->getRequest()->getSession()->set('tabZoom', $tabZoom);
	return new Response();
}

private function initialisationTabZoom() {
	$this->tabZoom['debut'] = null;
	$this->tabZoom['fin'] = null;
	$this->session->set('tabZoom', $this->tabZoom);
}

public function ajaxSetInfosBullesAction()
{
	if (! file_exists($this->flagSessionLive)) {
		file_put_contents($this->flagSessionLive, time());
	}
	$isChecked = $_GET['isChecked'];
	$this->getRequest()->getSession()->set('infosBulles', $isChecked);
	return new Response();
}

public function ajaxSetInfosAsideAction() {
	if (! file_exists($this->flagSessionLive)) {
		file_put_contents($this->flagSessionLive, time());
	}
	$isChecked = $_GET['isChecked'];
	$this->getRequest()->getSession()->set('infosAside', $isChecked);
	return new Response();
}
// Recherche de l'heure du dernier fichier importé en base
private function setLastEvenement() {
	$this->constructeur();

    $service_numbers = $this->get('ipc_prog.fillnumbers');
    $service_configuration = $this->get('ipc_prog.configuration');
    $connexion = $this->get('ipc_prog.connectbd');
    $dbh = $connexion->getDbh();
    $tmp_entity_fichier = new Fichier();
	$tmp_entity_donnee = new Donnee();
	$nb_boucle = 1;
	$date_de_mise_en_service = $service_numbers->reverseDate($this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('date_de_mise_en_service')->getValeur());
	$date_de_mise_en_service .= ' 00:00:00';
	$timestamp_date_end_boucle = strtotime($date_de_mise_en_service);
	$date_fin = date('Y-m-d H:i:s', strtotime('+6 days'));
	$entity_last_donnee = null;
	while ($entity_last_donnee == null) {
		$tmp_periode = $nb_boucle + 1;
		$tmp_duree = '-'.$tmp_periode.' weeks';
		$date_debut = date('Y-m-d H:i:s', strtotime($tmp_duree, strtotime($date_fin)));
		$entity_last_donnee = $tmp_entity_donnee->sqlGetLast($dbh, $date_debut, $date_fin, 'all', 'all');
		$nb_boucle ++;
		// Sortie de boucle si on atteind la date de début de mise en service
		if (strtotime($date_debut) < $timestamp_date_end_boucle) {
			break;
		}
	}
	$date_derniere_donnee = null;
	if ($entity_last_donnee != null) {
		$date_derniere_donnee = $entity_last_donnee[0]['horodatage'];
	}
	$last_file_name = $tmp_entity_fichier->sqlGetLast($dbh);
	if ($last_file_name != null) {
		$this->last_time_evenement = $this->get('translator')->trans('live.label.dernier_fichier_importe').' : '.$service_numbers->reverDate($last_file_name[0]['date_traitement'])."<br />"; 
		$this->last_time_evenement .= $this->get('translator')->trans('live.label.derniere_donnee_importee').' : '.$service_numbers->reverDate($date_derniere_donnee);
	}
	return 0;
}

public function ajaxTestPingAction() {
	echo $this->get('translator')->trans('live.label.serveur_actif');
	/*
		$response = new Response();
		$response->headers->set('Access-Control-Allow-Origin', '*');
		$response->setContent($this->get('translator')->trans('live.label.serveur_actif'));
		$response->send();
	*/
	return new Response();
}

public function ajaxSetSessionAsideAction() {
	$this->getRequest()->getSession()->set('sessionAside', true);
	return new Response();
}


public function telechargementLienLiveAction() {
   	$dossier = "bundles/ipcsupervision/liens/live/";
	$files = scandir($dossier);
   	$response = new Response();
   	$response->setContent(file_get_contents($dossier.$files[2]));
   	$response->headers->set('Content-Type', 'application/force-download');
   	$response->headers->set('Content-disposition', 'filename='. $files[2]);
	return $response;
}

}
