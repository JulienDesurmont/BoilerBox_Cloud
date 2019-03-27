<?php
//src/Ipc/ProgBundle/Controller/ProgController.php
namespace Ipc\ProgBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Ipc\ProgBundle\Entity\Site;
use Ipc\ProgBundle\Entity\Configuration;

use Lci\BoilerBoxBundle\Form\Type\ListeDesSitesType;
use Lci\BoilerBoxBundle\Entity\Site as SiteBoilerBox;


class ProgController extends Controller {

private $session;
private $sessTabPageTitle;
private $sessStrUserLabel;
private $messagePeriode;
private $connexion;
private $urlFichierToken;
private $dbh;
private $em;

public function constructeur(){
    // Connexion à la base de donnée
	$srv_connexion = $this->get('ipc_prog.connectbd');

	// Si la connexion provient de boilerbox.fr, la variable de session est définie avec la base de données et l'entity manager à afficher
    if (isset($_SESSION['database'])) {
		$srv_connexion->changementDeBase($_SESSION['database'], 'default');
		// Destruction de la variable de session pour éviter de se reconnecter dessus lors des prochains accès à la page d'accueil de ProgBundle
		unset($_SESSION['database']);
    }
	$this->em = $srv_connexion->getManager();
	$this->urlFichierToken = getenv("DOCUMENT_ROOT").'/web/logs/tokenIpcWeb.txt';
	if (empty($this->session)) {
		$service_session = $this->container->get('ipc_prog.session');
		$this->session = $service_session;
	}
}

// Constructeur de l'objet : Instancie le nom du fichier de log - Initialisation du tableau des modules présents en base de donnée
public function initialisation() {
	$this->connexion = $this->container->get('ipc_prog.connectbd');
	$this->dbh = $this->connexion->getDbh();
	$this->sessStrUserLabel = $this->session->get('label');
	//$this->session->set('pageTitle', array());
    $this->sessTabPageTitle = $this->session->get('pageTitle');
	$translator = $this->get('translator');
	$this->messagePeriode = $translator->trans('periode.info.none');
	$entity_config_ping = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('ping_intervalle');
	if ($entity_config_ping === null) {
        $configuration = new Configuration();
        $configuration->setParametre('ping_intervalle');
        $configuration->setDesignation('Delais entre les pings');
        $configuration->setValeur(20000);
        $configuration->setParametreAdmin(true);
		$this->em->persist($configuration);
		$this->em->flush();
		$entity_config_ping = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('ping_intervalle');
		//throw $this->createNotFoundException('Le paramètre [ping_intervalle] n\'existe pas.');
	} else {
		$this->session->set('ping_intervalle', $entity_config_ping->getValeur());
	}
	$entity_config_timeout = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('ping_timeout');
	if ($entity_config_timeout === null) {
        $configuration = new Configuration();
        $configuration->setParametre('ping_timeout');
        $configuration->setDesignation('Durée en millisecondes avant timeout du ping');
        $configuration->setValeur(5000);
        $configuration->setParametreAdmin(true);
        $this->em->persist($configuration);
        $this->em->flush();
        $entity_config_timeout = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('ping_timeout');
		//throw $this->createNotFoundException('Le paramètre [ping_timeout] n\'existe pas.');
	} else {
		$this->session->set('ping_timeout', $entity_config_timeout->getValeur());
	}
	if (! isset($this->sessTabPageTitle['affaire'])) {
		$id_site = $this->getIdSiteCourant($this->dbh);
		$site = $this->em->getRepository('IpcProgBundle:Site')->find($id_site);
		if ($site != null) {
			$affaire = $site->getAffaire();
			$intitule = $site->getIntitule();
		} else {
			$affaire = 'Aucun site défini';
			$intitule = '';
		}
		// Définition de la variable de session
		$this->sessTabPageTitle['affaire'] = $affaire;
		$this->sessTabPageTitle['site'] = $intitule;
		$this->sessTabPageTitle['title'] = $affaire.' : '.$intitule;
		$this->sessTabPageTitle['version'] = "";
		$versionning = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('numero_version');
		$activation_modbus = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('activation_modbus');
		if (($versionning != null)&&($activation_modbus != null)) {
			$version_boiler = 'V ';
			if ($activation_modbus->getValeur() == 0) {
				$version_boiler = $version_boiler.'NR.';
			}
			$this->sessTabPageTitle['version']   = $version_boiler.$versionning->getValeur();
		}
		$this->session->set('pageTitle', $this->sessTabPageTitle);
	}

	// Recherche du label de l'utilisateur
	if (($this->sessStrUserLabel == '' ) || (gettype($this->sessStrUserLabel) == 'object')) {
		if (isset($_SESSION['label'])) {
			if (! empty($_SESSION['label'])) {
				$this->sessStrUserLabel = $_SESSION['label'];
			} else {
				$usr = $this->get('security.context')->getToken()->getUser();
            	if (gettype($usr) == 'object') {
            	    $this->sessStrUserLabel = ucfirst($usr->getUsername());
            	}
			}
		} else {
			$usr = $this->get('security.context')->getToken()->getUser();
			if (gettype($usr) == 'object') {
				$this->sessStrUserLabel = ucfirst($usr->getUsername());
			}
		}
		$this->session->set('label', $this->sessStrUserLabel);
	}
	$timezone = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('timezone');
	if ($timezone != null) {
		date_default_timezone_set($timezone->getValeur());
	}
	setlocale (LC_TIME, 'fr_FR.utf8', 'fra');
}


// Enregistrement du logout dans les logs
public function gestionLogoutAction() {
	$this->constructeur();
	$this->initialisation();
	$date = new \Datetime();
	$dateDeconnexion = $date->format('Y-m-d H:i:s');
	$fichierToken = fopen($this->urlFichierToken, 'a+');
	fputs($fichierToken, "$dateDeconnexion;Déconnexion\n");
	fclose($fichierToken);
	return $this->render('IpcProgBundle:Prog:logout.html.twig', array(
		'messagePeriode' => null
	));
}

public function indexAction(Request $request) {
	$this->constructeur();
	/**************************************************************
	// Partie pour insérer une liste déroulante pour switcher de site 
    // Création du formulaire pour le choix du site à afficher
    $frm_liste_des_sites = $this->createForm(new ListeDesSitesType);
    $requete = $this->get('request');
	// Connexion au nouveau site 
    if ($requete->getMethod() == 'POST') {
		if (isset($_POST['ListeDesSites']['site'])) {
            // On passe sur la base boilerbox pour récupérer les informations du site de connexion
            $srv_connexion = $this->get('ipc_prog.connectbd');
            $base_initiale = $srv_connexion->getBase();
            $srv_connexion->changementDeBase('boilerbox', $base_initiale);
            $this->em = $srv_connexion->getManager();
           	$ent_site = $this->em->getRepository('Lci\BoilerBoxBundle\Entity\Site')->find($_POST['ListeDesSites']['site']);
           	// On essaye de se connecter à la base du site selectionné. Si echec, on reste sur la base du site d'origine
			if ($ent_site->getAffaire() == 'C671') {
           		$srv_connexion->changementDeBase('boilerbox_'.$ent_site->getAffaire().'_dev', $base_initiale);
			} else {
				$srv_connexion->changementDeBase('boilerbox_'.$ent_site->getAffaire(), $base_initiale);
			}
           	$this->em = $srv_connexion->getManager();
			// Réinitialisation des variables de session
			$this->container->get('ipc_prog.session.boilerbox')->reinitialisationSession('session_complete');
		}
    }
	// Partie pour insérer une liste déroulante pour switcher de site
	// **************************************************************
	*/

    $this->initialisation();

	// Remise à 0 des valeurs de MaxPages des requêtes de listing afin de réeffectuer les requêtes en base tout en gardant les différentes recherches demandées
	$liste_req_pour_listing = $this->session->get('liste_req_pour_listing');
	foreach ($liste_req_pour_listing as $key => $requete) {
	    $liste_req_pour_listing[$key]['MaxPages'] = null;
	}
	$this->session->set('liste_req_pour_listing', $liste_req_pour_listing);
	// Remise à 0 des valeurs de NbDonnees des requêtes graphiques afin de réeffectuer les requêtes en base tout en gardant les différentes recherches demandées
	$liste_req = $this->session->get('liste_graphreq');
	foreach ($liste_req as $key=>$requete) {
		$liste_req[$key]['NbDonnees'] = null;
		$liste_req[$key]['MaxDonnees'] = null;
		$liste_req[$key]['TexteRecherche'] = null;
	}
	$this->session->set('liste_graphreq', $liste_req);

	// Si la variable de session session_date est définie on transmet le message de la Période
	$session_date = $this->session->get('session_date');
	$messagePeriode	= $this->messagePeriode;
	if (! empty($session_date)){
		$messagePeriode = $session_date['messagePeriode'];
	}
	//	INITIALISATION DU TABLEAU DES PERIODES

	// Définition du tableau des périodes d'analyse
	// Récupération du tableau de limitation des requêtes en fonction des périodes d'analyse
	// Récupération du site courant
	$idSiteCourant = $this->getIdSiteCourant($this->dbh);
	if (! empty($idSiteCourant)) {
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
			$this->session->set('infoLimitePeriode', $tabPeriodeAnalyse);
		}
	}

	return $this->render('IpcProgBundle:Prog:accueil.html.twig', array(
		'messagePeriode' => $messagePeriode,
		'sessionCourante' => $this->session->getSessionName(),
		'tabSessions' => $this->session->getTabSessions(),
		// Partie pour insérer une liste déroulante pour switcher de site : 'frm_listeDesSites' => $frm_liste_des_sites->createView()
	));
}

private function getIdSiteCourant($dbh) {
	$this->constructeur();
	$site = new Site();
	$idSiteCourant = $site->SqlGetIdCourant($dbh);
	return ($idSiteCourant);
}

public function getEnTeteAction() {
	$this->constructeur();
    $this->initialisation();
    $response = $this->render('IpcProgBundle:Prog:enTete.html.twig');
	$response->setSharedMaxAge(600);
	return $response;
}

public function redirectionAccueilAction() {
	$this->constructeur();
    $langue = $this->getRequest()->getSession()->get('_locale', 'fr');
    $url = $this->container->get('router')->generate('ipc_prog_homepage', array('_locale' => $langue));
    return new RedirectResponse($url);
}

}
