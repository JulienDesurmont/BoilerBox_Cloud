<?php
//src/Ipc/AnalyseBundle/Controller/AjaxController.php

namespace Ipc\AnalyseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormBuilder;
use Ipc\ProgBundle\Entity\Donnee;
use Ipc\ProgBundle\Entity\Site;
use Ipc\ProgBundle\Entity\Localisation;

class AjaxController extends Controller {

private $session;
private $connexion;
private $dbh;
private $em;

public function constructeur(){
    if (empty($this->session)) {
        $service_session = $this->container->get('ipc_prog.session');
        $this->session = $service_session;
    }
	$this->em = $this->get('ipc_prog.connectbd')->getManager();
}

//	Récupération du tableau des défauts bruleurs
//	Recherche des défauts de la localisation selectionnée
//	Attend en entrée GET : L'identifiant de la localisation 
public function getDefautsBruleursAction() {
	$this->constructeur();
	$fillnumbers = $this->get('ipc_prog.fillnumbers');
    $this->connexion = $this->get('ipc_prog.connectbd');
    $this->dbh = $this->connexion->getDbh();

	$id_localisation = $_GET['idLocalisation'];
	//	Récupération de l'entité de la localisation et de l'identifiant du mode de cette localisation
	$entityLocalisation = $this->em->getRepository('IpcProgBundle:Localisation')->find($id_localisation);
	$entityMode = $entityLocalisation->getMode();
	$session_date = $this->session->get('session_date');
	if (empty($session_date)) {
		echo json_encode("Veuillez indiquer la période d'analyse");
		return new Response();
	}
	$tmp_date_deb = $this->getDatePeriode($session_date['datedebut'], $id_localisation, 'debut');
	$tmp_date_fin = $this->getDatePeriode($session_date['datefin'], $id_localisation, 'fin');
	$tableau_defauts_bruleurs = $this->session->get('tabdefautsbruleurs');
	// Initialisation du tableau de données de type défauts bruleurs
	$tableau_des_modules_defaut_bruleur = array();
	$tableau_des_modules_defaut_bruleur[$id_localisation] = array();
	$pattern_defaut_bruleur = '/^bruleur(.+?)$/';
	foreach ($tableau_defauts_bruleurs[$id_localisation.'_'.$entityLocalisation->getNumeroLocalisation()] as $parameter => $codeMessage) {
		//	Recherche des parametres des modules défauts bruleurs
		if (preg_match($pattern_defaut_bruleur, $parameter, $tab_defauts_bruleur)) {
			$numBruleur	= $tab_defauts_bruleur[1];
			//Recherche du module ayant le code défini et appartenant au mode de la localisation
			//Découpage de la catégorie, du module et du message du code
			$categorie = substr($codeMessage, 0, 2);
			$num_module = substr($codeMessage, 2, 2);
			$num_message = substr($codeMessage, 4, 2);
			$entityModule =  $this->em->getRepository('IpcProgBundle:Module')->findOneBy(array('mode' => $entityMode, 'categorie' => $categorie, 'numeroModule' => $num_module, 'numeroMessage' => $num_message));
			//	si le module n'existe pas : Aucune recherche n'est effectuée et aucun message n'est retourné	
			if (! empty($entityModule)) {
				//	Requete pour rechercher les défauts sur la période définie
				$tmp_donnee = new Donnee();
            	// Recherche de toutes les données si < limit
            	$OthersValues = $tmp_donnee->SqlGetForGraphique(
                        $this->dbh,
                        $fillnumbers->reverseDate($tmp_date_deb),
                        $fillnumbers->reverseDate($tmp_date_fin),
                        $id_localisation,
						$entityModule->getId(),
						'Eq',
						1,
						null,
                        null,
                        null,
                        null,
                        'nolimit',
                        0,
						'all',
						null
            	);
				$cle_tableau = $entityLocalisation->getDesignation();
				$tableau_des_modules_defaut_bruleur[$cle_tableau][$parameter] = $OthersValues;
			} else {
				$cle_tableau = $entityLocalisation->getDesignation();
				$tableau_des_modules_defaut_bruleur[$cle_tableau][$parameter] = "Le module $codeMessage n'existe pas pour le mode ".$entityMode->getDesignation().'.';
			}
		}
	}
	echo json_encode($tableau_des_modules_defaut_bruleur);
	return new Response();
}

private function getDatePeriode($dateAnalyse, $localisationId, $type) {
	$this->constructeur();
    $tabPeriode = $this->session->get('infoLimitePeriode');
	//	Ajouté pour recréer la variable de session quand celle ci est manquante.
	if (empty($tabPeriode)) {
    	$connexion = $this->container->get('ipc_prog.connectbd');
    	$dbh = $this->connexion->getDbh();
    	$site = new Site();
    	$idSiteCourant = $site->SqlGetIdCourant($dbh);
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
				$tabPeriode = $tabPeriodeAnalyse;
        	    $this->session->set('infoLimitePeriode', $tabPeriodeAnalyse);
        	}
    	}
	}
    $timestamp_dateAnalyse = strtotime($this->reverseDate($dateAnalyse));
    // En cas de localisation = all : Risque d'erreur dans les données ( cause de module non trouvé car appartenant à un programme différent du programme courante de la localisation
    if ($localisationId == 'all') {
        return($dateAnalyse);
    }
    if ($type == 'debut') {
        $dateDebDeLaLoc = $tabPeriode[intval(preg_replace("/'/", "", $localisationId))]['dateDeb'];
        $timestamp_dateDebDeLaLoc = strtotime($this->reverseDate($dateDebDeLaLoc));
        if (($dateDebDeLaLoc != null) && ($timestamp_dateAnalyse < $timestamp_dateDebDeLaLoc)) {
            return($dateDebDeLaLoc);
        }
    }
    if ($type == 'fin') {
        $dateFinDeLaLoc = $tabPeriode[intval(preg_replace("/'/", "", $localisationId))]['dateFin'];
        $timestamp_dateFinDeLaLoc = strtotime($this->reverseDate($dateFinDeLaLoc));
        if (($dateFinDeLaLoc != null) && ($timestamp_dateAnalyse > $timestamp_dateFinDeLaLoc)) {
            return($dateFinDeLaLoc);
        }
    }
    return($dateAnalyse);
}

//  Fonction qui recoit une date en entrée et inverse l'année et le jour : ex -> (entrée) 2014-05-10 12:23:34 <- (sortie) 10-05-2014 12:23:34
public function reverseDate($horodatage) {
	$this->constructeur();
    $pattern = '/^(\d{2})([-\/]\d{2}[-\/])(\d{4})(.+?)$/';
    if (preg_match($pattern,$horodatage,$tabdate)) {
        $retour_heure = $tabdate[3].$tabdate[2].$tabdate[1].$tabdate[4];
        return($retour_heure);
    }
    return($horodatage);
}


}
