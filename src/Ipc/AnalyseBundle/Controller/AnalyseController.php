<?php
// getenv("DOCUMENT_ROOT")/src/Ipc/AnalyseBundle/Controller/AnalyseController.php
namespace Ipc\AnalyseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerAware;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\FOSUserEvents;

use Ipc\ProgBundle\Entity\Site;
use Ipc\ProgBundle\Entity\Localisation;


class AnalyseController extends Controller {

private $connexion;
private $dbh;
private $em;
private $session;
private $pageTitle;
private $ping_intervalle;
private $ping_timeout;
private $liste_localisations;
private $tableau_defauts_bruleurs;
private $message_periode = 'Aucune période définie';
private $duree_periode_analyse_bruleur; 


public function constructeur(){
    if (empty($this->session)) {
        $service_session = $this->container->get('ipc_prog.session');
        $this->session = $service_session;
    }
	$this->em = $this->get('ipc_prog.connectbd')->getManager();
}

public function initialisation() {
	$this->constructeur();
    $this->connexion = $this->get('ipc_prog.connectbd');
    $this->dbh = $this->connexion->getDbh();
    $this->pageTitle = $this->session->get('pageTitle');
    $session_date = $this->session->get('session_date');
    if (! empty($session_date)) {
        setlocale (LC_TIME, 'fr_FR.utf8','fra');
        $this->message_periode = $session_date['messagePeriode'];
    }
    $this->ping_intervalle = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('ping_intervalle')->getValeur();
    $this->ping_timeout = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('ping_timeout')->getValeur();
	$this->duree_periode_analyse_bruleur = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('duree_periode_analyse_bruleur')->getValeur();
	$this->tableau_defauts_bruleurs = array();
    // Récupération de la liste des requêtes personnelles
    $this->tabRequetesPerso = $this->getRequetesPerso();
}

//  Création d'un tableau contenant le nom des fichiers de sauvegardes des requêtes graphiques
private function getRequetesPerso() {
    $tabListeFichiers = false;
    $nomUtilisateur = $this->get('security.context')->getToken()->getUser();
    $chemin_dossier_utilisateur =  __DIR__.'/../../../../web/uploads/requetes/graphique/'.$nomUtilisateur;
    // Si le dossier de l'utilisateur n'existe pas : Pas de requêtes perso
    if (is_dir($chemin_dossier_utilisateur)) {
        $tabListeFichiers = array_slice(scandir($chemin_dossier_utilisateur), 2);
        //  Remplacement des caractères espaces
        if ($tabListeFichiers === false) {
            $tabListeFichiers = array();
        } else {
            foreach ($tabListeFichiers as $fichier) {
                $newTab[] = str_replace('_', '', preg_replace('_nbsp_', ' ', $fichier));
            }
            if (! empty($newTab)) {
                $tabListeFichiers = $newTab;
            }
        }
    }
    return($tabListeFichiers);
}

public function indexAction() {
	$this->constructeur();
	$this->initialisation();
	//	Recherche de la liste des localisations du site courant ayant les paramètres de configuration 'défaut bruleur' définis
	//	Enregistrement de ces localisations dans un tableau qui sera utilisé dans la liste déroulante de la page twig
    // Initialisation des listes de localisation : Récupération des localisations associées au site courant
	$tmp_site = new Site();
	$site_id = $tmp_site->SqlGetIdCourant($this->dbh);
	$affaire_site = strtolower($this->em->getRepository('IpcProgBundle:Site')->find($site_id)->getAffaire());

	/*
    if (count($this->session->get('tablocalisations')) == 0) {
        $tmp_localisation = new Localisation();
        $this->liste_localisations = $tmp_localisation->SqlGetLocalisation($this->dbh, $site_id);
        $this->session->set('tablocalisations', $this->liste_localisations);
    } else {
        $this->liste_localisations = $this->session->get('tablocalisations');
    }
	*/
	$this->container->get('ipc_prog.session.boilerbox')->definirListeLocalisationsCourantes();

    $this->liste_localisations = $this->session->get('tablocalisations');
    if ($this->liste_localisations == null) {
        $this->get('session')->getFlashBag()->add('info', "Aucune Localisation définie pour le site courant");
        return false;
    }

	if (count($this->session->get('tabdefautsbruleurs')) == 0) {
		//  Recherche des paramètres défauts bruleurs pour chaque localisation
		//		Enregistrement des valeurs des codes messages dans le tableau $tabDefautsBruleurs en fonction de l'id de la localisation et du numéro du bruleur
		//	et
    	// 	Initialisation de tableaux de conversion permettant d'afficher 	- la désignation d'une localisation selon son id
		//																	- la désignation d'une localisation selon son numéro
		//																	- le numéro d'une localisation selon son id
		$pattern_num_bruleur = '/_defaut_bruleur_(.+?)$/';
    	foreach ($this->liste_localisations as $key => $localisation) {
			$tabDefautsBruleurs = $this->em->getRepository('IpcProgBundle:Configuration')->myFindOneByParametreLike($localisation['adresse_ip'].'_defaut_bruleur_');
			foreach ($tabDefautsBruleurs as $keyTab => $defaut) {
				// Récupération du numéro du bruleur
				if (preg_match($pattern_num_bruleur, $defaut['parametre'], $tab_pattern_defaut_bruleur)) {
					$numBruleur = $tab_pattern_defaut_bruleur[1];
					if (! isset($this->tableau_defauts_bruleurs[$localisation['id']."_".$localisation['numero_localisation']])) {
						$this->tableau_defauts_bruleurs[$localisation['id']."_".$localisation['numero_localisation']] = array();
						$this->tableau_defauts_bruleurs[$localisation['id']."_".$localisation['numero_localisation']]['designation'] = $localisation['designation'];
						$this->tableau_defauts_bruleurs[$localisation['id']."_".$localisation['numero_localisation']]['bruleur '.$numBruleur] = $defaut['valeur'];
					} else {
						$this->tableau_defauts_bruleurs[$localisation['id']."_".$localisation['numero_localisation']]['bruleur '.$numBruleur] = $defaut['valeur'];
					}
				}
			}
    	}
		$this->session->set('tabdefautsbruleurs', $this->tableau_defauts_bruleurs);
	} else {
		$this->tableau_defauts_bruleurs = $this->session->get('tabdefautsbruleurs');
	}
	return $this->render('IpcAnalyseBundle:Analyse:index.html.twig', array(
			'affaireSite' => $affaire_site,
			'messagePeriode' => $this->message_periode,
			'pageTitle' => $this->pageTitle,
        	'ping_intervalle' => $this->ping_intervalle,
        	'ping_timeout' => $this->ping_timeout,
			'duree_periode_analyse_bruleur' => $this->duree_periode_analyse_bruleur,
			'tableau_defauts_bruleurs' => $this->tableau_defauts_bruleurs,
			'sessionCourante' => $this->session->getSessionName(),
        	'tabSessions' => $this->session->getTabSessions()
	));
}

}

