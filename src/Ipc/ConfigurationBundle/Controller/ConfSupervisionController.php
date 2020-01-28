<?php
//src/Ipc/ConfigurationBundle/Controller/ConfSupervisionController.php

namespace Ipc\ConfigurationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Ipc\ProgBundle\Entity\DonneeLive;
use Ipc\ProgBundle\Entity\Site;
use Ipc\ProgBundle\Entity\Localisation;
use Ipc\ConfigurationBundle\Entity\ConfigModbus;
use Ipc\ConfigurationBundle\Form\Type\DonneeLiveType;
use Ipc\ConfigurationBundle\Form\Type\ModifDonneeLiveType;
use Ipc\ProgBundle\Entity\FormulairesLive\FormExploitationGenerateur;
use Ipc\ConfigurationBundle\Form\Type\EnteteLive\LiveExploitationGenerateurType;
use Ipc\ProgBundle\Entity\FormulairesLive\FormBruleur;
use Ipc\ConfigurationBundle\Form\Type\EnteteLive\LiveBruleurType;
use Ipc\ProgBundle\Entity\FormulairesLive\FormBase;
use Ipc\ConfigurationBundle\Form\Type\EnteteLive\LiveBaseType;
use Ipc\ProgBundle\Entity\FormulairesLive\FormCombustible;
use Ipc\ConfigurationBundle\Form\Type\EnteteLive\LiveCombustibleType;
use Ipc\ProgBundle\Entity\FormulairesLive\FormEtatGenerateur;
use Ipc\ConfigurationBundle\Form\Type\EnteteLive\LiveEtatGenerateurType;
use Ipc\ProgBundle\Form\Type\ReadLocalisationType;
use Symfony\Component\Form\FormBuilder;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class ConfSupervisionController extends Controller {
private $liste_localisations;
private $last_loc_graph_id;
private $session;
private $em;

public function constructeur(){
    if (empty($this->session)) {
        $service_session = $this->container->get('ipc_prog.session');
        $this->session = $service_session;
    }
	$this->em = $this->get('ipc_prog.connectbd')->getManager();
}

public function initialisationListe() {
	$this->constructeur();
	$connexion = $this->get('ipc_prog.connectbd');
	$dbh = $connexion->getDbh();
	// Initialisation des listes de localisation
	if (count($this->session->get('tablocalisations')) == 0) {
		$tmp_site = new Site();
		$tmp_localisation = new Localisation();
		$site_id = $tmp_site->SqlGetIdCourant($dbh);
		$this->liste_localisations = $this->em->getRepository('IpcProgBundle:Localisation')->SqlGetLocalisation($dbh, $site_id);
		$this->session->set('tablocalisations', $this->liste_localisations);
	} else {
		$this->liste_localisations = $this->session->get('tablocalisations');
	}
	if ($this->liste_localisations == null) {
		$this->get('session')->getFlashBag()->add('info', "Aucune Localisation définie pour le site courant");
		return false;
	}
	// Récupération de la dernière localisation entrée pour la réafficher par défaut dans la popup
	$this->last_loc_graph_id = $this->session->get('last_loc_graph_id');
	// Si il n'y a pas eu de requête enregistrée, la localisation par défaut est la première de la liste
	if (empty($this->last_loc_graph_id)) {
		$this->last_loc_graph_id = $this->liste_localisations[0]['id'];
	}
	$dbh = $connexion->disconnect();
	return(0);
}

public function addTuileLiveAction($idLocalisation = null) {
	$this->initialisationListe();
	if ($idLocalisation == null) { 
		$idLocalisation = $this->last_loc_graph_id;
	}
	// Récupération du service Requête
	$requete = $this->get('request');
	// Création du formulaire des données Live
	$donneeLive = new DonneeLive();
	$donneeLive->setPlacement('corps');
	$localisation = $this->em->getRepository('IpcProgBundle:Localisation')->find($idLocalisation);
	$donneeLive->setLocalisation($localisation);
	$form = $this->createForm(new DonneeLiveType(), $donneeLive);
	// Si le formulaire est correct : Enregistrement de la donnée en base
	if ($form->handleRequest($requete)->isValid()) {
		// Sauvegarde des informations sur les registres et le numéro du bit
		$donneeLive->setRegistresAndBit();
		$this->em->persist($donneeLive);
		// La localisation indiquée dans le formulaire devient la nouvelle localisation courante
		$localisation = $donneeLive->getLocalisation();
		$this->last_loc_graph_id = $localisation->getId();
		// Récupération de l'entité ConfigLocalisation pour lui passer la nouvelle donnée
		$entityConfigModbus = $this->em->getRepository('IpcConfigurationBundle:ConfigModbus')->findOneByLocalisation($localisation);
		// Si l'entité n'existe pas -> Création de celle ci
		if ($entityConfigModbus == null) {
			$entityConfigModbus = new ConfigModbus($localisation, null);
		}
		$donneeLive->setConfigModbus($entityConfigModbus);
		// Ajout de la localisation à l'entité
		$this->em->persist($entityConfigModbus);
		$this->em->flush();
	}
	$tabDonneesLive = $this->em->getRepository('IpcProgBundle:DonneeLive')->myFindByNotEntete($localisation->getId());
	// Récupération de la liste des données Live de la localisation courante
	return $this->render('IpcConfigurationBundle:Configuration:FormulairesLive/addTuileLive.html.twig', array(
		'form' => $form->createView(),
		'last_loc_graph_id'	=> $this->last_loc_graph_id,
		'localisationCourante' => $localisation,
		'tabDonneesLive' => $tabDonneesLive,
		'sessionCourante' => $this->session->getSessionName(),
        'tabSessions' => $this->session->getTabSessions()
	));
}

// DONNEES LIVE EN TETE
/**
 * Require ROLE_ADMIN for only this controller method.
 *
 * @Security("is_granted('ROLE_ADMIN')")
*/
public function addDonneeLiveAction() {
	$this->initialisationListe();
	// Récupération du service Requête
	$requete = $this->get('request');
	// Création du formulaire des données Live
	$donneeLive = new DonneeLive();
	$form = $this->createForm(new	ReadLocalisationType(), $donneeLive);
	if ($form->handleRequest($requete)->isValid()) {
		// La localisation indiquée dans le formulaire devient la nouvelle localisation courante
		$idLocalisation = $donneeLive->getLocalisation()->getId();
		$enTeteLive = strtolower($_POST['selectEnteteLive']);
		// Appel de la fonction d'affichage du formulaire associé au choix
		switch ($enTeteLive) {
		case 'niveau':
			return $this->addFormBaseAction($idLocalisation, $enTeteLive);
			break;
		case 'pression':
			return $this->addFormBaseAction($idLocalisation, $enTeteLive); 
			break;
		case 'conductivitech':
			return $this->addFormBaseAction($idLocalisation, $enTeteLive);
			break;
		case 'debitvapeur':
			return $this->addFormBaseAction($idLocalisation, $enTeteLive);
			break;
		case 'debitreseau':
			return $this->addFormBaseAction($idLocalisation, $enTeteLive);
			break;
		case 'temperaturedepart':
			return $this->addFormBaseAction($idLocalisation, $enTeteLive);
			break;
		case 'temperatureretour':
			return $this->addFormBaseAction($idLocalisation, $enTeteLive);
			break;
		case 'temperaturebache':
			return $this->addFormBaseAction($idLocalisation, $enTeteLive);
			break;
		case 'tuile':
			return $this->addTuileLiveAction($idLocalisation);
			break;
		default:
			$formulaireEntete = 'addForm'.$enTeteLive."Action";
			return $this->$formulaireEntete($idLocalisation);
			break;
		}
	} else {
		// Si le formulaire est incorrect ou n'est pas encore remplit : la localisation de départ est la localisation courante
		$localisation = $this->em->getRepository('IpcProgBundle:Localisation')->find($this->last_loc_graph_id);
	}
	// Récupération de la liste des En-têtes de module associés à la localisation courante
	$entity_typeGenerateur = $localisation->getTypeGenerateur();
	$entities_moduleEnTeteAssocies 	= $entity_typeGenerateur->getModulesEnteteLive();
	// Récupération de tous les modules d'entête live : La méthode = findAll avec tri sur le champs description
	$entities_moduleEnTete = $this->em->getRepository('IpcProgBundle:ModuleEnteteLive')->findBy(array(), array('description' => 'ASC'));
	$tabDesEnteteLive = array();
	// Récupération de tous les modules d'en tête appartenant au type de générateur (VP, ES ...) de l'automate
	foreach ($entities_moduleEnTete as $entity_moduleEntete) {
		$valid = false;
		foreach($entities_moduleEnTeteAssocies as $entity_moduleEnTeteAssocies) {
			if ($entity_moduleEntete->getDesignation() == $entity_moduleEnTeteAssocies->getDesignation()) {
				$valid = true;
				break;
			}
		}
		if ($valid == true) {
			$tabDesEnteteLive[$entity_moduleEntete->getDesignation()] = $entity_moduleEntete->getDescription();
		}
	}
	// Récupération des donneesLive de la localisation courante	
	$entitiesTuile = $this->em->getRepository('IpcProgBundle:donneeLive')->findBy(array('localisation' => $localisation), array('categorie'=>'ASC', 'label' => 'ASC'));
	// Récupération de la liste des données Live de la localisation courante
	return $this->render('IpcConfigurationBundle:Configuration:FormulairesLive/addDonneeLive.html.twig', array(
		'form' => $form->createView(),
		'last_loc_graph_id' => $this->last_loc_graph_id,
		'tabDesEnteteLive' => $tabDesEnteteLive,
		'localisationCourante' => $localisation,
		'entitiesTuile'	=> $entitiesTuile,
		'sessionCourante' => $this->session->getSessionName(),
        'tabSessions' => $this->session->getTabSessions()
	));
}

public function addFormExploitationGenAction($idLocalisation = null) {
	$this->initialisationListe();
	if ($idLocalisation == null) {
		$idLocalisation=$this->last_loc_graph_id;
	}
	$entity_formLiveExploitGen = new FormExploitationGenerateur();
	$entity_formLiveExploitGen->setLabel('Générateur');
	$entity_formLiveExploitGen->setIdLocalisation($idLocalisation);
	$formLiveExploitGene = $this->createForm(new LiveExploitationGenerateurType(), $entity_formLiveExploitGen);
	// Récupération du service Requête
	$requete = $this->get('request');
	if ($formLiveExploitGene->handleRequest($requete)->isValid()) {
		// Création de la donnée Live
		$donneeLive = new DonneeLive();
		$label = $_POST['FormLiveExploitationGenerateur']['label'].';Arrêt;Maintien en température;Production';	
		$adresse = $_POST['FormLiveExploitationGenerateur']['adPhaseExploit'].';'.$_POST['FormLiveExploitationGenerateur']['adModeExploit'];
		$type = 'INT;INT';
		$famille = 'enTeteGenerateur';
		$typeFamilleLive = $this->em->getRepository('IpcProgBundle:TypeFamilleLive')->findOneByDesignation($famille);
		$localisation = $this->em->getRepository('IpcProgBundle:Localisation')->find($_POST['FormLiveExploitationGenerateur']['idLocalisation']);
		$entityConfigModbus = $this->em->getRepository('IpcConfigurationBundle:ConfigModbus')->findOneByLocalisation($localisation);
		// Si l'entité n'existe pas -> Création de celle ci
		if ($entityConfigModbus == null) {
			$entityConfigModbus = new ConfigModbus($localisation, null);
		}
		$this->em->persist($entityConfigModbus);
		$donneeLive->setLabel($label);
		$donneeLive->setAdresse($adresse);
		$donneeLive->setType($type);
		$donneeLive->setFamille($famille);
		$donneeLive->setPlacement('enTete');
		$donneeLive->setLocalisation($localisation);
		$donneeLive->setConfigModbus($entityConfigModbus);
		$donneeLive->setTypeFamille($typeFamilleLive);
		$this->em->persist($donneeLive);
		$this->em->flush();
		$this->get('session')->getFlashBag()->add('info', "En-tête : Exploitation du générateur enregistré");
		return $this->redirect($this->generateUrl('ipc_add_donnee_live'));
	}
	return $this->render('IpcConfigurationBundle:Configuration:FormulairesLive/formExploitationGenerateur.html.twig', array(
		'form' => $formLiveExploitGene->createView(),
		'sessionCourante' => $this->session->getSessionName(),
        'tabSessions' => $this->session->getTabSessions()
	));
}



public function addFormBruleurAction ($idLocalisation = null) {
	$this->initialisationListe();
	if ($idLocalisation == null) {
		$idLocalisation = $this->last_loc_graph_id;
	}
	$entity_formBruleur = new FormBruleur();
	$entity_formBruleur->setIdLocalisation($idLocalisation);
	$entity_formBruleur->setLabel('Brûleur 1');
	$entity_formBruleur->setLabel2('Brûleur 2');
	$formBruleur = $this->createForm(new LiveBruleurType(), $entity_formBruleur);
	// Récupération du service Requête
	$requete = $this->get('request');
	if ($formBruleur->handleRequest($requete)->isValid()) {
		$bifoyer = isset($_POST['biFoyer'])?true:false;
		// Création de la donnée Live
		$donneeLive = new DonneeLive();
		$label = $_POST['FormLiveBruleur']['label'].';'.$_POST['FormLiveBruleur']['label2'].';Eteint;Val Charge brûleur 2';
		$adresse = $_POST['FormLiveBruleur']['adPrFlamme1'].';'.$_POST['FormLiveBruleur']['adChBruleur1'];
		$type = 'BOOL;REAL';
		$famille = 'enTeteBruleur';
		$typeFamilleLive = $this->em->getRepository('IpcProgBundle:TypeFamilleLive')->findOneByDesignation($famille);
		if ($bifoyer == true) {
			if (($_POST['FormLiveBruleur']['adPrFlamme1'] == null) ||( $_POST['FormLiveBruleur']['adPrFlamme2'] == null) || ($_POST['FormLiveBruleur']['adChBruleur1'] == null) || ($_POST['FormLiveBruleur']['adChBruleur2'] ==null)) {
				$this->get('session')->getFlashBag()->add('info', "Veuillez remplir tous les champs svp.");
				return $this->render('IpcConfigurationBundle:Configuration:FormulairesLive/formBruleur.html.twig', array(
					'form' => $formBruleur->createView(),
					'sessionCourante' => $this->session->getSessionName(),
        			'tabSessions' => $this->session->getTabSessions()
				));
			} 
			$label .= ';Eteint;Charge brûleur 2';
			$adresse .= ';'.$_POST['FormLiveBruleur']['adPrFlamme2'].';'.$_POST['FormLiveBruleur']['adChBruleur2'];
			$type .= ';BOOL;REAL';
			$famille = "enTeteBruleurBiFoyer";
		}
		$localisation = $this->em->getRepository('IpcProgBundle:Localisation')->find($_POST['FormLiveBruleur']['idLocalisation']);
		$entityConfigModbus = $this->em->getRepository('IpcConfigurationBundle:ConfigModbus')->findOneByLocalisation($localisation);
		// Si l'entité n'existe pas -> Création de celle ci
		if ($entityConfigModbus == null) {
			$entityConfigModbus = new ConfigModbus($localisation, null);
		}
		$this->em->persist($entityConfigModbus);
		$donneeLive->setLabel($label);
		$donneeLive->setAdresse($adresse);
		$donneeLive->setType($type);
		$donneeLive->setFamille($famille);
		$donneeLive->setTypeFamille($typeFamilleLive);
		$donneeLive->setPlacement('enTete');
		$donneeLive->setLocalisation($localisation);
		$donneeLive->setConfigModbus($entityConfigModbus);
		$this->em->persist($donneeLive);
		$this->em->flush();
		$this->get('session')->getFlashBag()->add('info', "En-tête : Brûleur enregistré");
		return $this->redirect($this->generateUrl('ipc_add_donnee_live'));
	}
	return $this->render('IpcConfigurationBundle:Configuration:FormulairesLive/formBruleur.html.twig', array(
		'form' => $formBruleur->createView(),
		'sessionCourante' => $this->session->getSessionName(),
        'tabSessions' => $this->session->getTabSessions()
	));
}


public function addFormBaseAction($idLocalisation = null, $base = null) {
	$this->initialisationListe();
	$entity_formBase = new FormBase();
	$formBase = $this->createForm(new LiveBaseType(), $entity_formBase);
	// Récupération du service Requête
	$requete = $this->get('request');
	if ($formBase->handleRequest($requete)->isValid()) {
		switch ($entity_formBase->getFamilleBase()) {
		case 'enTeteNiveau':
			$typeFamille = 'EnTeteNiveau';
			$messageInfo = "En-tête : Niveau enregistré";
			break;
		case 'enTetePression':
			$typeFamille = 'enTetePression';
			$messageInfo = "En-tête : Pression enregistré";
			break;
		case 'enTeteConductivite':
			$typeFamille = 'enTeteConductivite';
			$messageInfo = "En-tête : Conductivité chaudière enregistré";
			break;
		case 'enTeteDebitVapeur':
			$typeFamille = 'enTeteDebitVapeur';
			$messageInfo = "En-tête : Débit vapeur enregistré";
			break;
		case 'enTeteDebitReseau':
			$typeFamille = 'enTeteDebitReseau';
			$messageInfo = "En-tête : Débit réseau enregistré";
			break;
		case 'enTeteTemperatureDepart':
			$typeFamille = 'enTeteTemperatureDepart';
			$messageInfo = "En-tête : Température de départ enregistrée";
			break;
		case 'enTeteTemperatureRetour':
			$typeFamille = 'enTeteTemperatureRetour';
			$messageInfo = "En-tête : Température de retour enregistrée";
			break;
		case 'enTeteTemperatureBache':
			$typeFamille = 'enTeteTemperatureBache';
			$messageInfo = "En-tête : Température de la bâche enregistrée";
			break;
		}
		$entityTypeFamilleLive = $this->em->getRepository('IpcProgBundle:TypeFamilleLive')->findOneByDesignation($typeFamille);
		$type = 'REAL';
		$entityLocalisation = $this->em->getRepository('IpcProgBundle:Localisation')->find($entity_formBase->getIdLocalisation());
		$entityConfigModbus = $this->em->getRepository('IpcConfigurationBundle:ConfigModbus')->findOneByLocalisation($entityLocalisation);
		// Si l'entité n'existe pas -> Création de celle ci
		if ($entityConfigModbus == null) {
			$entityConfigModbus = new ConfigModbus($entityLocalisation, null);
		}
		$this->em->persist($entityConfigModbus);
		// Création de la donnée Live
		$donneeLive = new DonneeLive();
		$donneeLive->setLabel($entity_formBase->getLabelBase());
		$donneeLive->setAdresse($entity_formBase->getAdBase());
		$donneeLive->setFamille($entity_formBase->getFamilleBase());
		$donneeLive->setTypeFamille($entityTypeFamilleLive);
		$donneeLive->setLocalisation($entityLocalisation);
		$donneeLive->setConfigModbus($entityConfigModbus);
		$donneeLive->setType($type);
		$donneeLive->setPlacement('enTete');
		$this->em->persist($donneeLive);
		$this->em->flush();

		$this->get('session')->getFlashBag()->add('info', $messageInfo);
		return $this->redirect($this->generateUrl('ipc_add_donnee_live'));
	}
	// Si le formulaire n'est pas retourné : Initialisation des paramètres par défaut : label, famille, idLocalisation
	if ($idLocalisation == null) {
		$idLocalisation = $this->last_loc_graph_id;
	}
	$entity_formBase = new FormBase();
	$entity_formBase->setIdLocalisation($idLocalisation);
	$label = '';
	$labelAdresse = '';
	switch ($base) {
	case 'niveau':
		$labelAdresse = "Adresse du mot : Niveau";
		$label = "Niveau";
		$famille = 'enTeteNiveau';
		break;
	case 'pression':
		$labelAdresse = "Adresse du mot : Pression";
		$label = "Pression";
		$famille = 'enTetePression';
		break;
	case 'conductivitech':
		$labelAdresse = "Adresse du mot : Conductivité";
		$label = "Conductivité";
		$famille = 'enTeteConductivite';
		break;
	case 'debitvapeur':
		$labelAdresse = "Adresse du mot : Débit vapeur";
		$label = "Débit";
		$famille = 'enTeteDebitVapeur';
		break;
	case 'debitreseau':
		$labelAdresse = "Adresse du mot : Débit réseau";
		$label = "Débit";
		$famille = 'enTeteDebitReseau';
		break;
	case 'temperaturedepart':
		$labelAdresse = "Adresse du mot : Température de départ";
		$label = "Température";
		$famille = 'enTeteTemperatureDepart';
		break;
	case 'temperatureretour':
		$labelAdresse = "Adresse du mot : Température de retour";
		$label = "Température";
		$famille = 'enTeteTemperatureRetour';
		break;
	case 'temperaturebache':
		$labelAdresse = "Adresse du mot : Température de la bâche";
		$label = "Température";
		$famille = 'enTeteTemperatureBache';
		break;
	}
	$entity_formBase->setFamilleBase($famille);
	$entity_formBase->setLabelBase($label);
	$formBase = $this->createForm(new LiveBaseType(), $entity_formBase);
	return $this->render('IpcConfigurationBundle:Configuration:FormulairesLive/formBase.html.twig', array(
		'form' => $formBase->createView(),
		'labelAdresse' => $labelAdresse,
		'label' => $label,
		'sessionCourante' => $this->session->getSessionName(),
        'tabSessions' => $this->session->getTabSessions()
	));
}


public function addFormCombustibleAction ($idLocalisation = null) {
	$this->initialisationListe();
	if ($idLocalisation == null) { $idLocalisation = $this->last_loc_graph_id; }
		$entity_formCombustible = new FormCombustible();
		$entity_formCombustible->setLabel('Combustible');
		$entity_formCombustible->setLabel2('Combustible 2');
		$entity_formCombustible->setIdLocalisation($idLocalisation);
		$formCombustible = $this->createForm(new LiveCombustibleType(), $entity_formCombustible);
		// Récupération du service Requête
		$requete = $this->get('request');
		if ($formCombustible->handleRequest($requete)->isValid()) {
			$bifoyer = isset($_POST['biFoyer'])?true:false;
			// Création de la donnée Live
			$donneeLive = new DonneeLive();
			$label = $_POST['FormLiveCombustible']['label'].';'.$_POST['FormLiveCombustible']['label2'].';Gaz;Fioul;Mélange';
			$adresse = $_POST['FormLiveCombustible']['adCombustibleBruleur1'];
			$type = 'INT';
			$famille = 'enTeteCombustible';
			$typeFamilleLive = $this->em->getRepository('IpcProgBundle:TypeFamilleLive')->findOneByDesignation($famille);
			if ($bifoyer == true) {
				if ($_POST['FormLiveCombustible']['adCombustibleBruleur2'] == null) {
					$this->get('session')->getFlashBag()->add('info', "Veuillez remplir tous les champs svp.");
					return $this->render('IpcConfigurationBundle:Configuration:FormulairesLive/formCombustible.html.twig', array(
						'form' => $formCombustible->createView(),
						'sessionCourante' => $this->session->getSessionName(),
        				'tabSessions' => $this->session->getTabSessions()
					));
				}
				$adresse .= ';'.$_POST['FormLiveCombustible']['adCombustibleBruleur2'];
				$type .= ';INT';
				$famille = 'enTeteCombustibleBiFoyer';
			}
			$localisation = $this->em->getRepository('IpcProgBundle:Localisation')->find($_POST['FormLiveCombustible']['idLocalisation']);
			$entityConfigModbus = $this->em->getRepository('IpcConfigurationBundle:ConfigModbus')->findOneByLocalisation($localisation);
			// Si l'entité n'existe pas -> Création de celle ci
			if ($entityConfigModbus == null) {
				$entityConfigModbus = new ConfigModbus($localisation, null);
			}
			$this->em->persist($entityConfigModbus);
			$donneeLive->setLabel($label);
			$donneeLive->setAdresse($adresse);
			$donneeLive->setType($type);
			$donneeLive->setFamille($famille);
			$donneeLive->setTypeFamille($typeFamilleLive);
			$donneeLive->setPlacement('enTete');
			$donneeLive->setLocalisation($localisation);
			$donneeLive->setConfigModbus($entityConfigModbus);
			$this->em->persist($donneeLive);
			$this->em->flush();
			$this->get('session')->getFlashBag()->add('info', "En-tête : Combustible enregistré");
			return $this->redirect($this->generateUrl('ipc_add_donnee_live'));
	}
	return $this->render('IpcConfigurationBundle:Configuration:FormulairesLive/formCombustible.html.twig', array(
		'form' => $formCombustible->createView(),
		'sessionCourante' => $this->session->getSessionName(),
        'tabSessions' => $this->session->getTabSessions()
	));
}




public function addFormEtatGenAction ($idLocalisation = null) {
	$this->initialisationListe();
	if ($idLocalisation == null) { 
		$idLocalisation = $this->last_loc_graph_id; 
	}
	$entity_formEtatGenerateur = new FormEtatGenerateur();
	$entity_formEtatGenerateur->setLabel('Etat');
	$entity_formEtatGenerateur->setIdLocalisation($idLocalisation);
	$formEtatGenerateur = $this->createForm(new LiveEtatGenerateurType(), $entity_formEtatGenerateur);
	// Récupération du service Requête
	$requete = $this->get('request');
	if ($formEtatGenerateur->handleRequest($requete)->isValid()) {
		// Création de la donnée Live
		$donneeLive = new DonneeLive();
		$label = $_POST['FormLiveEtatGenerateur']['label'].";Val Nombre d'évenements;Val Nombre d'alarmes;Val Nombre de défauts";
		$adresse = $_POST['FormLiveEtatGenerateur']['adNbEvenements'].';'.$_POST['FormLiveEtatGenerateur']['adNbAlarmes'].';'.$_POST['FormLiveEtatGenerateur']['adNbDefauts'];
		$type = 'INT;INT;INT';
		$famille = 'enTeteEtat';
		$localisation = $this->em->getRepository('IpcProgBundle:Localisation')->find($_POST['FormLiveEtatGenerateur']['idLocalisation']);
		$entityConfigModbus = $this->em->getRepository('IpcConfigurationBundle:ConfigModbus')->findOneByLocalisation($localisation);
		$typeFamilleLive = $this->em->getRepository('IpcProgBundle:TypeFamilleLive')->findOneByDesignation($famille);
		// Si l'entité n'existe pas -> Création de celle ci
		if ($entityConfigModbus == null) {
			$entityConfigModbus = new ConfigModbus($localisation, null);
		}
		$this->em->persist($entityConfigModbus);
		$donneeLive->setLabel($label);
		$donneeLive->setAdresse($adresse);
		$donneeLive->setType($type);
		$donneeLive->setFamille($famille);
		$donneeLive->setTypeFamille($typeFamilleLive);
		$donneeLive->setPlacement('enTete');
		$donneeLive->setLocalisation($localisation);
		$donneeLive->setConfigModbus($entityConfigModbus);
		$this->em->persist($donneeLive);
		$this->em->flush();
		$this->get('session')->getFlashBag()->add('info', "En-tête : Etat du générateur enregistré");
		return $this->redirect($this->generateUrl('ipc_add_donnee_live'));
	}
	return $this->render('IpcConfigurationBundle:Configuration:FormulairesLive/formEtatGenerateur.html.twig', array(
		'form' => $formEtatGenerateur->createView(),
		'sessionCourante' => $this->session->getSessionName(),
        'tabSessions' => $this->session->getTabSessions()
	));
}

public function changeTuileLiveAction($idLocalisation = null) {
	$this->initialisationListe();
	if ($idLocalisation == null) { $idLocalisation = $this->last_loc_graph_id; } 
		// Récupération du service Requête
		$requete = $this->get('request');
		if ($requete->getMethod() == 'GET') {
			$idDonneeLive = $_GET['choixTuile'];
			$entityDonneeLive = $this->em->getRepository('IpcProgBundle:DonneeLive')->find($idDonneeLive);
		} else {
			$entityDonneeLive = new DonneeLive();
		}
		// Création du formulaire des données Live
		$localisation = $this->em->getRepository('IpcProgBundle:Localisation')->find($idLocalisation);
		$form = $this->createForm(new ModifDonneeLiveType(), $entityDonneeLive);
		// Si le formulaire est correct : Modification de la donnée en base
		if ($form->handleRequest($requete)->isValid()) {
			$entityExistante  = $this->em->getRepository('IpcProgBundle:DonneeLive')->find($entityDonneeLive->getId());
			if (isset($_POST['DonneeLive']['suppression'])) {
				$this->em->remove($entityExistante);
			} else {
				$entityExistante->setCategorie($entityDonneeLive->getCategorie());
				$entityExistante->setLabel($entityDonneeLive->getLabel());
				$entityExistante->setAdresse($entityDonneeLive->getAdresse());
				$entityExistante->setType($entityDonneeLive->getType());
				$entityExistante->setUnite($entityDonneeLive->getUnite());
				$entityExistante->setIcone($entityDonneeLive->getIcone());
				$entityExistante->setFamille($entityDonneeLive->getFamille());
				$entityExistante->setCouleur($entityDonneeLive->getCouleur());
				$entityExistante->setValeurEntreeVrai($entityDonneeLive->getValeurEntreeVrai());
				$entityExistante->setValeurSortieVrai($entityDonneeLive->getValeurSortieVrai());
				$entityExistante->setValeurSortieFaux($entityDonneeLive->getValeurSortieFaux());
				$entityExistante->setRegistresAndBit();
			}
			// Modification de la base de données
			$this->em->flush();
			return $this->redirect($this->generateUrl('ipc_add_donnee_live'));
		}
		// Récupération de la liste des données Live de la localisation courante
		return $this->render('IpcConfigurationBundle:Configuration:FormulairesLive/changeTuileLive.html.twig', array(
			'form' => $form->createView(),
			'last_loc_graph_id' => $this->last_loc_graph_id,
			'localisationCourante' => $localisation,
			'sessionCourante' => $this->session->getSessionName(),
        	'tabSessions' => $this->session->getTabSessions()
		));
	}
}
