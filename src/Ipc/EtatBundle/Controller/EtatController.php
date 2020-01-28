<?php
//src/Ipc/EtatBundle/Controller/EtatController
namespace Ipc\EtatBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use Ipc\ProgBundle\Entity\Site;
use Ipc\ProgBundle\Entity\Localisation;
use Ipc\ProgBundle\Entity\Module;
use Ipc\ProgBundle\Entity\Configuration;
use Ipc\ProgBundle\Entity\Etat;

use Ipc\ProgBundle\Entity\EtatAuto;
use Ipc\EtatBundle\Form\Type\EtatAutoType;

use Ipc\ProgBundle\Entity\EtatDate;
use Ipc\EtatBundle\Form\Type\EtatDateType;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;


class EtatController extends Controller {

private $test;
private $fichier_log = 'etat.log';
private $connexion;
private $dbh;
private $em;
private $session;
private $sessTabPageTitle;
private $sessStrUserLabel;
private $tab_modules_e;
private $tab_calculs;
private $tab_etats;
private $liste_localisations;
private $tab_conversion_loc_id = array();
private $tab_conversion_loc_num = array();
private $tab_conversion_loc_getnum = array();
private $tab_last_horodatage_loc_id = array();
private $last_loc_etat_id;
private $liste_genres = null;
private $tab_conversion_genre_id = array();
private $tab_conversion_idmodule_numgenre = array();
private $liste_messages_modules = array();
private $liste_noms_modules = array();
private $tab_conversion_message_id = array();
private $titre_page_etat;
private $service_fillNumbers;

public function constructeur(){
    if (empty($this->session)) {
        $service_session = $this->container->get('ipc_prog.session');
        $this->session = $service_session;
    }
}

// Récupération des calculs (= ensembles des patrons des états) et des états (= états créés) de la base de données
private function getModulesEtat() {
    $this->tab_calculs = $this->em->getRepository('IpcProgBundle:Calcul')->findAll();
    $this->tab_etats = $this->em->getRepository('IpcProgBundle:Etat')->findAll();
    return(0);
}

// Retourne le répertoire des logs
private function getLogDir() {
    return __DIR__.'/../../../../web/logs/';
}

private function getEtatDir() {
    return __DIR__.'/../../../../web/etats/';
}

// Ecris un message de log
private function setLog($message) {
    $this->initialisation();
    $ficlog = $this->getLogDir().$this->fichier_log;
    $message = date("d/m/Y;H:i:s;").$message."\n";
    $fp = fopen($ficlog, "a");
    fwrite($fp, $message);
    fclose($fp);
}

// Fonction qui supprime les caractères spéciaux dans les messages des modules
private function suppressionDesCaracteres($chaine) {
    $pattern = '/=?\s?\$/';
    $replacement = '';
    $chaine = preg_replace($pattern, $replacement, $chaine);
    return($chaine);
}


private function getMessageRequete($numLocalisation, $message_idModule, $codeVal1, $val1min, $val1max, $codeVal2, $val2min, $val2max) {
    $message = '';
    if ($numLocalisation != 'all') {
        $message .= "Localisation : [$numLocalisation] : ";
    }
    $message .= $this->suppressionDesCaracteres($message_idModule);
    switch($codeVal1) {
        case 'Sup' :
            $message .= " (Valeur 1 inférieure à $val1min)";
        break;
        case 'Inf' :
            $message .= " (Valeur 1 supérieure à $val1min)";
        break;
        case 'Int' :
            $message .= " (Valeur 1 comprise entre $val1min et $val1max)";
        break;
    }
    switch($codeVal2) {
        case 'Sup' :
            $message .= " (Valeur 2 inférieure à $val2min)";
        break;
        case 'Inf' :
            $message .= " (Valeur 2 supérieure à $val2min)";
        break;
        case 'Int' :
            $message .= " (Valeur 2 comprise entre $val2min et $val2max)";
        break;
    }
    return($message);
}

// Fonction qui vérifie que les conditions entrées sont bien formatées
private function verificationCondition($condition) {
    // Suppression des espaces
    $condition = trim($condition);
    $condition = preg_replace('/\s+/','',$condition);
    $pattern_condition = '/^(<|>|<=|>=|=|\d<>)\d$/';
    if (preg_match($pattern_condition, "$condition")) {
        return($condition);
    }
    return(null);
}


private function verifEtatDates($dateDeb,$dateFin) {
    $bool_verifDate = true;
    if ($dateDeb == null || $dateFin == null) {
        $bool_verifDate = false;
    }
    if (preg_match('/(\d{2})[-\/](\d{2})[-\/](\d{4}) \d{2}:\d{2}:\d{2}$/',$dateDeb,$tabDate) == 0 || checkdate($tabDate[2],$tabDate[1],$tabDate[3]) == 0) {
        $bool_verifDate = false;
    }
    if (preg_match('/(\d{2})[-\/](\d{2})[-\/](\d{4}) \d{2}:\d{2}:\d{2}$/',$dateFin,$tabDate) == 0 || checkdate($tabDate[2],$tabDate[1],$tabDate[3]) == 0) {
        $bool_verifDate = false;
    }
    try{
        $date1 = new \Datetime($dateDeb);
        $date2 = new \Datetime($dateFin);
        if ($date1 > $date2) {
            $bool_verifDate = false;
        }
    } catch(\Exception $e) {
        $bool_verifDate = false;
    }
    return($bool_verifDate);
}


// - - - - - - - - - - - -- - - - -- - - - - - - - - -- - - -- - - - - - - - - - - - - - -- - - - - - - -- - - - - - -- - - - - - -- --  - 


// Initialisation des variables communes aux différentes fonctions
public function initialisation() {
	$this->connexion = $this->get('ipc_prog.connectbd');
	$this->service_fillNumbers = $this->get('ipc_prog.fillnumbers');
	$this->dbh = $this->connexion->getDbh();
	$this->em = $this->getDoctrine()->getManager();
	$this->sessStrUserLabel = $this->session->get('label');
	$this->sessTabPageTitle = $this->session->get('pageTitle');
	$translator = $this->get('translator');
	$this->messagePeriode = $translator->trans('periode.info.none');
    if (! isset($this->sessTabPageTitle['affaire'])) {
    	$id_site = $this->getIdSiteCourant($this->dbh);
        $site = $this->container->get('doctrine')->getManager()->getRepository('IpcProgBundle:Site')->find($id_site);
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
       	$versionning = $this->container->get('doctrine')->getManager()->getRepository('IpcProgBundle:Configuration')->findOneByParametre('numero_version');
        $activation_modbus = $this->container->get('doctrine')->getManager()->getRepository('IpcProgBundle:Configuration')->findOneByParametre('activation_modbus');
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
    if ($this->sessStrUserLabel == '' ) {
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
    $timezone = $this->container->get('doctrine')->getManager()->getRepository('IpcProgBundle:Configuration')->findOneByParametre('timezone');
    if ($timezone != null) {
        date_default_timezone_set($timezone->getValeur());
    }
    setlocale (LC_TIME, 'fr_FR.utf8', 'fra');
    $this->getModulesEtat();
	return(0);
}

public function initialisationListes() {
	$liste_modules = null;
	$correspondance_message_code = array();


    $this->session->definirListeLocalisationsCourantes();
    $this->liste_localisations = $this->session->get('tablocalisations');
    if ($this->liste_localisations == null) {
        $this->get('session')->getFlashBag()->add('info',"Aucune Localisation définie pour le site courant (l1)");
        return false;
    }
	// Initialisation d'un tableau de converion des localisations permettant d'afficher la désignation d'une localisation selon son id
    foreach($this->liste_localisations as $key => $localisation) {
        $this->tab_conversion_loc_id[$localisation['id']] = $localisation['designation'];
        $this->tab_conversion_loc_num[$localisation['numero_localisation']] = $localisation['designation'];
        $this->tab_conversion_loc_getnum[$localisation['id']] = $localisation['numero_localisation'];
		$this->tab_last_horodatage_loc_id[$localisation['id']] = $localisation['last_horodatage'];
    }
    // Récupération de la dernière localisation entrée pour la réafficher par défaut dans la popup
    $this->last_loc_etat_id = $this->session->get('last_loc_etat_id');
    // Si il n'y a pas eu de requête enregistrée, la localisation par défaut est la première de la liste
    if (empty($this->last_loc_etat_id)) {
        $this->last_loc_etat_id = $this->liste_localisations[0]['id'];
    }


    // Initialisation de la liste des genres autorisés
    $this->session->definirListeDesGenres();
    $this->liste_genres = $this->session->get('tabgenres');
    foreach ($this->liste_genres as $key => $genre) {
        $this->tab_conversion_genre_id[$genre['id']] = $genre['intitule_genre'];
        $this->tab_conversion_genre_num[$genre['id']] = $genre['numero_genre'];
    }



    // Initialisation de la liste des modules
    $this->session->definirTabModuleL();
    $this->tab_modules_e = $this->session->get('tabModules');
    if ($this->tab_modules_e == null) {
        $this->get('session')->getFlashBag()->add('info', "Etat : Aucun module n'est associé aux localisations du site courant : Veuillez importer la/les table(s) d'échanges ou modifier le paramètre popup_simplifiee");
        return false;
    }
    $correspondance_message_code = $this->session->get('correspondance_Message_Code');
	

	// les trois paramètres sont définis en même temps.
    $this->liste_messages_modules = $this->session->get('liste_messages_modules_etat');
    $this->liste_noms_modules = $this->session->get('liste_noms_modules_etat');
    $this->tab_conversion_message_id = $this->session->get('tab_conversion_message_id_etat');
	// si un des paramètres est manquant, récupération de la liste des paramètres
	if ((empty($this->liste_messages_modules)) || empty($this->liste_noms_modules) || empty($this->tab_conversion_message_id)) {
        if (count($this->liste_localisations) > 1) {
            $localisation_id = $this->last_loc_etat_id;
			foreach ($this->tab_modules_e as $key => $module) {
                if (in_array($this->last_loc_etat_id, $module['localisation'])) {
                    // Création d'un tableau pour éviter de présenter des doublons dans les intitulés des modules
                    if (! in_array($module['intitule'], $this->liste_noms_modules)) {
                        array_push($this->liste_noms_modules, $module['intitule']);
                    }
                    $this->liste_messages_modules[$key] = $correspondance_message_code[$key]." - ".$this->suppressionDesCaracteres($module['message']);
                    $this->tab_conversion_message_id[$key] = $module['message'];
                }
            }
		} else {
			// Si il y a plusieurs localisations : Récupération initiale des informations concernant la localisation 1
			// Création des tableaux des intitulés et des messages de modules	
			foreach ($this->tab_modules_e as $key => $module) {
				if (! in_array($module['intitule'], $this->liste_noms_modules)) {
					array_push($this->liste_noms_modules, $module['intitule']);
				}
				$this->liste_messages_modules[$key] = $correspondance_message_code[$key]." - ".$this->suppressionDesCaracteres($module['message']);
            	$this->tab_conversion_message_id[$key] = $module['message'];
			}
		}
		asort($this->liste_noms_modules);
		asort($this->liste_messages_modules);
		// Ajout d'une variable de session afin de permettre une recherche des messages par recherche directe
        $this->session->set('liste_messages_modules_etat', $this->liste_messages_modules);
		// Ajout d'une variable de session qui stock la liste des modules afin d'éviter des faire la boucle de parcours de modules une fois la variable créée
		$this->session->set('liste_noms_modules_etat', $this->liste_noms_modules);
        // Ajout de la variable de session qui stock les correspondances idModule => message
		$this->session->set('tab_conversion_message_id_etat', $this->tab_conversion_message_id);
	}
	 return(true);
}


// Récupération de l'état à afficher. Si 0 on affiche la page d'accueil sans Etat
public function afficheEtatAction($idEtat) {
	$this->constructeur();
    // Récupération du type de l'Etat
    $this->initialisation();
    if ($idEtat != 0) {
        $entity_etat = $this->em->getRepository('IpcProgBundle:Etat')->find($idEtat);
        $type_etat = $entity_etat->getCalcul()->getId();
    } else {
        $type_etat = 0;
    }
    switch ($type_etat) {
        case 0: return $this->afficheEtatAccueil();
        break;
        case 1: return $this->afficheEtat1AccueilAction($idEtat);	//return $this->afficheEtat1Action($idEtat);
        break;
    }
	return 0;
}

// Appel d'un nouveau calcul depuis la page d'accueil des états
// Calcul 1 = Etat Analyse de marche chaudière
public function nouvelEtatAction($numero) {
	$this->constructeur();
	switch ($numero) {
		case 1:
	 		return $this->nouvelEtatAnalyseDeMarche();
			break;
	}
	return new Response();
}

// - - - - - - - - - - - - -- - - - - - - - - - - -- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
//											AFFICHAGE DES CALCULS

private function nouvelEtatAnalyseDeMarche() {
	$this->initialisation();
	$autorisation_acces = $this->initialisationListes();
	if ($autorisation_acces == false) {
		return $this->redirect($this->generateUrl('ipc_prog_homepage'));
	}
    if (count($this->liste_messages_modules) == 0) {
        $this->get('session')->getFlashBag()->add('info', "Etat : Aucun module défini pour les localisations du site courant : Veuillez importer la/les table(s) d'échanges");
        return $this->redirect($this->generateUrl('ipc_prog_homepage'));
    }
	set_time_limit(0);

    $this->session->remove('liste_req_etat_combustibleB1');
    $this->session->remove('liste_req_etat_combustibleB2');
    $this->session->remove('liste_req_etat_compteur');
    $this->session->remove('liste_req_etat_test');
    $this->session->remove('liste_req_etat_forcage');
	$liste_req_combustibleB1 = $this->session->get('liste_req_etat_combustibleB1', array());
	$liste_req_combustibleB2 = $this->session->get('liste_req_etat_combustibleB2', array());
	$liste_req_compteur = $this->session->get('liste_req_etat_compteur', array());
	$liste_req_test = $this->session->get('liste_req_etat_test', array());
	$liste_req_forcage = $this->session->get('liste_req_etat_forcage', array());

$tab_requetes_combustibleB1 = array();
    $tab_requetes_combustibleB2 = array();
    $tab_requetes_compteur = array();
    $tab_requetes_test = array();
    $tab_requetes_forcage = array();


	//  - - - -- - - - - - - - - - -- - - - - --    PARTIE REMPLACEMENT du numéro de localisation par l'intitulé de la localisation pour l'affichage
	// Pour chaque courbe, si une Localisation est définie, remplacement du numéro par l'intitulé
	// Création du tableau de conversion : N°localisation => Intitulé

	foreach ($this->liste_localisations as $key => $localisation) {
		$tabConversionLoc[$localisation['numero_localisation']] = $localisation['designation'];
	}
	// - - - -- - - - - - - - - - -- - - - - -- - - - -- - - - - - - - - - -- - - - - -- - - - -- - - - - - - - - - -- - - - - --

    // Le fichier Prog/selection.js necessite la présence de la variable $tab_requetes
    $tab_requetes   = array();
    $fileToInclude  = 'IpcEtatBundle:Etat:analyseMarchePrincipalChaudiere.html.twig';
    $this->titre_page_etat = 'Etat - Analyse de marche';

    $response = new Response($this->renderView('IpcEtatBundle:Etat:accueil.html.twig', array(
    	'titrePageEtat' => $this->titre_page_etat,
        'tabCalculs' => $this->tab_calculs,
        'tabEtats' => $this->tab_etats,
        'fileToInclude' => $fileToInclude,
        'liste_localisations' => $this->liste_localisations,
        'liste_genres' => $this->liste_genres,
        'liste_nomsModules' => $this->liste_noms_modules,
        'liste_messagesModules' => $this->liste_messages_modules,
        'tab_requetes_combustibleB1' => $tab_requetes_combustibleB1,
        'tab_requetes_combustibleB2' => $tab_requetes_combustibleB2,
        'tab_requetes_compteur' => $tab_requetes_compteur,
        'tab_requetes_test' => $tab_requetes_test,
        'tab_requetes_forcage' => $tab_requetes_forcage,
        'jsProd' => true,
		'sessionCourante' => $this->session->getSessionName(),
        'tabSessions' => $this->session->getTabSessions()
        ))
    );
    $response->setPublic();
    $response->setETag(md5($response->getContent()));
    return $response;
}
// - - - - - - - - - - - - -- - - - - - - - - - - -- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

// Fonction d'ajout de requête Appelée en AJAX
public function gestionEtatQueriesAction() {
	$this->constructeur();
    $this->initialisation();
    $autorisation_acces     = $this->initialisationListes();
    if ($autorisation_acces == false) {
        return $this->redirect($this->generateUrl('ipc_prog_homepage'));
    }
    set_time_limit(0);
    $typePopup = $_GET['typePopup'];
    // Réinitialisation des valeurs des requêtes des 'etatgraphique
    // Sinon la recherche Count n'est pas réeffectuée : Erreur lorsque le nombre de données correspond au nombre de données après un Zoom et non pas au nombre de données de la période
    switch ($typePopup) {
        case 'combustibleB1':
            $liste_req = $this->session->get('liste_req_etat_combustibleB1');
            break;
        case 'combustibleB2':
            $liste_req = $this->session->get('liste_req_etat_combustibleB2');
            break;
        case 'compteur':
            $liste_req = $this->session->get('liste_req_etat_compteur');
            break;
        case 'test':
            $liste_req = $this->session->get('liste_req_etat_test');
            break;
        case 'forcage':
            $liste_req = $this->session->get('liste_req_etat_forcage');
            break;
    	default:
    		$liste_req = array();
    		break;
    }
    foreach ($liste_req as $key => $requete) {
        $liste_req[$key]['TexteRecherche']  = null;
    }
    switch ($typePopup) {
        case 'combustibleB1':
            $this->session->set('liste_req_etat_combustibleB1', $liste_req);
            break;
        case 'combustibleB2':
            $this->session->set('liste_req_etat_combustibleB2', $liste_req);
            break;
        case 'compteur':
            $this->session->set('liste_req_etat_compteur', $liste_req);
            break;
        case 'test':
            $this->session->set('liste_req_etat_test', $liste_req);
            break;
        case 'forcage':
            $this->session->set('liste_req_etat_forcage', $liste_req);
            break;
    }
    $correspondance_message_code = $this->session->get('correspondance_Message_Code');
    // Etablissement de la connexion et récupération du pointeur permettant de manipuler les requêtes Sql
    $dbh = $this->dbh;
    $configuration = new Configuration();
    $heure_debut = strtotime(date('Y-m-d h:i:s'));
    // Service des requêtes entrantes
    $request = $this->get('request');
    // Distinction entre 'Lancer la recherche' OU 'Ajouter une requête de recherche'
    $submit = $_GET["choixSubmit"];
    if ($submit == 'suppressionRequete') {
        // Récupération du numéro de requête à supprimer
        $tabModifRequete = explode('_', $_GET['suppression_requete']);
        $idRequeteASup = $tabModifRequete[1];
        // Si il y a plus d'une requête : Suppression de la requête Sinon réinitialisation de la variable
        if (count($liste_req) == 1) {
    		switch ($typePopup) {
        		case 'combustibleB1':
            		$this->session->remove('liste_req_etat_combustibleB1');
            		$liste_req = $this->session->get('liste_req_etat_combustibleB1');
            		break;
                case 'combustibleB2':
                    $this->session->remove('liste_req_etat_combustibleB2');
                    $liste_req = $this->session->get('liste_req_etat_combustibleB2');
                    break;
        		case 'compteur':
            		$this->session->remove('liste_req_etat_compteur');
            		$liste_req = $this->session->get('liste_req_etat_compteur');
            		break;
        		case 'test':
            		$this->session->remove('liste_req_etat_test');
            		$liste_req = $this->session->get('liste_req_etat_test');
            		break;
        		case 'forcage':
            		$this->session->remove('liste_req_etat_forcage');
            		$liste_req = $this->session->get('liste_req_etat_forcage');
            		break;
    		}
        } else {
            unset($liste_req[$idRequeteASup]);
        }
        // Réorganisation du tableau
        $liste_req = array_filter($liste_req);
        $liste_req = array_values($liste_req);
        switch ($typePopup) {
            case 'combustibleB1':
				$this->session->set('liste_req_etat_combustibleB1', $liste_req);
                break;
            case 'combustibleB2':
                $this->session->set('liste_req_etat_combustibleB2', $liste_req);
                break;
            case 'compteur':
                $this->session->set('liste_req_etat_compteur', $liste_req);
                break;
            case 'test':
                $this->session->set('liste_req_etat_test', $liste_req);
                break;
            case 'forcage':
                $this->session->set('liste_req_etat_forcage', $liste_req);
                break;
        }
    } elseif ($submit != 'RAZ') {
        // Si une modification de requête est demandée : Suppression de l'ancienne requête et création d'une nouvelle requête
        if ($_GET['modificationRequete'] != null) {
            $idRequeteASup = $_GET['modificationRequete'];
            if (count($liste_req) == 1) {
                switch ($typePopup) {
                    case 'combustibleB1':
                        $this->session->remove('liste_req_etat_combustibleB1');
                        $liste_req = $this->session->get('liste_req_etat_combustibleB1');
                        break;
                    case 'combustibleB2':
                        $this->session->remove('liste_req_etat_combustibleB2');
                        $liste_req = $this->session->get('liste_req_etat_combustibleB2');
                        break;
                    case 'compteur':
                        $this->session->remove('liste_req_etat_compteur');
                        $liste_req = $this->session->get('liste_req_etat_compteur');
                        break;
                    case 'test':
                        $this->session->remove('liste_req_etat_test');
                        $liste_req = $this->session->get('liste_req_etat_test');
                        break;
                    case 'forcage':
                        $liste_req = $this->session->get('liste_req_etat_forcage');
                        $this->session->remove('liste_req_etat_forcage');
                        break;
                }
            } else {
                unset($liste_req[$idRequeteASup]);
                $liste_req = array_filter($liste_req);
                $liste_req = array_values($liste_req);
            }
            switch ($typePopup) {
                case 'combustibleB1':
                    $this->session->set('liste_req_etat_combustibleB1', $liste_req);
                    break;
                case 'combustibleB2':
                    $this->session->set('liste_req_etat_combustibleB2', $liste_req);
                    break;
                case 'compteur':
                    $this->session->set('liste_req_etat_compteur', $liste_req);
                    break;
                case 'test':
                    $this->session->set('liste_req_etat_test', $liste_req);
                    break;
               	case 'forcage':
                    $this->session->set('liste_req_etat_forcage', $liste_req);
                    break;
            }
        }
        $localisations = $_GET['listeLocalisations'];
        $modules = $_GET['listeModules'];
        $idGenre = $_GET['listeGenres'];
        $idModules = $_GET['listeIdModules'];
        $val1min = null;
        $val1max = null;
        // Indique quel type de recherche sur la valeur 1 est demandé (Supérieur/ Inférieur/ Interval)
        $codeVal1 = $_GET['codeVal1'];
        if (($codeVal1 != 'None') && ($codeVal1 != 'undefined')) {
            $val1min = (int)$_GET['codeVal1Min'];
            if ($codeVal1  == 'Int') {
                $val1max = (int)$_GET['codeVal1Max'];
            }
        } else {
        	$codeVal1 = null;
        }
        $val2min = null;
        $val2max = null;
        // Indique quel type de recherche sur la valeur 2 est demandé (Supérieur/ Inférieur/ Interval)
        $codeVal2 = $_GET['codeVal2'];
        if (($codeVal2 != 'None')&&($codeVal2 != 'undefined')) {
            $val2min = (int)$_GET['codeVal2Min'];
            if ($codeVal2  == 'Int') {
                $val2max = (int)$_GET['codeVal2Max'];
            }
        } else {
        	$codeVal2 = null;
        }
        // Recherche de l'intitulé du genre dont l'identifiant est envoyé à la page : Permet d'indiquer l'intitulé du genre dans le message de la page graphique.html
        $message_genre = 'all';
        if ($idGenre != 'all') {
            foreach ($this->liste_genres as $tmp_genre) {
                if ($tmp_genre['id'] == $idGenre) {
                    $message_genre = $tmp_genre['intitule_genre'];
                }
            }
        }
        // Recupération de la liste des modules : Tableau :        
		// IdModule 	=> 'intitule'  -> intitulé de la famille de module
        //    			=> 'message'   -> message du module
        //             	=> 'genre'     -> id du genre du module
        $this->liste_message = $this->session->get('tabModules');
 
        // info : Lors de la selection d'un message, c'est l'id du module qui est retourné par la page index.html.twig
        // Recherche du message associé à l'identifiant de module
        $message_idModule = 'all';
        if ($idModules != 'all') {
            foreach ($this->liste_message as $key => $message) {
                if ($key == $idModules) {
                    $message_idModule   = $correspondance_message_code[$idModules].'_'.$message['message'];
                }
            }
        }
        // Récupération du numéro de localisation
        $tmp_numero_localisation = null;
        foreach ($this->liste_localisations as $key => $localisation) {
            if ($localisation['id'] == $localisations) {
                $tmp_numero_localisation = $localisation['numero_localisation'];
            }
        }
        // Création du message déterminant la demande
        $messageTemporaire = $this->getMessageRequete($tmp_numero_localisation, $message_idModule, $codeVal1, $val1min, $val1max, $codeVal2, $val2min, $val2max);
        // ____________________________________________________________________________________________________
        // Parcours de la liste des demandes précédentes : Si une requête identique est déjà demandée on ne prend pas en compte la nouvelle
        // Vérification d'Enregistrement de la nouvelle requête : Passe à False si une requête avec un message identique ( = des paramètres identiques ) existe déjà
        $reg = true;
        foreach ($liste_req as $key => $req) {
            if ($messageTemporaire == $req['Texte']) {
                $reg = false;
            }
        }
        // Si c'est une nouvelle requête : Enregistrement de celle-ci
        if ($reg == true) {
        	// Récupération du nombre de requêtes demandées = Nombre de requêtes déjà enregistré + Nouvelle requête demandée
          	// Modification du 23/04/2015 : Plus de restriction limite du nombre de requête
          	$tmpNumeroDeRequete                                         = count($liste_req);
          	//  Sauvegarde des paramètres de la demande
          	$liste_req[$tmpNumeroDeRequete]['id_localisations']         = $localisations;
            $liste_req[$tmpNumeroDeRequete]['idModule']                 = $idModules;
            $liste_req[$tmpNumeroDeRequete]['codeVal1']                 = $codeVal1;
            $liste_req[$tmpNumeroDeRequete]['val1min']                  = $val1min;
            $liste_req[$tmpNumeroDeRequete]['val1max']                  = $val1max;
            $liste_req[$tmpNumeroDeRequete]['codeVal2']                 = $codeVal2;
            $liste_req[$tmpNumeroDeRequete]['val2min']                  = $val2min;
            $liste_req[$tmpNumeroDeRequete]['val2max']                  = $val2max;
            $liste_req[$tmpNumeroDeRequete]['Texte']                    = $messageTemporaire;   // Message de la requête
            $liste_req[$tmpNumeroDeRequete]['Localisation']             = $tmp_numero_localisation;     //null;
            $liste_req[$tmpNumeroDeRequete]['TexteRecherche']           = null;                 //Texte indiquant la recherche effectuée / All /Moyenne à l'heure/ A la minute etc.
            // Redéfinition de la variable de session avec la nouvelle demande
            switch ($typePopup) {
                case 'combustibleB1':
                    $this->session->set('liste_req_etat_combustibleB1', $liste_req);
                    break;
                case 'combustibleB2':
                    $this->session->set('liste_req_etat_combustibleB2', $liste_req);
                    break;
                case 'compteur':
                    $this->session->set('liste_req_etat_compteur', $liste_req);
                    break;
                case 'test':
                    $this->session->set('liste_req_etat_test', $liste_req);
                    break;
                case 'forcage':
                    $this->session->set('liste_req_etat_forcage', $liste_req);
                    break;
            }
        }
    } else {
        // Lors du clic sur RAZ : Suppression des variables de session
        $this->session->remove('liste_req_etat_combustibleB1');
        $this->session->remove('liste_req_etat_combustibleB2');
        $this->session->remove('liste_req_etat_compteur');
        $this->session->remove('liste_req_etat_test');
        $this->session->remove('liste_req_etat_forcage');
        $liste_req_etat_combustibleB1 = $this->session->get('liste_req_etat_combustibleB1');
        $liste_req_etat_combustibleB2 = $this->session->get('liste_req_etat_combustibleB2');
        $liste_req_etat_compteur = $this->session->get('liste_req_etat_compteur');
        $liste_req_etat_test = $this->session->get('liste_req_etat_test');
        $liste_req_etat_forcage = $this->session->get('liste_req_etat_forcage');
        return new Response();
    }
    $dbh = $this->connexion->disconnect();
    // - - - -- - - - - - - - - - -- - - - - --    PARTIE REMPLACEMENT du numéro de localisation par l'intitulé de la localisation pour l'affichage
    // Pour chaque courbe, si une Localisation est définie, remplacement du numéro par l'intitulé
    // Création du tableau de conversion : N°localisation => Intitulé
    foreach ($this->liste_localisations as $key => $localisation) {
        $tabConversionLoc[$localisation['numero_localisation']] = $localisation['designation'];
    }
    foreach ($liste_req as $key => $requete) {
        $pattern = '/Localisation : \[(.+?)\]/';
        if (preg_match($pattern, $requete['Texte'], $tab_numeroLoc)) {
            $numero_localisation = $tab_numeroLoc[1];
            // Si Toutes les localisations sur tous les sites est demandé
            if ($numero_localisation == 'every') {
                $remplacement = 'Tous Sites-Toutes localisations';
            } else {
                $remplacement = $tabConversionLoc[$numero_localisation];
            }
            $liste_req[$key]['Texte'] = preg_replace($pattern, $remplacement, $requete['Texte']);
        } else {
            $liste_req[$key]['Texte'] = 'Toutes localisations : '.$requete['Texte'];
        }
    }
    //  - - - -- - - - - - - - - - -- - - - - --    PARTIE REMPLACEMENT du numéro de localisation par l'intitulé de la localisation pour l'affichage
    if (count($this->liste_messages_modules) == 0) {
        $this->get('session')->getFlashBag()->add('info', "Etat : Aucun module défini pour les localisations du site courant : Veuillez importer la/les table(s) d'échanges");
        return $this->redirect($this->generateUrl('ipc_prog_homepage'));
    }
    $tab_requetes = array();
    $num_requete = 0;
    foreach ($liste_req as $requete) {
        $code = null;
        $message = '';
        switch ($requete['Localisation']) {
            case 'all':
                $localisation = 'Toutes';
                break;
            case 'every':
                $localisation = 'Toutes';
                break;
            default:
                $localisation = $requete['Localisation'];
                break;
        }
        if (! isset($tab_requetes[$num_requete])) {
            $tab_requetes[$num_requete] = array();
        }
        $pattern_message = '/^(.+?) : (.+?)_(.+?)$/';
        if (preg_match($pattern_message, $requete['Texte'], $tab_retour)) {
            $code = $tab_retour[2];
            $message = $tab_retour[3];
        }
        $tab_requetes[$num_requete]['message'] = $message;
        $tab_requetes[$num_requete]['code'] = $code;
        $tab_requetes[$num_requete]['localisation'] = $localisation;
        $tab_requetes[$num_requete]['numrequete'] = $num_requete;
        $tab_requetes[$num_requete]['idModule'] = $requete['idModule'];
        $tab_requetes[$num_requete]['conditions'] = $requete['idModule'].'_'.$requete['codeVal1'].'_'.$requete['val1min'].'_'.$requete['val1max'];
        $num_requete ++;
    }
    echo json_encode($tab_requetes);
    return new Response();
}



// Page d'accueil du module Etat : Appelée lors du clic sur le menu Etat
private function afficheEtatAccueil() {
	$this->constructeur();
    $fileToInclude  = 'IpcEtatBundle:Etat:afficheEtat.html.twig';
    $this->titre_page_etat = 'Etat';
    $response = new Response($this->renderView('IpcEtatBundle:Etat:accueil.html.twig', array(
    	'titrePageEtat' => $this->titre_page_etat,
        'tabCalculs' => $this->tab_calculs,
        'tabEtats' => $this->tab_etats,
       	'fileToInclude' => $fileToInclude,
		'sessionCourante' => $this->session->getSessionName(),
        'tabSessions' => $this->session->getTabSessions()
        ))
    );
    $response->setPublic();
    $response->setETag(md5($response->getContent()));
    return $response;
}


// préparation de la popup de la page Etat
public function prepareAction() {
	$this->constructeur();
	$this->initialisation();
	$autorisation_acces = $this->initialisationListes();
	$heure_debut = strtotime(date('Y-m-d h:i:s'));
	$session_date = $this->session->get('session_date');
	$tabDesDonnees = array();
	$tabDesRequetes = array();
	$message_erreur = '';
	$em = $this->getDoctrine()->getManager();
	$service_numbers = $this->get('ipc_prog.fillnumbers');
	if (! empty($session_date)) {
        setlocale (LC_TIME, 'fr_FR.utf8','fra');
    }
	// Service de modification du format des chiffres pour les afficher sur 2 caractères : ex -> (entrée) 1 <- (sortie) 01
	$fillnumbers = $this->get('ipc_prog.fillnumbers');
	// Récupération du Service de connexion à la base de donnée IPC & de l'objet représentant la connexion
	$dbh = $this->dbh;
	// Service de récupération de la requête entrante
	$request = $this->get('request');
	// liste des requêtes demandées par l'utilisateur
	//$liste_req = $this->session->get('liste_req_etat_compteur');
	//$correspondance_message_code = $this->session->get('correspondance_Message_Code');
	//$session_idmodules = $this->session->get('session_idmodules');
	$configuration = new Configuration();
	$maximum_execution_time = $configuration->SqlGetParam($dbh,'maximum_execution_time');
	// La requête d'appelle de la fonction provient du formulaire de la page (analyse....html.twig)
	$dbh = $this->connexion->disconnect();
	$popup_simplifiee = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('popup_simplifiee')->getValeur();
	$response = new Response(
	$this->renderView('IpcProgBundle:Prog:popupMessages.html.twig', array(
			'page_appelante' => 'etat',
			'popup_simplifiee' => $popup_simplifiee,
            'liste_localisations' => $this->liste_localisations,
            'last_loc_id' => $this->last_loc_etat_id,
            'liste_genres' => $this->liste_genres,
            'liste_nomsModules' => $this->liste_noms_modules,
            'liste_messagesModules' => $this->liste_messages_modules,
            'maximum_execution_time' => $maximum_execution_time
        ))
    );
    $response->setPrivate();
    $response->setMaxAge(3600);
    $response->setETag(md5($response->getContent()));
    $response->headers->addCacheControlDirective('must-revalidate', true);
    return $response;
}


// Recherche des dates de l'etat
public function afficheEtat1AccueilAction($idEtat) {
	$this->constructeur();
	$this->initialisation();

	$type_periode;

	$requete = $this->get('request');
	$champs_etat_date = new EtatDate();
	if ($idEtat != 0) {
		$champs_etat_date->setId($idEtat);
	}
    $form = $this->createForm(new EtatDateType, $champs_etat_date);
	if ($requete->getMethod() == 'POST') {
        if ($form->handleRequest($requete)->isValid()) {
			$idEtat = $champs_etat_date->getId();
			if ($champs_etat_date->getChampsDateDebut() > $champs_etat_date->getChampsDateFin()) {
				$this->get('session')->getFlashBag()->add('info', 'Les dates sont inversées. Nouvelle analyse non créée');
			} else {
				$nouvelle_periode = 'Du;'.$champs_etat_date->getChampsDateDebutStr().';au;'.$champs_etat_date->getChampsDateFinStr();
				$entity_etat = $this->em->getRepository('IpcProgBundle:Etat')->find($champs_etat_date->getId());
				$entity_etat->setPeriode($nouvelle_periode);
				$entity_etat->setActive(true);
				$dateJour = new \Datetime(date('Y/m/d H:i:s'));
				$entity_etat->setDateActivation($dateJour);
				$this->em->flush();

				$dateDemain = date('d/m/Y', strtotime('+1 day'));
				$this->get('session')->getFlashBag()->add('info', "Analyse programmée pour le $dateDemain");
			}
        } else {
			$message_erreur = "Erreur : ".$form->getErrorsAsString();
			$this->get('session')->getFlashBag()->add('info', $message_erreur);
		}
    }

	$dossier_etat = $this->getEtatDir().'etat_'.$idEtat;
	$tab_des_dates = array();
	$tab_contenu_dossier = scandir($dossier_etat);
	foreach($tab_contenu_dossier as $contenu) {
		if (is_dir($dossier_etat.'/'.$contenu)){
			if (($contenu != '.') && ($contenu != '..')) {
				if (preg_match('/^(....)(..)(..)$/', $contenu, $tab_du_contenu)) {
					$tab_des_dates[$contenu] = $tab_du_contenu[3].'/'.$tab_du_contenu[2].'/'.$tab_du_contenu[1];
				} else if (preg_match('/^(....)(..)(..)_(....)(..)(..)$/', $contenu, $tab_du_contenu)) {
                    $tab_des_dates[$contenu] = $tab_du_contenu[3].'/'.$tab_du_contenu[2].'/'.$tab_du_contenu[1].'_'.$tab_du_contenu[6].'/'.$tab_du_contenu[5].'/'.$tab_du_contenu[4];
				}
			}
		}
	}
	$entity_etat = $this->getDoctrine()->getManager()->getRepository('IpcProgBundle:Etat')->find($idEtat);
	$intitule = $entity_etat->getIntitule();
	// Récupération et formatage de la période
	$periode = $entity_etat->getPeriode();
	$message_periode = '';
	// Si la période et de type : Du ... au ...  Du;2017/01/30 00:00:00;au;2017/02/01 15:00:00
	if (substr($periode, 0, 2) == 'Du'){
		// Si il existe un dossier avec la date de début de période: Affichage des informations de ce dossier sinon affichage du premier dossier
		$message_periode = "Recherche sur une période définie";
		$type_periode = 'unique';
	}
	else {
		// Si la période est au format: frequence_7_jour;periode_7_jour	 -> Retour = Recherche sur une période de 7 jours
		$tab_periode = explode(';', $periode);
		$tab_intervalle_periode = explode('_', $tab_periode[1]);
		$message_periode = 'Recherche sur une période de '.$tab_intervalle_periode[1].' '.$tab_intervalle_periode[2];	
		if ( ($tab_intervalle_periode[1] > 1) && ($tab_intervalle_periode[2] != 'mois') ) {
			$message_periode .= 's';	
		}
		$type_periode = 'periodique';
	}
   	$fileToInclude = 'IpcEtatBundle:Etat:afficheEtat.html.twig';
   	$this->titre_page_etat = 'Etat';
   	$response = new Response($this->renderView('IpcEtatBundle:Etat:accueilEtat1.html.twig', array(
		'form' => $form->createView(),
		'idEtat' => $idEtat,
		'intitule' => $intitule,
		'typePeriode' => $type_periode,
		'messagePeriode' => $message_periode,
		'tab_des_dates' => $tab_des_dates,
	    'titrePageEtat' => $this->titre_page_etat,
	    'tabCalculs' => $this->tab_calculs,
	    'tabEtats' => $this->tab_etats,
	    'fileToInclude' => $fileToInclude,
   		'sessionCourante' => $this->session->getSessionName(),
       	'tabSessions' => $this->session->getTabSessions()
    )));
   	$response->setPublic();
   	$response->setETag(md5($response->getContent()));
   	return $response;
}

// Fonction qui affiche les informations concernant l'état 1
public function afficheEtat1Action($idEtat, $dateEtat) {
	$this->constructeur();
    	$this->initialisation();
    // Récupération de l'état à afficher
    $entity_etat = $this->em->getRepository('IpcProgBundle:Etat')->find($idEtat);
    $etat_titre = $entity_etat->getIntitule();
    $file_resume = $this->getEtatDir().'etat_'.$idEtat.'/'.$dateEtat.'/resume.csv';
    // Lecture fichier 1
    $fp_resume = fopen($file_resume, 'r');
    $num_ligne = 1;
    $etat_tempsModule3 = null;
    $etat_pourcentModule3 = null;
    $etat_pourcentModule31 = null;
    $etat_secondsModule3 = null;
    $etat_MaxRearmementOccurences = null;
    $etat_MaxRearmementDateDeb = null;
    $etat_MaxRearmementDateFin = null;
    $messageRearmementMax = null;
    $messageRearmementMoy = null;
    // Pour une chaudière monofoyée n'ayant qu'un bruleur on ne fait pas comparaison des temps bruleurs
    $etat_tempsModuleBruleurs = null;
    $tabPieCC = array();
    while (($tab_ligne = fgetcsv($fp_resume, 4096, ';')) != false) {
       switch ($num_ligne) {
        case 1:
            $foyer = $tab_ligne[2];
        case 2:
            $etat_periode = 'Du '.$this->service_fillNumbers->formaterDate($tab_ligne[1], 'human').' au '.$this->service_fillNumbers->formaterDate($tab_ligne[2], 'human');
            case 5:
            if ($foyer == 'monofoyer') {
                $etat_tempsTotal = $tab_ligne[1];
                $etat_secondsTotal = $tab_ligne[2];
            }
            break;
            case 6:
            if ($foyer == 'monofoyer') {
                $etat_tempsModule1 = $tab_ligne[1];
                $etat_pourcentModule1 = $tab_ligne[2];
                $etat_secondsModule1 = $tab_ligne[3];
            } else {
            	$etat_tempsTotal = $tab_ligne[1];
                $etat_secondsTotal = $tab_ligne[2];
            }
            break;
        case 7:
            if ($foyer == 'monofoyer') {
            	$etat_tempsModule2 = $tab_ligne[1];
                $etat_pourcentModule2 = $tab_ligne[2];
                $etat_pourcentModule21 = $tab_ligne[3];
                $etat_secondsModule2 = $tab_ligne[4];
            } else {
                $etat_tempsModule1 = $tab_ligne[1];
                $etat_pourcentModule1 = $tab_ligne[2];
                $etat_secondsModule1 = $tab_ligne[3];
            }
            break;
        case 8:
            if ($foyer == 'monofoyer') {
            	$etat_MaxRearmementOccurences = $tab_ligne[1];
            	$etat_MaxRearmementDateDeb = $tab_ligne[2];
            	$etat_MaxRearmementDateFin = $tab_ligne[3];
            	$etat_MaxRearmementMoyenne = $tab_ligne[4];
            } else {
                $etat_tempsModule2 = $tab_ligne[1];
                $etat_pourcentModule2 = $tab_ligne[2];
                $etat_pourcentModule21 = $tab_ligne[3];
                $etat_secondsModule2 = $tab_ligne[4];
            }
            break;
        case 9:
            if ($foyer == 'monofoyer') {
                $etat_tempsModuleBruleurs = $tab_ligne[1];
                $etat_pourcentModuleBruleurs = $tab_ligne[2];
                $etat_pourcentModuleBruleurs1 = $tab_ligne[3];
                $etat_secondsModuleBruleurs = $tab_ligne[4];
            } else {
                $etat_tempsModule3 = $tab_ligne[1];
                $etat_pourcentModule3 = $tab_ligne[2];
                $etat_pourcentModule31 = $tab_ligne[3];
                $etat_secondsModule3 = $tab_ligne[4];
            }
            break;
        case 10:
            if ($foyer == 'bifoyer') {
                $etat_tempsModuleBruleurs = $tab_ligne[1];
                $etat_pourcentModuleBruleurs = $tab_ligne[2];
                $etat_pourcentModuleBruleurs1 = $tab_ligne[3];
                $etat_secondsModuleBruleurs = $tab_ligne[4];
            }
            break;
        case 11:
            if ($foyer == 'bifoyer') {
                $etat_MaxRearmementOccurences = $tab_ligne[1];
                $etat_MaxRearmementDateDeb = $tab_ligne[2];
                $etat_MaxRearmementDateFin = $tab_ligne[3];
            	$etat_MaxRearmementMoyenne = $tab_ligne[4];
            }
            break;
        }
        $num_ligne ++;
    }
    if($etat_MaxRearmementDateDeb != null)
    {
        $messageRearmementMax = "$etat_MaxRearmementOccurences réarmements successif avant redémarrage du bruleur suite à un défaut du ".$this->service_fillNumbers->formaterDate($etat_MaxRearmementDateDeb, 'en')." au ".$this->service_fillNumbers->formaterDate($etat_MaxRearmementDateFin, 'en');
        $messageRearmementMoy = "Nombre moyen de réarmements par défaut : $etat_MaxRearmementMoyenne";
    }
    fclose($fp_resume);

    //  Récupération des intitulés des modules de l'Etat
    $tab_designations = explode(';', $entity_etat->getListeDesignations());
    $etat_designation1 = $tab_designations[0];
    $etat_designation2 = $tab_designations[1];
    $etat_designation3 = null;
    if ($foyer == 'bifoyer') {
        $etat_designation3  = $tab_designations[2];
    }
    // Récupération des informations concernant les compteurs
    $tab_compteurs = array();
    // Listes des fichiers dont les courbes sont à afficher
    $tab_fichiers = array();
    $fichier_resumeCompteurs = $this->getEtatDir().'etat_'.$idEtat.'/'.$dateEtat.'/resumeCompteur.csv';
	//  Le caractère @ supprime le warning lorsque le fichier n'existe pas
    $fp_resumeCompteur = @fopen($fichier_resumeCompteurs, 'r'); 
    if ($fp_resumeCompteur != false) {
        while (($tab_ligne = fgetcsv($fp_resumeCompteur, 4096, ';')) != false) {
            $tab_compteurs[$tab_ligne[0]]['message'] = $tab_ligne[1];
            $tab_compteurs[$tab_ligne[0]]['unite'] = $tab_ligne[2];
            $tab_compteurs[$tab_ligne[0]]['valDebut'] = $this->formatNumber($tab_ligne[3]);
            $tab_compteurs[$tab_ligne[0]]['valFin'] = $this->formatNumber($tab_ligne[4]);
            $tab_compteurs[$tab_ligne[0]]['compteur'] = $this->formatNumber($tab_ligne[5]);
        	$tab_compteurs[$tab_ligne[0]]['moyenneHeure'] = $this->formatNumber($tab_ligne[6]);
        	$tab_compteurs[$tab_ligne[0]]['moyenneJour'] = $this->formatNumber($tab_ligne[7]);
        	$tab_compteurs[$tab_ligne[0]]['moyenneHeureAB'] = $this->formatNumber($tab_ligne[8]);
        	$tab_compteurs[$tab_ligne[0]]['moyenneJourAB'] = $this->formatNumber($tab_ligne[9]);
            $tab_fichiers[$tab_ligne[0]]['jour'] = 'etat_'.$idEtat.'/graphique_'.$tab_ligne[0].'_jour.csv';
            $tab_fichiers[$tab_ligne[0]]['heure'] = 'etat_'.$idEtat.'/graphique_'.$tab_ligne[0].'_heure.csv';
        }
        fclose($fp_resumeCompteur);
    }
    // Récupération des données des Tests
    $tab_tests = array();
    // Listes des fichiers dont les courbes sont à afficher
    //$tab_fichiers = array();
    $fichier_resumeTest = $this->getEtatDir().'etat_'.$idEtat.'/'.$dateEtat.'/resumeTest.csv';
	// Le caractère @ supprime le warning lorsque le fichier n'existe pas
    $fp_resumeTest = @fopen($fichier_resumeTest, 'r');
    if ($fp_resumeTest != false) {
        while (($tab_ligne = fgetcsv($fp_resumeTest, 4096, ';')) != false) {
            $tab_tests[$tab_ligne[0]]['message'] = $tab_ligne[1];
            $tab_tests[$tab_ligne[0]]['occurences'] = $tab_ligne[2];
			$tab_tests[$tab_ligne[0]]['ecartMax'] = $this->transformeTexteDuree($tab_ligne[3]);
        }
        fclose($fp_resumeTest);
    }
    // Récupération des données des Forçages
    $tab_forcages = array();
    // Listes des fichiers dont les courbes sont à afficher
    //$tab_fichiers = array();
    $fichier_resumeForcage = $this->getEtatDir().'etat_'.$idEtat.'/'.$dateEtat.'/resumeForcage.csv';
    $fp_resumeForcage = @fopen($fichier_resumeForcage, 'r');
    if ($fp_resumeForcage != false) {
        while (($tab_ligne = fgetcsv($fp_resumeForcage, 4096, ';')) != false) {
            $tab_forcages[$tab_ligne[0]]['message'] = $tab_ligne[1];
            $tab_forcages[$tab_ligne[0]]['occurences'] = $tab_ligne[2];
        }
        fclose($fp_resumeForcage);
	}

    // Récupération des informations concernant les combustibles

	if ($foyer == 'bifoyer'){
	        $titre_combustible1 = "Analyse des combustibles du brûleur 1";
	        $titre_combustible2 = "Analyse des combustibles du brûleur 2";
	} else {
	        $titre_combustible1 = "Analyse des combustibles du brûleur";
			$titre_combustible2 = null;
	}


    $tab_combustiblesB1 = array();
	$tabPieCombustibleB1 = array();

    $fichier_resumeCombustiblesB1 = $this->getEtatDir().'etat_'.$idEtat.'/'.$dateEtat.'/resumeCombustibleB1.csv';
    // Le caractère @ supprime le warning lorsque le fichier n'existe pas
    $fp_resumeCombustiblesB1 = @fopen($fichier_resumeCombustiblesB1, 'r');
    if ($fp_resumeCombustiblesB1 != false) {
		$compteur = 0;
		$numLigne = 1;
		$ligneCombustible = 0;
        while (($tab_ligne = fgetcsv($fp_resumeCombustiblesB1, 4096, ';')) != false) {
			if ($numLigne == 1) {
				$tab_combustiblesB1[$compteur]['periode'] = $tab_ligne[1];
			}else if ($numLigne == 2) {
				$tab_combustiblesB1[$compteur]['messageBruleur'] = $tab_ligne[1];
			}else if ($numLigne == 3) {
                		$tab_combustiblesB1[$compteur]['periodeTexte'] = $this->transformeTexteDuree($tab_ligne[1]);
				$tab_combustiblesB1[$compteur]['periodeSeconde'] = $tab_ligne[2];
            }else if ($tab_ligne[0] == 'Nouveau combustible') {
					$compteur ++;
					$ligneCombustible = 1;
			}else{
				if ($ligneCombustible == 1) {
					$tab_combustiblesB1[$compteur]['messageCombustible'] = $tab_ligne[1];	
					$ligneCombustible ++;
				} else if ($ligneCombustible == 2) {
					$tab_combustiblesB1[$compteur]['periodeTexte'] = $this->transformeTexteDuree($tab_ligne[1]);
					$tab_combustiblesB1[$compteur]['pourcentage'] = $this->formatNumber($tab_ligne[2]);
					$tab_combustiblesB1[$compteur]['pourcentageBA'] = $this->formatNumber($tab_ligne[3]);
					$tab_combustiblesB1[$compteur]['periodeSeconde'] = $tab_ligne[4];	

					$tabPieCombustibleB1[$compteur - 1] = array();
					$tabPieCombustibleB1[$compteur - 1]['name'] = $tab_combustiblesB1[$compteur]['messageCombustible'];
					$tabPieCombustibleB1[$compteur - 1]['y'] = intval($tab_combustiblesB1[$compteur]['periodeSeconde']);

					$ligneCombustible ++;
				}
			}
			$numLigne ++;
        }
        fclose($fp_resumeCombustiblesB1);
    }
    if (sizeof($tab_combustiblesB1 == 1)){
        $tab_combustiblesB1 = array();
    }

    $tab_combustiblesB2 = array();
        $tabPieCombustibleB2 = array();

    $fichier_resumeCombustiblesB2 = $this->getEtatDir().'etat_'.$idEtat.'/'.$dateEtat.'/resumeCombustibleB2.csv';
    // Le caractère @ supprime le warning lorsque le fichier n'existe pas
    $fp_resumeCombustiblesB2 = @fopen($fichier_resumeCombustiblesB2, 'r');
    if ($fp_resumeCombustiblesB2 != false) {
        $compteur = 0;
        $numLigne = 1;
	$ligneCombustible = 0;
        while (($tab_ligne = fgetcsv($fp_resumeCombustiblesB2, 4096, ';')) != false) {
            if ($numLigne == 1) {
                $tab_combustiblesB2[$compteur]['periode'] = $tab_ligne[1];
            }else if ($numLigne == 2) {
                $tab_combustiblesB2[$compteur]['messageBruleur'] = $tab_ligne[1];
            }else if ($numLigne == 3) {
                $tab_combustiblesB2[$compteur]['periodeTexte'] = $this->transformeTexteDuree($tab_ligne[1]);
                $tab_combustiblesB2[$compteur]['periodeSeconde'] = $tab_ligne[2];
            }else if ($tab_ligne[0] == 'Nouveau combustible') {
                    $compteur ++;
                    $ligneCombustible = 1;
            }else{
                if ($ligneCombustible == 1) {
                    $tab_combustiblesB2[$compteur]['messageCombustible'] = $tab_ligne[1];
                    $ligneCombustible ++;
                } else if ($ligneCombustible == 2) {
                    $tab_combustiblesB2[$compteur]['periodeTexte'] = $this->transformeTexteDuree($tab_ligne[1]);
                    $tab_combustiblesB2[$compteur]['pourcentage'] = $this->formatNumber($tab_ligne[2]);
                    $tab_combustiblesB2[$compteur]['pourcentageBA'] = $this->formatNumber($tab_ligne[3]);
                    $tab_combustiblesB2[$compteur]['periodeSeconde'] = $tab_ligne[4];

                    $tabPieCombustibleB2[$compteur - 1] = array();
                    $tabPieCombustibleB2[$compteur - 1]['name'] = $tab_combustiblesB2[$compteur]['messageCombustible'];
                    $tabPieCombustibleB2[$compteur - 1]['y'] = intval($tab_combustiblesB2[$compteur]['periodeSeconde']);

                    $ligneCombustible ++;
                }
            }
            $numLigne ++;
        }
        fclose($fp_resumeCombustiblesB2);
    }
    if (sizeof($tab_combustiblesB2 == 1)){
        $tab_combustiblesB2 = array();
    }


	// Nombre de défauts restant sur la période : Nombre total - Défauts réccurents - Somme des 10 défauts les plus courants
	// Récupération des Défauts les plus courants
	$tab_defauts = array();
	$tab_defauts_titre = array();
	$file_defauts = $this->getEtatDir().'etat_'.$idEtat.'/'.$dateEtat.'/mostDefauts.csv';
	$fp_defauts = @fopen($file_defauts, 'r');
	$num_ligne = 1;
	$num_titre = 0;
	$somme_des_defauts_affiches = 0;
	while (($tab_ligne = fgetcsv($fp_defauts, 4096, ';')) != false) {
		if ($num_ligne == 1) {
			$titre_defauts = $tab_ligne[1];
			$occurences_defauts = $tab_ligne[2];
		} else {
			if ($tab_ligne[0] == 'T') {
				$tab_defauts_titre[$num_titre] = array();
				$tab_defauts_titre[$num_titre]['name'] = $tab_ligne[1];
				$tab_defauts_titre[$num_titre]['code'] = $this->getCodeFromTexte($tab_ligne[1], 0, null);
				$tab_defauts_titre[$num_titre]['designation'] = trim($this->getCodeFromTexte($tab_ligne[1], 1, 2));
				$tab_defauts_titre[$num_titre]['y'] = intval($tab_ligne[2]);
				// Incrémentation de l'indice du tableau des titres
				$num_titre ++;
				// Annulation de l'incrémentation de l'indice du tableau des messages
				$num_ligne --;
			} else {
				// -1 pour la ligne de titre et -1 pour débuter offset 0
				$tab_defauts[$num_ligne - 2] = array();
				$tab_defauts[$num_ligne - 2]['name'] = $tab_ligne[0];
				$tab_defauts[$num_ligne - 2]['code'] = $this->getCodeFromTexte($tab_ligne[0], 0, null);
				$tab_defauts[$num_ligne - 2]['designation'] = trim($this->getCodeFromTexte($tab_ligne[0], 1, 2));
				$tab_defauts[$num_ligne - 2]['y'] = intval($tab_ligne[1]);
				// Calcul de la somme des défauts affichés
				$somme_des_defauts_affiches += intval($tab_ligne[1]);
			}
		}
		$num_ligne ++;
	}
	fclose($fp_defauts);
	// Nombre de défauts restant sur la période : Nombre total - Somme des 10 défauts les plus courants
	$tab_defauts[$num_ligne - 2] = array();
	$tab_defauts[$num_ligne - 2]['name'] = 'Autres défauts';
	$tab_defauts[$num_ligne - 2]['y'] = intval($occurences_defauts) - $somme_des_defauts_affiches;

	// Récupération des Alarmes les plus courantes
	$tab_alarmes = array();
	$tab_alarmes_titre = array();
	$file_alarmes = $this->getEtatDir().'etat_'.$idEtat.'/'.$dateEtat.'/mostAlarmes.csv';
	$fp_alarmes = @fopen($file_alarmes, 'r');
	$num_ligne = 1;
	$num_titre = 0;
	$somme_des_alarmes_affichees = 0;
	while (($tab_ligne = fgetcsv($fp_alarmes, 4096, ';')) != false) {
		if ($num_ligne == 1) {
			$titre_alarmes = $tab_ligne[1];
			$occurences_alarmes = $tab_ligne[2];
		} else {
	        if ($tab_ligne[0] == 'T') {
            	$tab_alarmes_titre[$num_titre] = array();
                $tab_alarmes_titre[$num_titre]['name'] = $tab_ligne[1];
            	$tab_alarmes_titre[$num_titre]['code'] = $this->getCodeFromTexte($tab_ligne[1], 0, null);
            	$tab_alarmes_titre[$num_titre]['designation'] = trim($this->getCodeFromTexte($tab_ligne[1], 1, 2)); 
                $tab_alarmes_titre[$num_titre]['y'] = intval($tab_ligne[2]);
            	// Incrémentation de l'indice du tableau des titres
                $num_titre ++;
            	// Annulation de l'incrémentation de l'indice du tableau des messages
            	$num_ligne --;
        	} else {
                $tab_alarmes[$num_ligne - 2] = array();
            	// Le nom correspond au nom sans le code:
                $tab_alarmes[$num_ligne - 2]['name'] = $tab_ligne[0];
                $tab_alarmes[$num_ligne - 2]['y'] = intval($tab_ligne[1]);
            	$tab_alarmes[$num_ligne - 2]['code'] = $this->getCodeFromTexte($tab_ligne[0], 0, null);
            	$tab_alarmes[$num_ligne - 2]['designation'] = trim($this->getCodeFromTexte($tab_ligne[0], 1, 2)); 
            	// Calcul de la somme des alarmes affichées
                $somme_des_alarmes_affichees += intval($tab_ligne[1]);
        	}
        }
        $num_ligne ++;
    }
    fclose($fp_alarmes);
    // Nombre d'alarmes restantes sur la période : Nombre total - Somme des 10 alarmes les plus courantes
    $tab_alarmes[$num_ligne - 2] = array();
    $tab_alarmes[$num_ligne - 2]['name'] = 'Autres alarmes';
    $tab_alarmes[$num_ligne - 2]['code'] = null;
    $tab_alarmes[$num_ligne - 2]['designation'] = 'Autres alarmes';
    $tab_alarmes[$num_ligne - 2]['y'] = intval($occurences_alarmes) - $somme_des_alarmes_affichees;


    // Récupération des Anomalies de régulation les plus courantes
    $tab_anomaliesR = array();
    $titre_anomaliesR = '';
    $occurences_anomaliesR = 0;
    $file_anomaliesR = $this->getEtatDir().'etat_'.$idEtat.'/'.$dateEtat.'/mostAnomaliesR.csv';
    $fp_anomaliesR = @fopen($file_anomaliesR, 'r');
    // Si le fichier n'existe pas: cad aucune anomalie détectée.
    if ($fp_anomaliesR != false) {
	    $num_ligne = 1;
	    $num_titre = 0;
	    $somme_des_anomaliesR_affichees = 0;
	    while (($tab_ligne = fgetcsv($fp_anomaliesR, 4096, ';')) != false) {
	        if ($num_ligne == 1) {
	            $titre_anomaliesR = $tab_ligne[1];
	            $occurences_anomaliesR = $tab_ligne[2];
	        } else {
	        	$tab_anomaliesR[$num_ligne - 2] = array();
	        	// Le nom correspond au nom sans le code:
	        	$tab_anomaliesR[$num_ligne - 2]['name'] = $tab_ligne[0];
	        	$tab_anomaliesR[$num_ligne - 2]['y'] = intval($tab_ligne[1]);
	        	$tab_anomaliesR[$num_ligne - 2]['code'] = $this->getCodeFromTexte($tab_ligne[0], 0, null);
				$tab_anomaliesR[$num_ligne - 2]['designation'] = trim($this->getCodeFromTexte($tab_ligne[0], 1, 2));
	        	// Calcul de la somme des alarmes affichées
	        	$somme_des_anomaliesR_affichees += intval($tab_ligne[1]);
		}
	        $num_ligne ++;
	    }
	    fclose($fp_anomaliesR);
	    // Nombre d'anomalies restantes sur la période : Nombre total - Somme des 10 alarmes les plus courantes
	    $tab_anomaliesR[$num_ligne - 2] = array();
	    $tab_anomaliesR[$num_ligne - 2]['name'] = 'Autres anomalies';
	    $tab_anomaliesR[$num_ligne - 2]['code'] = null;
	    $tab_anomaliesR[$num_ligne - 2]['designation'] = 'Autres anomalies';
	    $tab_anomaliesR[$num_ligne - 2]['y'] = intval($occurences_anomaliesR) - $somme_des_anomaliesR_affichees;
    }

    // Camembert temps chaudière / temps total
    $tabPieCT = array();
    $tabPieCT[0] = array();
    $tabPieCT[0]['name'] = "Temps restant";
    $tabPieCT[0]['y'] = $etat_secondsTotal - $etat_secondsModule1;
    $tabPieCT[1] = array();
    $tabPieCT[1]['name'] = $etat_designation1;
    $tabPieCT[1]['y'] = intval($etat_secondsModule1);
    // Camembert temps burleur1 / temps total
    $tabPieB1T = array();
    $tabPieB1T[0] = array();
    $tabPieB1T[0]['name'] = "Temps restant";
    $tabPieB1T[0]['y'] = $etat_secondsTotal - $etat_secondsModule2;
    $tabPieB1T[1] = array();
    $tabPieB1T[1]['name'] = $etat_designation2;
    $tabPieB1T[1]['y'] = intval($etat_secondsModule2);
    // Camembert temps bruleur1 / temps chaudière
    $tabPieB1C = array();
    $tabPieB1C[0] = array();
    $tabPieB1C[0]['name'] = "Temps restant";
    $tabPieB1C[0]['y'] = $etat_secondsModule1 - $etat_secondsModule2;
    $tabPieB1C[1] = array();
    $tabPieB1C[1]['name'] = $etat_designation2;
    $tabPieB1C[1]['y'] = intval($etat_secondsModule2);
    // Camembert temps bruleur2 / temps total
    $tabPieB2T = array();
    // Camembert temps bruleur2 / temps chaudière
    $tabPieB2C         = array();
    if ($foyer == 'bifoyer') {
        // Camembert temps bruleur2 / temps total
        $tabPieB2T[0] = array();
        $tabPieB2T[0]['name'] = "Temps restant";
        $tabPieB2T[0]['y'] = $etat_secondsTotal - $etat_secondsModule3;
        $tabPieB2T[1] = array();
        $tabPieB2T[1]['name'] = $etat_designation3;
        $tabPieB2T[1]['y'] = intval($etat_secondsModule3);
        // Camembert temps bruleur2 / temps chaudière
        $tabPieB2C[0] = array();
        $tabPieB2C[0]['name'] = "Temps restant";
        $tabPieB2C[0]['y'] = $etat_secondsModule1 - $etat_secondsModule3;
        $tabPieB2C[1] = array();
        $tabPieB2C[1]['name'] = $etat_designation3;
        $tabPieB2C[1]['y'] = intval($etat_secondsModule3);
        // Le temps restant correspond au tempsFlamme1-tempsCommun + tempsFlamme2-tempsCommun
        // Camembert temps bruleur1 / temps bruleur2
        $fonctionnementNonCommun  = ($etat_secondsModule2 - $etat_secondsModuleBruleurs) + ($etat_secondsModule3 - $etat_secondsModuleBruleurs);
        $tabPieCC[0] = array();
        $tabPieCC[0]['name'] = "Temps restant";
        $tabPieCC[0]['y'] = $etat_secondsModule1 - $etat_secondsModuleBruleurs - $fonctionnementNonCommun;
        $tabPieCC[1] = array();
        $tabPieCC[1]['name'] = "Fonctionnement non commun des bruleurs";
        $tabPieCC[1]['y'] = $fonctionnementNonCommun;
        $tabPieCC[2] = array();
        $tabPieCC[2]['name'] = "Fonctionnement commun des bruleurs";
        $tabPieCC[2]['y'] = intval($etat_secondsModuleBruleurs);
    }
    $fileToInclude  = 'IpcEtatBundle:Etat:afficheEtat1.html.twig';
	if ($foyer == 'bifoyer') {
    	$jsToInclude = "Etat/etat1_bifoyer_gestionPieType.js";
	} else {
		$jsToInclude = "Etat/etat1_gestionPieType.js";
	}
    $this->titre_page_etat = 'Etat - Analyse de marche';

    //  Lecture fichier 2
    $response = new Response($this->renderView('IpcEtatBundle:Etat:accueil.html.twig', array(
        'titrePageEtat' => $this->titre_page_etat,
        'titre' => $etat_titre,
        'foyer' => $foyer,
        'designation1' => $etat_designation1,
        'designation2' => $etat_designation2,
        'designation3' => $etat_designation3,
        'periode' => $etat_periode,
        'tempsTotal' => $this->transformeTexteDuree($etat_tempsTotal),
        'tempsModule1' => $this->transformeTexteDuree($etat_tempsModule1),
        'tempsModule2' => $this->transformeTexteDuree($etat_tempsModule2),
        'tempsModule3' => $this->transformeTexteDuree($etat_tempsModule3),
        'tempsModulesC' => $this->transformeTexteDuree($etat_tempsModuleBruleurs),
        'pourcentage1' => $etat_pourcentModule1,
        'pourcentage2' => $etat_pourcentModule2,
        'pourcentage3' => $etat_pourcentModule3,
        'pourcentage21' => $etat_pourcentModule21,
        'pourcentage31' => $etat_pourcentModule31,
        'messageRearmementMax' => $messageRearmementMax,
        'messageRearmementMoy' => $messageRearmementMoy,
        'titreDefauts' => $titre_defauts,
        'tabDefauts' => $tab_defauts,
        'tabDefautsTitre' => $tab_defauts_titre,
		'tabCombustiblesB1' => $tab_combustiblesB1,
		'tabCombustiblesB2' => $tab_combustiblesB2,
		'titreCombustible1' => $titre_combustible1,
		'titreCombustible2' => $titre_combustible2,
        'titreAlarmes' => $titre_alarmes,
		'titreAnomaliesR' => $titre_anomaliesR,
        'occurencesAnomaliesR' => $occurences_anomaliesR,
        'occurencesAlarmes' => $occurences_alarmes,
        'occurencesDefauts' => $occurences_defauts,
        'tabAlarmes' => $tab_alarmes,
        'tabAlarmesTitre' => $tab_alarmes_titre,
		'tabAnomaliesR' => $tab_anomaliesR,
        'tabCompteurs' => $tab_compteurs,
        'tabTests' => $tab_tests,
        'tabForcages' => $tab_forcages,
        'tabFichiers' => $tab_fichiers,
        'tabPieCT'  => $tabPieCT,
        'tabPieB1T' => $tabPieB1T,
        'tabPieB1C' => $tabPieB1C,
        'tabPieB2T' => $tabPieB2T,
        'tabPieB2C' => $tabPieB2C,
        'tabPieCC'  => $tabPieCC,
		'tabPieCombustibleB1' => $tabPieCombustibleB1,
		'tabPieCombustibleB2' => $tabPieCombustibleB2,
        'tabCalculs' => $this->tab_calculs,
        'tabEtats' => $this->tab_etats,
        'fileToInclude' => $fileToInclude,
        'jsToInclude' => $jsToInclude,
		'sessionCourante' => $this->session->getSessionName(),
        'tabSessions' => $this->session->getTabSessions()
        ))
    );
    $response->setPublic();
    $response->setETag(md5($response->getContent()));
    return $response;
}



// Fonction qui recoit la validation de création d'un nouvel etat
public function creationAction() {
	$this->constructeur();
    $this->initialisation();
    $requete = $this->get('request');
    $messageRetour = '';
    $this->titre_page_etat = 'Etat';
    if ($requete->getMethod() == 'POST') {
        // Récupération des informations retournées par le formulaire
        $variablesPost = $_POST;
        $numeroCalcul = $variablesPost['numeroCalcul'];
        $entityCalcul = $this->em->getRepository('IpcProgBundle:Calcul')->findOneByNumeroCalcul($numeroCalcul);
        // Selon le type d'Etat à créer -> Envoi vers la page de création de l'Etat
        switch ($numeroCalcul) {
        	// Analyse de marche chaudière
        	case 1:
				//	Etat : Analyse de marche chaudières
        	    $this->titre_page_etat = 'Etat - Analyse de marche chaudières';
        	    $returnCode = $this->creationEtat1($variablesPost, $entityCalcul);
        	    $this->getModulesEtat();
        	    if ($returnCode != 'Ok') {
            		$messageRetour = "Etat non crée - Erreur : $returnCode";
            		$this->get('session')->getFlashBag()->add('info', $messageRetour);
					$this->test = "NOUVEAU";
            		return $this->redirect($this->generateUrl('ipc_nouvelEtat', array('numero' => $numeroCalcul)));
            	} else {
            		$messageRetour = "Etat Crée";
            	}
            	break;
        }
    }
    $this->get('session')->getFlashBag()->add('info', $messageRetour);
    $fileToInclude = 'IpcEtatBundle:Etat:afficheEtat.html.twig';
    $response = new Response($this->renderView('IpcEtatBundle:Etat:accueil.html.twig',array(
    	'titrePageEtat' => $this->titre_page_etat,
        'tabCalculs' => $this->tab_calculs,
        'tabEtats' => $this->tab_etats,
        'fileToInclude' => $fileToInclude,
		'sessionCourante' => $this->session->getSessionName(),
        'tabSessions' => $this->session->getTabSessions()
    	))
    );
    $response->setPublic();
    $response->setETag(md5($response->getContent()));
    return $response;
}


// Fonction de création d'un Etat de type 1 : Analyse de marche chaudière
protected function creationEtat1($variablesPost, $entityCalcul) {
    $this->initialisation();
    $serviceEtat = $this->container->get('ipc_prog.etats');
    $entityEtat = new Etat();
    if ($variablesPost['titre'] == '') {
     	return("Titre manquant");
    }
    if ($this->em->getRepository('IpcProgBundle:Etat')->findOneByIntitule($variablesPost['titre']) != null) {
        return("Etat '".$variablesPost['titre']."' existant. Veuillez choisir un autre titre svp.");
    } 
	// Définition du titre de l'Etat
    $entityEtat->setIntitule($variablesPost['titre']);
	// Définition de la catègorie de l'Etat : Catégorie 1 pour l'Etat Analyse de marche chaudière
    $entityEtat->setCalcul($entityCalcul);
	// Définition de la période de l'Etat : Au format de fréquence de création - ou de date de début et de fin
    $periode = '';
    switch ($variablesPost['choixPeriode']) {
        case 'unique':
            $dateDeb = $variablesPost['dateDeb'];
            $dateFin = $variablesPost['dateFin'];
            // Vérification des dates
            if ($this->verifEtatDates($dateDeb, $dateFin) == 0) {
                return("Dates incorrectes");
            }
            $periode = "Du;".$this->service_fillNumbers->formaterDate($dateDeb, 'mySql').";au;".$this->service_fillNumbers->formaterDate($dateFin, 'mySql');
            break;
        case 'periodique':
            $periode .= 'frequence_'.$variablesPost['nbOccurencesFrequence'].'_'.$variablesPost['frequence'];
            $periode .= ';periode_'.$variablesPost['nbOccurencesPeriode'].'_'.$variablesPost['periode'];
       		break;
    }
    $entityEtat->setPeriode($periode);
	// Définition des modules Chaudière en marche, Présence flamme 1, [ Présence flamme 2 - pour les chaudières bifoyer ]
   	$foyer = (isset($variablesPost['bifoyer']))?'bifoyer':'monofoyer';
    $mode = $this->em->getRepository('IpcProgBundle:Localisation')->find($variablesPost['idLocalisation1'])->getMode();
    $liste_modules  = $foyer;
    if ($mode != NULL) {
        $mode = $mode->getId();
     }
    // Récupération de la condition sur valeur
    // Vérification du bon formatage des données entrées
    $condValeur1 = $this->verificationCondition($variablesPost['condValeur1']);
    $liste_modules .= ';module1_'.$mode.'_'.$variablesPost['idLocalisation1'].'_'.$variablesPost['idModule1'].'_'.$condValeur1;
    // Récupération de la condition sur valeur
    // Vérification du bon formatage des données entrées
    $condValeur2 = $this->verificationCondition($variablesPost['condValeur2']);
    $liste_modules .= ';module2_'.$mode.'_'.$variablesPost['idLocalisation1'].'_'.$variablesPost['idModule2'].'_'.$condValeur2;
    if ($foyer == 'bifoyer') {
        // Récupération de la condition sur valeur
        // Vérification du bon formatage des données entrées
        $condValeur3 = $this->verificationCondition($variablesPost['condValeur3']);
        $liste_modules .= ';module3_'.$mode.'_'.$variablesPost['idLocalisation1'].'_'.$variablesPost['idModule3'].'_'.$condValeur3;
    }
    $entityEtat->setListeModules($liste_modules);
    $liste_acquittement = '';
    // Définition du Module de réarmement : Acquittement des messages de défauts
    if (isset($variablesPost['defAcquittement'])) {
      	$liste_acquittement .= $variablesPost['iddefAcquittement'];
    }
    $entityEtat->setOption4($liste_acquittement);
    // Définition de la liste des défauts récurrents à exclure des résultats de la recherche : Listes de défauts
    $liste_exlusion1 = '';
    if (isset($variablesPost['defChaudiere1'])) {
       	$liste_exlusion1 .= 'C1'.$variablesPost['iddefChaudiere1'].';';
    }
    if (isset($variablesPost['defChaudiere2'])) {
        $liste_exlusion1 .= 'C2'.$variablesPost['iddefChaudiere2'].';';
    }
    if (isset($variablesPost['defBruleur1'])) {
        $liste_exlusion1 .= 'B1'.$variablesPost['iddefBruleur1'].';';
    }
    if (isset($variablesPost['defBruleur2'])) {
        $liste_exlusion1 .= 'B2'.$variablesPost['iddefBruleur2'].';';
    }
    // Si au moins une exclusion est présente : suppression du caractère ; de fin de liste
    if ($liste_exlusion1 != '') {
       	$liste_exlusion1 = substr($liste_exlusion1,0,-1);
    }	
    $entityEtat->setOptionExclusion1($liste_exlusion1);
    // Définition de la liste des alarmes récurrentes à exclure des résultats de la recherche : Liste des alarmes
    $liste_exlusion2 = '';
    if (isset($variablesPost['defKlaxon'])) {
        $liste_exlusion2 .= $variablesPost['iddefKlaxon'].';';
    }
    if (isset($variablesPost['defGyrophare'])) {
        $liste_exlusion2 .= $variablesPost['iddefGyrophare'].';';
    }
    // Si au moins une exclusion est présente : suppression du caractère ; de fin de liste
    if ($liste_exlusion2 != '') {
        $liste_exlusion2 = substr($liste_exlusion2, 0, -1);
    }
    $entityEtat->setOptionExclusion2($liste_exlusion2);
    // Définition de la liste des désignations des modules = Désignation des modules définis dans le paramètre [ liste_modules ]
    if ($variablesPost['designation1'] == '' || $variablesPost['designation2'] == '') {
    	return('Désignation de module manquante');
    }
    $liste_designations = trim($variablesPost['designation1']).';'.trim($variablesPost['designation2']);
    if ($foyer == 'bifoyer') {
     	if ($variablesPost['designation3'] == '') {
       		return('Désignation de module manquante');
       	}
       	$liste_designations .= ';'.trim($variablesPost['designation3']);
    }
    $entityEtat->setListeDesignations($liste_designations);
    // Lors de la création de l'état il est mis en actif par défaut
    $entityEtat->setActive(true);

    // Définition de la liste des combustibles
    $liste_req = $this->session->get('liste_req_etat_combustibleB1');
    $liste_combustibleB1 = '';
    foreach ($liste_req as $key => $liste) {
        $liste_combustibleB1 .= $liste['idModule'].'_'.$liste['codeVal1'].'_'.$liste['val1min'].'_'.$liste['val1max'].';';
    }
    $liste_combustibleB1 = substr($liste_combustibleB1, 0, -1);
    $entityEtat->setOption5($liste_combustibleB1);

    // Définition de la liste des combustibles du brûleur 2
    $liste_req = $this->session->get('liste_req_etat_combustibleB2');
    $liste_combustibleB2 = '';
    foreach ($liste_req as $key => $liste) {
        $liste_combustibleB2 .= $liste['idModule'].'_'.$liste['codeVal1'].'_'.$liste['val1min'].'_'.$liste['val1max'].';';
    }
    $liste_combustibleB2 = substr($liste_combustibleB2, 0, -1);
    $entityEtat->setOption6($liste_combustibleB2);


    // Définition de la liste des compteurs
    $liste_req = $this->session->get('liste_req_etat_compteur');
    $liste_compteur = '';
    foreach ($liste_req as $key => $liste) {
    	$liste_compteur .= $liste['idModule'].'_'.$liste['codeVal1'].'_'.$liste['val1min'].'_'.$liste['val1max'].';';
    }
    $liste_compteur = substr($liste_compteur, 0, -1);
    $entityEtat->setOption1($liste_compteur);

    // Récupération de la liste des tests
    $liste_req = $this->session->get('liste_req_etat_test');
    $liste_test = '';
    foreach ($liste_req as $key => $liste) {
        $liste_test .= $liste['idModule'].'_'.$liste['codeVal1'].'_'.$liste['val1min'].'_'.$liste['val1max'].';';
    }
    $liste_test = substr($liste_test, 0, -1);
    $entityEtat->setOption2($liste_test);
    // Récupération de la liste des forcages
    $liste_req = $this->session->get('liste_req_etat_forcage');
    $liste_forcage = '';
    foreach ($liste_req as $key => $liste) {
        $liste_forcage .= $liste['idModule'].'_'.$liste['codeVal1'].'_'.$liste['val1min'].'_'.$liste['val1max'].';';
    }
    $liste_forcage = substr($liste_forcage, 0, -1);
    $entityEtat->setOption3($liste_forcage);
    $this->em->persist($entityEtat);
    $this->em->flush();
    //  Modifier par une fonction de création de l'état en base de donnée
    if (strtolower($_POST['choixSubmit']) == 'recherche') {
      	$retourAnalyse = $serviceEtat->EtatAnalyseDeMarche('creation', $entityEtat);
    }
    return('Ok');
}

// fonction qui transforme un texte du durée en enlevant la partie inutile
// ex 0 mois 0 jour(s) 7 heure(s) 14 minute(s) 18 seconde(s) trasnformé en 7 heure(s) 14 minute(s) 18 seconde(s)
private function transformeTexteDuree($texte){
	// Suppression de tous les caractères jusqu'au 1er chiffre != de zéro rencontré
	$tab = preg_split('/^.*?([1-9][0-9]?)\s/', $texte, -1, PREG_SPLIT_DELIM_CAPTURE);
	if (count($tab) != 3) return ($texte);
	$texte_sans_superflu = $tab[1].' '.$tab[2];
	// Supression des parenthèses
	// Pour le tableau en sortie : 1 -> nb mois / 3 -> nb jours / 5 -> nb heures / 7 -> nb minutes / 9 -> nb secondes
	$tab = preg_split('/(\d+)/', $texte_sans_superflu, -1, PREG_SPLIT_DELIM_CAPTURE);
	// Tous les impaires = Nombre // Tous les impaires + 1 : s à suppirmé ou non
	$texte_retour = '';
	$pluriel = false;
	foreach($tab as $key => $portion_tab) {
		if ($key == 0) continue;
		if ($key % 2 == 1){
			$texte_retour = $texte_retour.$portion_tab;
			if ($portion_tab > 1){
				// Il faut ajouter le S => Supprimer les parenthèses autour du s
				$pluriel = true;	
			}
		} else {
			// Soit on supprime les parenthèses pour ajouter le s, soit on supprime les parentheses et leur contenu pour supprimer le s
			if ($pluriel == true) {
				$texte_retour = $texte_retour.preg_replace('/[\(\)]/', '', $portion_tab);	
			} else {
				$texte_retour = $texte_retour.preg_replace('/\(.+?\)/', '', $portion_tab);
			}
			$pluriel = false;
		}
		// Ajout d'un espace
		$texte_retour = $texte_retour.' ';
	}
	return ($texte_retour);
}

// Fonction qui affiche ou non les chiffres aprés la virgule en fonction du nombre
private function formatNumber($nombre){
	//  Si le nombre est < 1 : 5 chiffres après la virgule
	//  Si le nombre est < 10 : 3 chiffres après la virgule
	//	Si le nombre est < 100 : 2 chiffres après la virgule
	//  Si le nombre est < 1000 : 1 chiffre après la virgule
	//  sinon pas de chiffre après la virgule
	$tabNombre = preg_split('/\./', $nombre);
	if (count($tabNombre) != 2) {
		$partieEntiere = $nombre;
        $partieDecimale = '';
	} else {
        $partieEntiere = $tabNombre[0];
        $partieDecimale = $tabNombre[1];
		if ($partieEntiere <= 1){
			if (strlen($partieDecimale) > 5){
				return round($nombre, 5);
				$partieDecimale = substr($partieDecimale, 5);
			}
		} elseif ($partieEntiere <= 10){
    	    if (strlen($partieDecimale) > 3){
				return round($nombre, 3);
    	    }
    	} elseif ($partieEntiere <= 100){
    	    if (strlen($partieDecimale) > 2){
				return round($nombre, 2);
    	    }
    	} elseif ($partieEntiere <= 1000){
    	    if (strlen($partieDecimale) > 1){
				return round($nombre, 1);
    	    }
		} else {
			return intval($nombre);
		}
	}
	return $nombre;
}

// Formate la date passée en paramètre
private function formatDate($la_date){
	// Cas 1 : La date entrée est au paramètre 2017/01/30 00:00:00 -> La sortie doit être 30/01/2017		
	$tab_date = explode('/', substr($la_date, 0, 10));
	return ($tab_date[2].'/'.$tab_date[1].'/'.$tab_date[0]);
}


// Fonction qui créée l'état Analyse de marche chaudière avec les informations contenues dans le fichier excel 
public function creationEtat1FromCsv($url_fichier) {
	$code_affaire;
	$numero_localisation;
	$titre;
	$frequence;
	$m_exploitation;
	$m_flamme1;
	$m_flamme2;
	$d_chaudiere;
	$d_flamme1;
	$d_flamme2;
	$m_rearmement;
	$liste_m_compteur;
	$liste_m_test;
	$liste_m_forcage;
	$liste_m_combustible1;
	$liste_m_combustible2;
	$m_defaut_chaudiere1;
	$m_defaut_chaudiere2;
	$m_defaut_bruleur1;
	$m_defaut_bruleur2;
	$m_klaxon;
	$m_gyrophare;
	
	$entity_site;
	$entity_localisation;
	$entity_mode;

	$liste_id_compteur = '';
	$liste_id_test = '';
	$liste_id_forcage = '';
	$liste_id_combustible1 = '';
	$liste_id_combustible2 = '';
	$liste_modules;	
	$liste_designations;

	// Ouverture et récupération des informations contenues dans le fichiers excel
	if (($handle = fopen($url_fichier, 'r')) != false) {
		$num_ligne_courante = 0;
		while (($data = fgetcsv($handle, 1000, ';')) != false) {
			$num_ligne_courante ++;
			// La première ligne indique les informations qui sont demandées
			// On vérifie que l'ordre des champs est correct
			if ($num_ligne_courante == 1){
				/*if (trim($data[0]) != 'Affaire'){
					return new Response('erreur affaire');
				}*/
				if (trim($data[1]) != 'Numéro de localisation'){
					return $this->returnMessage("Titre du champs 1 : [ Numéro de localisation ] non trouvé");
				}
				if (trim($data[2]) != "Titre de l'état"){
					return $this->returnMessage("Titre du champs 2 : [ Titre de l'état ] non trouvé");
				}
				if (trim($data[3]) != 'Fréquence'){
					return $this->returnMessage("Titre du champs 3 : [ Fréquence ] non trouvé");
				}
				if (trim($data[4]) != "Module Exploitation - Phase d'exploitation de la chaudière"){
					return $this->returnMessage("Titre du champs 4 : [ Module Exploitation - Phase d'exploitation de la chaudière ] non trouvé");
				}
				if (trim($data[5]) != "Module Came numérique 1 - Présence flamme"){
					return $this->returnMessage("Titre du champs 5 : [ Module Came numérique 1 - Présence flamme ] non trouvé");
				}
                if (trim($data[6]) != "Module Came numérique 2 - Présence flamme"){
					return $this->returnMessage("Titre du champs 6 : [ Module Came numérique 2 - Présence flamme ] non trouvé");
                }
				if (trim($data[7]) != "Désignation Chaudière en marche"){
					return $this->returnMessage("Titre du champs 7 : [ Désignation Chaudière en marche ] non trouvé");
				}
				if (trim($data[8]) != "Désignation Présence flamme"){
					return $this->returnMessage("Titre du champs 8 : [ Désignation Présence flamme ] non trouvé");
                }
                if (trim($data[9]) != "Désignation Présence flamme2"){
					return $this->returnMessage("Titre du champs 9 : [ Désignation Présence flamme2 ] non trouvé");
                }
                if (! preg_match('/^Module de réarmement/', trim($data[10]))){
					return $this->returnMessage("Titre du champs 10 : [ Module de réarmement ] non trouvé");
                }
				if (! preg_match('/Compteur$/', trim($data[11]))){
					return $this->returnMessage("Titre du champs 11 : [ Compteur ] non trouvé");
                }
                if (! preg_match('/Test$/', trim($data[12]))){
					return $this->returnMessage("Titre du champs 12 : [ Test ] non trouvé");
                }
                if (! preg_match('/Forçage$/', trim($data[13]))){
					return $this->returnMessage("Titre du champs 13 : [ Forçage ] non trouvé");
                }
                if (! preg_match('/Combustible du brûleur 1$/', trim($data[14]))){
					return $this->returnMessage("Titre du champs 14 : [ Combustible du brûleur 1 ] non trouvé");
                }
                if (! preg_match('/Combustible du brûleur 2$/', trim($data[15]))){
					return $this->returnMessage("Titre du champs 15 : [ Combustible du brûleur 2 ] non trouvé");
                }
                if (! preg_match('/Sécurité - défaut chaudière 1$/', trim($data[16]))){
					return $this->returnMessage("Titre du champs 16 : Sécurité - défaut chaudière 1 ] non trouvé");
                }
                if (! preg_match('/Sécurité - défaut chaudière 2$/', trim($data[17]))){
					return $this->returnMessage("Titre du champs 17 : Sécurité - défaut chaudière 2 ] non trouvé");
                }
                if (! preg_match('/Sécurité - défaut brûleur 1$/', trim($data[18]))){
					return $this->returnMessage("Titre du champs 18 : Sécurité - défaut brûleur 1 ] non trouvé");
                }
                if (! preg_match('/Sécurité - défaut brûleur 2$/', trim($data[19]))){
					return $this->returnMessage("Titre du champs 19 : Sécurité - défaut brûleur 2 ] non trouvé");
                }
				if (! preg_match('/klaxon/', trim($data[20]))){
					return $this->returnMessage("Titre du champs 20 : klaxon ] non trouvé");
				}
               	if (! preg_match('/gyrophare/', trim($data[21]))){
					return $this->returnMessage("Titre du champs 21 : gyrophare ] non trouvé");
				}
			} else if ($num_ligne_courante == 2) {
				$code_affaire = $data[0];
				$numero_localisation = $data[1];
				$titre = $data[2];
				$frequence = $data[3];
				$m_exploitation = $data[4];
				$m_flamme1 = $data[5];	
				$m_flamme2 = $data[6];
				$d_chaudiere = $data[7];
				$d_flamme1 = $data[8];
				$d_flamme2 = $data[9];
				$m_rearmement = $data[10];
				$liste_m_compteur[] = $data[11];
				$liste_m_test[] = $data[12];
				$liste_m_forcage[] = $data[13];
				$liste_m_combustible1[] = $data[14];
				$liste_m_combustible2[] = $data[15];
				$m_defaut_chaudiere1 = $data[16];
				$m_defaut_chaudiere2 = $data[17];
				$m_defaut_bruleur1 = $data[18];
				$m_defaut_bruleur2 = $data[19];
				$m_klaxon = $data[20];
				$m_gyrophare = $data[21];		
				// Récupération du mode de fonctionnement de la localisation
				$entity_site = $this->em->getRepository('IpcProgBundle:Site')->findOneByAffaire($code_affaire);
				$entity_localisation = $this->em->getRepository('IpcProgBundle:Localisation')->findOneBy(array('numeroLocalisation' => $numero_localisation, 'site' => $entity_site));
				if (! $entity_localisation) {
					fclose($handle);
					return $this->returnMessage("La localisation n'existe pas pour le site spécifié", 'etat');
				}
				$entity_mode = $entity_localisation->getMode();

			} else {	
				($data[11] != '') ? $liste_m_compteur[] = $data[11] : '';
				($data[12] != '') ? $liste_m_test[] = $data[12] : '';
				($data[13] != '') ? $liste_m_forcage[] = $data[13] : '';
				($data[14] != '') ? $liste_m_combustible1[] = $data[14] : '';
				($data[15] != '') ? $liste_m_combustible2[] = $data[15] : '';
			}
		}
		fclose($handle);
	}
	if ($this->em->getRepository('IpcProgBundle:Etat')->findOneByIntitule($titre) != null) {
		return $this->returnMessage("Un état de même intitulé existe déjà. Aucun enregistrement ajouté.");
	}
	$id_m_exploitation = $this->getIdModule($m_exploitation, $entity_mode);
	$id_flamme1 = $this->getIdModule($m_flamme1, $entity_mode);
    $id_flamme2 = $this->getIdModule($m_flamme2, $entity_mode);
    $id_rearmement = $this->getIdModule($m_rearmement, $entity_mode);
    $id_defaut_chaudiere1 = $this->getIdModule($m_defaut_chaudiere1, $entity_mode);
    $id_defaut_chaudiere2 = $this->getIdModule($m_defaut_chaudiere2, $entity_mode);
    $id_defaut_bruleur1 = $this->getIdModule($m_defaut_bruleur1, $entity_mode);
    $id_defaut_bruleur2 = $this->getIdModule($m_defaut_bruleur2, $entity_mode);
    $id_klaxon = $this->getIdModule($m_klaxon, $entity_mode);
    $id_gyrophare = $this->getIdModule($m_gyrophare, $entity_mode);
	foreach($liste_m_compteur as $m_compteur){
		$tmp_id = $this->getIdModule($m_compteur, $entity_mode);
		if ($tmp_id != null) {
			$liste_id_compteur .= $tmp_id.'___;';	
		}
	}
	$liste_id_compteur = substr($liste_id_compteur, 0, -1);

    foreach($liste_m_test as $m_test){
		$tmp_id = $this->getIdModule($m_test, $entity_mode);
		if ($tmp_id != null) {
        	$liste_id_test .= $tmp_id.'___;';
		}	
    }
    $liste_id_test = substr($liste_id_test, 0, -1);

    foreach($liste_m_forcage as $m_forcage){
		$tmp_id = $this->getIdModule($m_forcage, $entity_mode);
		if ($tmp_id != null) {
        	$liste_id_forcage .= $tmp_id.'___;';
		}
    }
    $liste_id_forcage = substr($liste_id_forcage, 0, -1);

	if ($liste_m_combustible1[0] != '') {
    	foreach($liste_m_combustible1 as $m_combustible1){
			$tmp_id = $this->getIdModule($m_combustible1, $entity_mode);
			if ($tmp_id != null) {
    	    	$liste_id_combustible1 .= $tmp_id.'___;';
			}
    	}
    	$liste_id_combustible1 = substr($liste_id_combustible1, 0, -1);
	}

	if ($liste_m_combustible2[0] != '') {
    	foreach($liste_m_combustible2 as $m_combustible2){
			$tmp_id = $this->getIdModule($m_combustible2, $entity_mode);
			if ($tmp_id != null) {
    	    	$liste_id_combustible2 .= $tmp_id.'___;';
			}
   		} 
    	$liste_id_combustible2 = substr($liste_id_combustible2, 0, -1);
	}


    $liste_modules = ($id_flamme2 != null) ? 'bifoyer;' : 'monofoyer;';
    $liste_modules .= 'module1_'.$entity_mode->getId().'_'.$entity_localisation->getId().'_'.$this->em->getRepository('IpcProgBundle:Module')->find($id_m_exploitation)->getId().'_>=3';
    $liste_modules .= ';module2_'.$entity_mode->getId().'_'.$entity_localisation->getId().'_'.$this->em->getRepository('IpcProgBundle:Module')->find($id_flamme1)->getId().'_=1';
    $liste_designations = $d_chaudiere.';'.$d_flamme1;
    if ($id_flamme2 != null) {
        $liste_modules .= ';module3_'.$entity_mode->getId().'_'.$entity_localisation->getId().'_'.$this->em->getRepository('IpcProgBundle:Module')->find($id_flamme2)->getId().'_=1';
        $liste_designations .= ';'.$d_flamme2;
    }

	$entity_etat = new Etat();
	$entity_etat->setIntitule($titre);
	$entity_etat->setPeriode($frequence);
	$entity_etat->setCalcul($this->em->getRepository('IpcProgBundle:Calcul')->find(1));
	$entity_etat->setListeModules($liste_modules);
	$entity_etat->setListeDesignations($liste_designations);	
	$entity_etat->setActive(true);
	$entity_etat->setOption1($liste_id_compteur);
	$entity_etat->setOption2($liste_id_test);
	$entity_etat->setOption3($liste_id_forcage);
	$entity_etat->setOption4($id_rearmement.'___');
	$entity_etat->setOption5($liste_id_combustible1);
	$entity_etat->setOption6($liste_id_combustible2);
	$option_exclusion1 = 'C1'.$id_defaut_chaudiere1;
	$option_exclusion1 .= ($id_defaut_chaudiere2 != null) ? ';C2'.$id_defaut_chaudiere2 : '';
	$option_exclusion1 .= ($id_defaut_bruleur1 != null) ? ';B1'.$id_defaut_bruleur1 : '';
    $option_exclusion1 .= ($id_defaut_bruleur2 != null) ? ';B2'.$id_defaut_bruleur2 : '';
	$option_exclusion2 = $id_klaxon.';'.$id_gyrophare;
	$entity_etat->setOptionExclusion1($option_exclusion1);
	$entity_etat->setOptionExclusion2($option_exclusion2);
	$this->em->persist($entity_etat);
	$this->em->flush();
	return $this->returnMessage('Nouvel état crée', 'configuration');
}

private function getIdModule($trigramme_module, $entity_mode){
	$tab_trigramme = str_split($trigramme_module, 2);
	$entity_module = $this->em->getRepository('IpcProgBundle:Module')->findOneBy(array('categorie' => $tab_trigramme[0], 'numeroModule' => $tab_trigramme[1], 'numeroMessage' => $tab_trigramme[2], 'mode' => $entity_mode));	
	return ($entity_module != null) ? $entity_module->getId() : null;
}

// Fonction qui retourne la page de création d'un état par importation d'un fichier au format csv
/**
 *
 * @Security("is_granted('ROLE_ADMIN_LTS')")
*/
public function creationAutoAction(){
    // Récupération de la liste de calcul possible ( = Listes des différents états existants )
    $this->constructeur();
    $this->initialisation();
    $requete = $this->get('request');
	$fichier_calcul = new EtatAuto();
	$form = $this->createForm(new EtatAutoType, $fichier_calcul);
    if ($requete->getMethod() == 'POST') {
		if ($form->handleRequest($requete)->isValid()) {
            if (strtolower(gettype($fichier_calcul->getFile())) == 'null') {
                return $this->returnMessage('Aucun fichier sélectionné.', 'etat');
            }

			// Analyse du format du fichier uploadé
			$service_fichier = $this->get('ipc_prog.fichiers');
			if (! $service_fichier->detectUtf8($fichier_calcul->getFile())) {
				return $this->returnMessage($service_fichier->getMessage(), 'etat');
			}
			$url_fichier = $fichier_calcul->deplacement();
			switch ($fichier_calcul->getCalcul()->getNumeroCalcul()) {
				case '1':
					return ($this->creationEtat1FromCsv($url_fichier));
					break;
			}
			return $this->returnMessage('Valid', 'configuration');
		}
		return $this->returnMessage('Formulaire soumis', 'configuration');
	}
   	return $this->render('IpcEtatBundle:Etat:accueilCreateAuto.html.twig', array(
		'form' => $form->createView()
	)); 
}


private function returnMessage($message, $page='etat') {
    $this->get('session')->getFlashBag()->add('info', $message);
	switch ($page) {
		case 'etat':
			return $this->redirect($this->generateUrl('ipc_createEtatAuto'));	
			break;
		case 'configuration':
			return $this->redirect($this->generateUrl('ipc_conf'));
			break;
	}
}


// Retourne le nieme argument du message passé en parametre. Ex -> TT0106 : gdfgkdfglkdfjg -> retourne TT0106 si le premier argument est demandé
// Si vous définissez le paramètre limit à -1, 0, ou NULL, cela signifie "aucune limite" 
//	sinon seules les limit premières sous-chaînes sont retournées avec le reste de la chaîne placé dans la dernière sous-chaîne
private function getCodeFromTexte($texte, $numero_argument, $limit){
	$tableau_texte = preg_split('/[\s]/',$texte, $limit);
	return $tableau_texte[$numero_argument];
}

}
