<?php 
//src/Ipc/ProgBundle/Services/Session/ServiceSessionBoilerBox.php
namespace Ipc\ProgBundle\Services\Session;
use Ipc\ProgBundle\Entity\Site;

use Ipc\ProgBundle\Entity\Genre;
use Ipc\ProgBundle\Entity\Module;
use Ipc\ProgBundle\Entity\Localisation;


class ServiceSessionBoilerBox {
protected $dbh;
protected $srv_session;
protected $em;
protected $securityContext;
protected $srv_fillnumbers;
protected $srv_traduction;

protected $liste_genre_en_base;
protected $liste_genre;


public function __construct($srv_connexion, $securityContext, $srv_session, $srv_fillnumbers, $srv_traduction) {
	$this->dbh = $srv_connexion->getDbh();
	$this->srv_session = $srv_session;
	$this->securityContext = $securityContext;
	$this->srv_fillnumbers = $srv_fillnumbers;
	$this->srv_traduction = $srv_traduction;
	$this->em = $srv_connexion->getManager();
}


public function definirTabModuleL() {
    // Initialisation de la liste des modules
    //      Pour un compte Client, Si tous les genres ne sont pas autorisés : Récupération des modules dont le genre est autorisé
    //      Recupération de la liste des modules sous la forme :        IdModule => 'intitule'  -> intitulé de la famille de module
    //                                                                           => 'message'   -> message du module
    //                                                                           => 'genre'     -> id du genre du module
    //      Parcours de la liste des modules : pour chaque localisation on vérifie qu'un lien module/localisation existe :
    //                                          Pour prendre en compte le module
    //                                          Pour incrémenter la variable $tabModulesL[$module['id']]['tabLocalisations'][]
	$this->definirListeDesGenres();
	$correspondance_message_code = array();
	$tabModulesL = array();
	$param_popup_simplifiee = $this->em->getRepository('IpcProgBundle:Configuration')->getValueOf('popup_simplifiee');
    if (count($this->srv_session->get('tabModules')) == 0) {
        $tmp_module = new Module();
        // Récupération de tous les modules de la base de donnée
        $tmp_liste_modules = $tmp_module->SqlGetModulesGenreAndUnit($this->dbh);
        foreach ($tmp_liste_modules as $module) {
                // Pour les techniciens et les administrateurs : Récupération de tous les modules ayant un lien avec les localisations du site courant
                //   Ajout 1.29.0 : En fonction d'un parametre de configuration : Recherche des modules liés à la localisation ET qui ont au moins une donnée enregistrée en base
                foreach ($this->liste_localisations as $localisation) {
                    $tmpNbLien = $tmp_module->sqlGetNbLien($this->dbh, $module['id'], $localisation['id']);
                    if ($tmpNbLien != 0) {
                        if (! array_key_exists($module['id'], $tabModulesL)) {
							if ($this->ajoutModuleAuTableau($module['hasDonnees'], $param_popup_simplifiee) === true) {
                                $correspondance_message_code[$module['id']] = $module['categorie'].$this->srv_fillnumbers->fillNumber($module['numeroModule'], 2).$this->srv_fillnumbers->fillNumber($module['numeroMessage'], 2);
                                $tabModulesL[$module['id']]['intitule'] = $this->srv_traduction->getTraduction($module['intituleModule']);
                                $tabModulesL[$module['id']]['message'] = $this->srv_traduction->getTraduction($module['message']);
                                $tabModulesL[$module['id']]['genre'] = $module['idGenre'];
                                $tabModulesL[$module['id']]['unite'] = $module['unite'];
                                $tabModulesL[$module['id']]['localisation'][] = $localisation['id'];
                            }
                        } else {
                            $tabModulesL[$module['id']]['localisation'][] = $localisation['id'];
                        }
                    }
                }
        }
    } else {
        $tabModulesL = $this->srv_session->get('tabModules');
        //      Tableau de clé IDModule et de valeurs : CATEGORIE.NumMODULE.NumMESSAGE
        $correspondance_message_code = $this->srv_session->get('correspondance_Message_Code');
    }
	$this->srv_session->set('tabModules', $tabModulesL);
	$this->srv_session->set('correspondance_Message_Code', $correspondance_message_code);
	return(0);
}


// Initialisation de la liste des genres
//      Récupération de la liste des genres présents en base de données
//      Retour sous forme d'un tableau de tableau       array(id => X,intitule_genre => X)
//      Création de la variable de session 'tabgenres' si elle n'existe pas
//
//      Récupération de la liste des genres autorisés au client (Par défaut la liste correspond à l'ensemble des genres présent en base)
//      Parmis tous les genres présents en base de donnée : Suppression des genres dont l'id ne fait pas partie de la liste des id des genres autorisés
//      Le compte client est défini par l'absence d'autorisation Technicien
//      Création de la variable de session 'session_genrel_autorise' si elle n'existe pas
public function definirListeDesGenres() {
	if (count($this->srv_session->get('tabgenres')) == 0) {
		$tmp_genre = new Genre();
        $this->liste_genres_en_base = $tmp_genre->SqlGetAllGenre($this->dbh);
        $this->srv_session->set('tabgenres', $this->liste_genres_en_base);
	} else {
        $this->liste_genres_en_base = $this->srv_session->get('tabgenres');
    }

	if (count($this->srv_session->get('session_genrel_autorise')) == 0) {
        $liste_genres = $this->liste_genres_en_base;
		if (! $this->securityContext->isGranted('ROLE_TECHNICIEN')) {
            // Compte CLIENT : Genres autorisés pour la partie listing
            $configuration = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('autorisation_genres_listing');
            if (! $configuration) {
                $this->srv_session->get('session')->getFlashBag()->add('info',"Paramètre de configuration [autorisation_genres_listing] non trouvé");
                return(false);
            }
            $tmp_valeur_conf = $configuration->getValeur();
            // Création du tableau des genres autorisés
            $tmp_tab_genres_autorises = explode(',', $tmp_valeur_conf);
            // Suppression des genres ne faisant pas partie des genres autorisés du tableau des genres de la base
            foreach ($liste_genres as $key => $genre) {
                if (! in_array($genre['id'], $tmp_tab_genres_autorises)) {
                    unset($liste_genres[$key]);
                }
            }
        }
        $this->srv_session->set('session_genrel_autorise', $liste_genres);
		$this->liste_genres = $liste_genres;
    }

    if (count($this->srv_session->get('session_genreg_autorise')) == 0) {
        $liste_genres = $this->liste_genres_en_base;
        if (! $this->securityContext->isGranted('ROLE_TECHNICIEN')) {
            // Compte CLIENT : Genres autorisés pour la partie listing
            $configuration = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('autorisation_genres_graphique');
            if (! $configuration) {
                $this->srv_session->get('session')->getFlashBag()->add('info',"Paramètre de configuration [autorisation_genres_graphique] non trouvé");
                return(false);
            }
            $tmp_valeur_conf = $configuration->getValeur();
            // Création du tableau des genres autorisés
            $tmp_tab_genres_autorises = explode(',', $tmp_valeur_conf);
            // Suppression des genres ne faisant pas partie des genres autorisés du tableau des genres de la base
            foreach ($liste_genres as $key => $genre) {
                if (! in_array($genre['id'], $tmp_tab_genres_autorises)) {
                    unset($liste_genres[$key]);
                }
            }
        }
        $this->srv_session->set('session_genreg_autorise', $this->liste_genres);
		$this->liste_genres = $liste_genres;
    }

}



// Initialisation des listes de localisation : Récupération des localisations associées au site courant
public function definirListeLocalisationsCourantes() {
    if (count($this->srv_session->get('tablocalisations')) == 0) {
        $tmp_site = new Site();
        $tmp_localisation = new Localisation();
        $site_id = $tmp_site->SqlGetIdCourant($this->dbh);
        $this->liste_localisations = $this->em->getRepository('IpcProgBundle:Localisation')->SqlGetLocalisation($this->dbh, $site_id);
    } else {
        $this->liste_localisations = $this->srv_session->get('tablocalisations');
    }
	$this->srv_session->set('tablocalisations', $this->liste_localisations);
	return(0);
}

private function ajoutModuleAuTableau($hasDonnee, $popupSimplifiee) {
	// Si la popup ne doit pas être simplifiée, enregistrement du module dans le tableau
	if ($popupSimplifiee == false) {
		return true;
	}
	// Si la popup doit être simplifié et que le module à au moins une donnée rnregistrée en base : enregistrement du module
	if ($hasDonnee == true) {
		return true;
	}
	// Sinon pas d'enregistrement
	return false;
}

public function reinitialisationSession($type) {
	$bag_session_courante = $this->srv_session->getSessionCourante();
    if ($type == 'localisations_modules') {
        // Réinitialisation des variables de session
		$bag_session_courante->remove('tablocalisations');
		$bag_session_courante->remove('tabModules');
		$bag_session_courante->remove('correspondance_Message_Code');
    }
    if ($type == 'liste_des_requetes') {
		$bag_session_courante->remove('tabModules');
		$bag_session_courante->remove('liste_req');
		$bag_session_courante->remove('liste_req_pour_listing');
		$bag_session_courante->remove('liste_req_pour_graphique');
    }
	if ($type == 'session_complete') {
        $bag_session_courante->remove('liste_req');
        $bag_session_courante->remove('liste_req_pour_listing');
        $bag_session_courante->remove('liste_req_pour_graphique');
		$bag_session_courante->remove('tablocalisations');
        $bag_session_courante->remove('tabModules');
        $bag_session_courante->remove('correspondance_Message_Code');
	}
	$this->srv_session->saveSession($bag_session_courante);
	return 0;
}


}

