<?php
//src/Ipc/ConfigurationBundle/Controller/AnonymAjaxController.php

namespace Ipc\ConfigurationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormBuilder;
use Ipc\ProgBundle\Entity\Site;
use Ipc\ProgBundle\Entity\Localisation;
use Ipc\ProgBundle\Entity\Genre;
use Ipc\ProgBundle\Entity\Module;

class AnonymAjaxController extends Controller {
private $liste_localisations;
private $last_loc_graph_id;
private $pageTitle;
private $session;
private $connexion;
private $dbh;
private $em;
private $tabModulesL;
private $isInitialize;

public function constructeur(){
	$this->em = $this->get('ipc_prog.connectbd')->getManager();
    if (empty($this->session)) {
        $service_session = $this->container->get('ipc_prog.session');
        $this->session = $service_session;
    }
}

public function initialisationListe() {
	$this->constructeur();
	$this->connexion = $this->get('ipc_prog.connectbd');
	$this->dbh = $this->connexion->getDbh();
	$this->isInitialize = $this->session->get('isInitialize');
	$this->pageTitle = $this->session->get('pageTitle');
	$this->tabModulesL = array();

	$this->container->get('ipc_prog.session.boilerbox')->definirListeLocalisationsCourantes();

	$this->liste_localisations = $this->session->get('tablocalisations');
	if ($this->liste_localisations == null) {
		$this->get('session')->getFlashBag()->add('info', "Aucune Localisation définie pour le site courant (a2)");
		return false;
	}
	// Récupération de la dernière localisation entrée pour la réafficher par défaut dans la popup
	$this->last_loc_graph_id = $this->session->get('last_loc_graph_id');
	// Si il n'y a pas eu de requête enregistrée, la localisation par défaut est la première de la liste
	if (empty($this->last_loc_graph_id)) {
		$this->last_loc_graph_id = $this->liste_localisations[0]['id'];
	}
	return(0);
}


private function deconnexionDbh() {
	$this->dbh = $this->connexion->disconnect();
}

//Enregistre une nouvelle variable de session
public function setNewSessionVarsAction() {
    $variable = $_POST['variable'];
    $valeur = $_POST['valeur'];
    $this->constructeur();
    $this->initialisationListe();
    $dbh = $this->dbh;
    $em = $this->em;
    $this->session->set($variable, $valeur);
    return new Response();
}
         

public function setSessionVarsAction() {
	$this->constructeur();
	$this->initialisationListe();
	if (! empty($this->isInitialize)) {
        return new Response();
    }
    $dbh = $this->dbh;
    $liste_messages_modules = array();
    $liste_noms_modules = array();
    $correspondance_message_code = array();
    $tab_conversion_loc_id = array();
    $tab_conversion_loc_num = array();
    $tab_conversion_loc_getnum = array();
    $tab_conversion_genre_id = array();
    $tab_conversion_genre_num = array();
    $tab_conversion_message_id = array();
    $fillnumbers = $this->get('ipc_prog.fillnumbers');
	$last_loc_id = null;
	$last_loc_graph_id = null;

    // Initialisation des listes de localisation : Récupération des localisations associées au site courant
	$this->container->get('ipc_prog.session.boilerbox')->definirListeLocalisationsCourantes();

	$this->liste_localisations = $this->session->get('tablocalisations');
    if ($this->liste_localisations == null) {
        $this->get('session')->getFlashBag()->add('info', "Aucune Localisation définie pour le site courant (a1)");
		return false;
        //return new Reponse(false);
    }
    // Initialisation d'un tableau de conversion des localisations permettant d'afficher la désignation d'une localisation selon son id
    foreach ($this->liste_localisations as $key => $localisation) {
        $tab_conversion_loc_id[$localisation['id']] = $localisation['designation'];
        $tab_conversion_loc_num[$localisation['numero_localisation']] = $localisation['designation'];
        $tab_conversion_loc_getnum[$localisation['id']] = $localisation['numero_localisation'];
    }
    // Récupération de la dernière localisation entrée pour la réafficher par défaut dans la popup
    $last_loc_id = $this->session->get('last_loc_id');
    // Si il n'y a pas eu de requête enregistrée, la localisation par défaut est la première de la liste
    if (empty($last_loc_id)) {
        $last_loc_id = $this->liste_localisations[0]['id'];
    }

    // Récupération de la dernière localisation entrée pour la réafficher par défaut dans la popup
    $last_loc_graph_id = $this->session->get('last_loc_graph_id');
    // Si il n'y a pas eu de requête enregistrée, la localisation par défaut est la première de la liste
    if (empty($last_loc_graph_id)) {
        $last_loc_graph_id = $this->liste_localisations[0]['id'];
    }


    // Initialisation de la liste des genres autorisés
	$this->container->get('ipc_prog.session.boilerbox')->definirListeDesGenres();

	// Initialisation de la liste des modules
	$this->container->get('ipc_prog.session.boilerbox')->definirTabModuleL();

	$this->tabModulesL = $this->session->get('tabModules');
	$correspondance_message_code = $this->session->get('correspondance_Message_Code');
    if ($this->tabModulesL == null) {
        $this->get('session')->getFlashBag()->add('info', "Ajax : Aucun module n'est associé aux localisations du site courant : Veuillez importer la/les table(s) d'échanges");
		return new Response();
    }
    $liste_messages_modules = $this->session->get('liste_messages_modules_listing');
    $liste_noms_modules = $this->session->get('liste_noms_modules_listing');
    $tab_conversion_message_id = $this->session->get('tab_conversion_message_id_listing');
    if ((empty($liste_messages_modules)) || empty($liste_noms_modules) || empty($tab_conversion_message_id)) {
        if (count($this->liste_localisations) > 1) {
            // Récupération initiale des informations concernant la localisation 2
            $localisation_id = $last_loc_id;
            foreach ($this->tabModulesL as $key => $module) {
            	if (in_array($localisation_id, $module['localisation'])) {
            	    // Création d'un tableau pour éviter des présenter des doublons dans les intitulé des modules
            	    if (! in_array($module['intitule'], $liste_noms_modules)) {
            	        array_push($liste_noms_modules, $module['intitule']);
            	    }
            	    $liste_messages_modules[$key] = $correspondance_message_code[$key]." - ".$this->suppressionDesCaracteres($module['message']);
            	    $tab_conversion_message_id[$key] = $module['message'];
            	}
        	}
        } else {
            // Si il y a plusieurs localisations : Récupération initiale des informations concernant la localisation 1
            // Création des tableaux des intitulés de module
            // et des messages de modules
            foreach ($this->tabModulesL as $key => $module) {
                // Création d'un tableau pour éviter des présenter des doublons dans les intitulé des modules
                if (! in_array($module['intitule'], $liste_noms_modules)) {
                    array_push($liste_noms_modules, $module['intitule']);
                }
                $liste_messages_modules[$key] = $correspondance_message_code[$key]." - ".$this->suppressionDesCaracteres($module['message']);
                $tab_conversion_message_id[$key] = $module['message'];
            }
        }
        asort($liste_noms_modules);
        asort($liste_messages_modules);
        $liste_noms_modules = $liste_noms_modules;
        $liste_messages_modules  = $liste_messages_modules;
        // Ajout d'une variable de session afin de permettre une recherche des messages par recherche direct
        $this->session->set('liste_messages_modules_listing', $liste_messages_modules);
		$this->session->set('liste_messages_modules_graphique', $liste_messages_modules);
        // Ajout d'une variable de session qui stock la liste des modules afin d'éviter des faire la boucle de parcours de modules une fois la variable crée
        $this->session->set('liste_noms_modules_listing', $liste_noms_modules);
		$this->session->set('liste_noms_modules_graphique', $liste_noms_modules);
        // Ajout de la variable de session qui stock les correspondances idModule => message
        $this->session->set('tab_conversion_message_id_listing', $tab_conversion_message_id);
		$this->session->set('tab_conversion_message_id_graphique', $tab_conversion_message_id);
    }/* else {
        $liste_noms_modules = $liste_noms_modules;
        $liste_messages_modules = $liste_messages_modules;
        $tab_conversion_message_id = $tab_conversion_message_id;
    }*/
	$this->session->set('isInitialize', true);
	return new Response();
}

// Fonction qui supprime les caractères spéciaux dans les messages des modules
public function suppressionDesCaracteres($chaine) {
    $this->constructeur();
    // Suppression des $ et =$ des messages
    $pattern = '/=?\s?\$/';
    $replacement = '';
    $chaine = preg_replace($pattern, $replacement, $chaine);
    return($chaine);
}

private function getIdSiteCourant($dbh) {
    $site = new Site();
    $idSiteCourant = $site->SqlGetIdCourant($dbh);
    return ($idSiteCourant);
}

}
