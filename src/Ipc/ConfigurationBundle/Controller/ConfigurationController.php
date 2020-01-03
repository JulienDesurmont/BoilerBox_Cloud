<?php
//src/Ipc/ConfigurationBundle/Controller/ConfigurationController.php

namespace Ipc\ConfigurationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerAware;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\FOSUserEvents;

use Ipc\UserBundle\Entity\User;
use Ipc\ProgBundle\Entity\Site;
use Ipc\ProgBundle\Entity\Module;
use Ipc\ProgBundle\Entity\Mode;
use Ipc\ProgBundle\Entity\Genre;
use Ipc\ProgBundle\Entity\Erreur;
use Ipc\ProgBundle\Entity\Fichier;
use Ipc\ProgBundle\Entity\FichierIpc;
use Ipc\ProgBundle\Entity\Rapport;
use Ipc\ProgBundle\Entity\FichierRapport;
use Ipc\ProgBundle\Entity\ModuleEnteteLive;
use Ipc\ProgBundle\Entity\TypeGenerateur;
use Ipc\ProgBundle\Entity\Localisation;
use Ipc\ProgBundle\Entity\Configuration;
use Ipc\ProgBundle\Entity\Donnee;
use Ipc\ProgBundle\Entity\Donneetmp;
use Ipc\ConfigurationBundle\Entity\ModbusMaster;
use Ipc\ConfigurationBundle\Entity\IecType;

use Ipc\UserBundle\Form\Type\RegistrationType;

use Ipc\ProgBundle\Form\Type\FichierIpcType;
use Ipc\ProgBundle\Form\Type\ModuleEnteteLiveType;
use Ipc\ProgBundle\Form\Type\LocalisationType;
use Ipc\ProgBundle\Form\Type\ConfigurationType;
use Ipc\ProgBundle\Form\Type\SiteType;
use Ipc\ProgBundle\Form\Handler\SiteHandler;
use Ipc\ProgBundle\Form\Handler\LocalisationHandler;
use Ipc\ProgBundle\Form\Handler\ConfigurationHandler;


use Ipc\ConfigurationBundle\Form\Type\TypeGenerateurType;
use Ipc\ConfigurationBundle\Form\Type\RapportType;
use Ipc\ConfigurationBundle\Form\Type\ModifyRapportType;
use Ipc\ConfigurationBundle\Form\Type\FichierRapport2Type;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormError;

use Ipc\ConfigurationBundle\Entity\Requete;
use Ipc\ConfigurationBundle\Form\Type\RequeteType;
use Ipc\ConfigurationBundle\Form\Handler\RequeteHandler;



class ConfigurationController extends Controller {
private $fillnumber;
private $liste_localisations;
private $session;
private $datedebut;
private $datefin;
private $liste_heures;
private $liste_minutes;
private $tab_modules;
private $pageTitle;
private $pageActive;
private $userLabel;
private $adresseMot;
private $highPercentLimit = 80;
private $last_loc_graph_id;
private $em;
private $service_configuration;
private $fichier_log;
private $log;
private $document_root;

public function constructeur(){
	$this->em = $this->get('ipc_prog.connectbd')->getManager();
	$this->fichier_log = 'parametresIpc.log';
	$this->log = $this->container->get('ipc_prog.log');
    if (empty($this->session)) {
        $service_session = $this->container->get('ipc_prog.session');
        $this->session = $service_session;
    }
	$this->document_root = getenv("DOCUMENT_ROOT");
}

private function initialisation() {
	$this->constructeur();	
	$this->service_configuration = $this->get('ipc_prog.configuration');
	$this->pageTitle = $this->session->get('pageTitle');
	$this->pageActive = $this->session->get('page_active');
	$this->userLabel = $this->session->get('label');
	$this->fillnumbers = $this->get('ipc_prog.fillnumbers');
	$this->tab_modules = array();
    if ($this->userLabel == '' ) {
        if ($this->get('security.context')->isGranted('ROLE_ADMIN')) {
            $this->userLabel = 'Administrateur';
        } elseif ($this->get('security.context')->isGranted('ROLE_SUPERVISEUR')) {
            $this->userLabel = 'Superviseur';
        } elseif ($this->get('security.context')->isGranted('ROLE_TECHNICIEN')) {
            $this->userLabel = 'Technicien';
        } elseif ($this->get('security.context')->isGranted('ROLE_USER')) {
            $this->userLabel = 'Client';
        }
		$this->session->set('label', $this->userLabel);
    }
}

public function initialiseModbus() {
	$this->constructeur();
	$connexion = $this->get('ipc_prog.connectbd');
	$dbh = $connexion->getDbh();
	$this->adresseMot = array();
	// Adresse du mot reservé pour déclanchement de l'écriture du fichier de log
	$this->adresseMot['binaryFiles'] = 2005;
	// Ecriture de la valeur 1 pour création des fichiers de logs sur l'automate
	$this->adresseMot['dataBinaryFiles'] = array(1);
	$this->adresseMot['typeBinaryFiles'] = array("INT");
	// Récupération de la liste des automates
	$site = new Site();
	$site_id = $site->SqlGetIdCourant($dbh);
	$site = $this->em->getRepository('IpcProgBundle:Site')->find($site_id);
	$this->adresseMot['automates'] = $site->getLocalisations();
	return(0);
}

public function getInfosSessionAction() {
	$this->initialisation();
	$tab_session = array();
	$tab_session['pageTitle'] = $this->pageTitle;
	$tab_session['pageActive'] = $this->pageActive;
	$tab_session['userLabel'] = $this->userLabel;
	echo json_encode($tab_session);
	$response = new Response();
	return $response;
}

public function configurationAction(Request $request) {
	$this->initialisation();
	// Si la configuration d'origine n'a pas été crée, création de celle-ci
	$configuration_auto = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('configuration_installation');
	if (! $configuration_auto) {
		$this->configurationAuto('init');
		return $this->redirect($this->generateUrl('ipc_param_ipc'));
	}
	return  $this->render('IpcConfigurationBundle:Configuration:configuration.html.twig', array(
		'sessionCourante' => $this->session->getSessionName(),
        'tabSessions' => $this->session->getTabSessions()
    ));
}



// Création des variables d'heures et de minutes pour pouvoir appeler l'affichage de la page d'accueil
public function definePeriode() {
	$this->constructeur();
	if (! isset($this->messagePeriode)) {
		$this->fillnumbers = $this->get('ipc_prog.fillnumbers');
		$session_date = $this->session->get('session_date');
		if (empty($session_date)) {
    		$translator = $this->get('translator');
    		$this->messagePeriode = $translator->trans('periode.info.none');
		} else {
			$this->messagePeriode = $session_date['messagePeriode'];
		}
		//      Préparation des listes déroulantes pour les heures et minutes de début et de fin
		$this->liste_heures = array(); 
		for ($i=0 ; $i<=23 ; $i++) {
			array_push($this->liste_heures, $this->fillnumbers->fillNumber($i, 2));
		}
		$this->liste_minutes = array();
		for ($i=0 ; $i<=59 ; $i++) {
			array_push($this->liste_minutes, $this->fillnumbers->fillNumber($i, 2));
		}
		$this->datedebut = date("d-m-Y");
		$this->datefin = $this->datedebut;
	}
}

// Fonction appelée lors d'un changement de la variable 'Maximum execution Time'
// Modifie les valeurs dans le script shell + Relance du script + Modification de la valeur en base de données + Retour de la valeur pour affichage dans la zone de texte
public function changeMaxExecTimeAction() {
	$this->constructeur();
	$configuration 	= new configuration();
	$connexion 	= $this->get('ipc_prog.connectbd');
	$dbh = $connexion->getDbh();
	$maxExecTime = $_POST['maxExecTime'];
	$pattern = '/^\d+$/';
	if (preg_match($pattern, $maxExecTime)) {
		// Mise à jour de la variable en base
				$maxExecTimeId = $configuration->SqlGetId($dbh, 'maximum_execution_time');
		$configuration->setId($maxExecTimeId);
		$configuration->setValeur($maxExecTime);
		$configuration->SqlUpdateValue($dbh);
	} else {
		$maxExecTime = $configuration->SqlGetParam($dbh, 'maximum_execution_time');
	}
	// Modification dans le script sh : suppHighRequest.sh
	$commande = "cat ".$this->document_root."/web/sh/GestionSql/suppHighRequest.sh | sed s/tempAttente=.*/tempAttente=$maxExecTime/g > ".$this->document_root."/web/sh/GestionSql/suppHighRequest.sh_tempo";
	$execCmd = exec($commande);
	$commande = "mv ".$this->document_root."/web/sh/GestionSql/suppHighRequest.sh_tempo ".$this->document_root."/web/sh/GestionSql/suppHighRequest.sh";
	$execCmd = exec($commande);
	$commande = "chmod 777 ".$this->document_root."/web/sh/GestionSql/suppHighRequest.sh";
	$execCmd = exec($commande);
	// Demande d'Arrêt-Relance du script
	$commande = $this->document_root."/web/sh/GestionSql/arretRelanceSuppHighRequest.sh";
	$execCmd = exec($commande);
	echo $maxExecTime;
	$dbh = $connexion->disconnect();
	return new Response();
}

// Fonction qui vérifie le nombre maximum de requêtes autorisées : La restriction ne s'applique que pour les clients et les techniciens
public function getMaxRequetesAction() {
	$this->initialisation();
	if (! $this->get('security.context')->isGranted('ROLE_SUPERVISEUR')) {
		if (isset($_GET['page'])) {
			$page = $_GET['page'];
			if ($page == 'graphique') {
				if ($this->get('security.context')->isGranted('ROLE_TECHNICIEN')) {
					$param_de_conf = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('graphique_nbmax_requetes');
				} elseif ($this->get('security.context')->isGranted('ROLE_USER')) {
					$param_de_conf = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('autorisation_graphique_nbmax_requetes');    
				}
			} elseif ($page == 'listing') {
				if ($this->get('security.context')->isGranted('ROLE_TECHNICIEN')) {
					$param_de_conf = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('listing_nbmax_requetes');
				} elseif ($this->get('security.context')->isGranted('ROLE_USER')) {
					$param_de_conf = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('autorisation_listing_nbmax_requetes');
				}
			} elseif ($page == 'etat') {
				$param_de_conf = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('autorisation_etat_nbmax_requetes');
			}
			$nb_requetes_max = $param_de_conf->getValeur();
		} else {
			$nb_requetes_max = 0;
		}
	} else {
		$nb_requetes_max = 9999;
	}
	echo $nb_requetes_max;
	$response = new Response();
	$response->setPrivate();
	$response->setMaxAge(3600);
	$response->setETag(md5($response->getContent()));
	$response->headers->addCacheControlDirective('must-revalidate', true);
	return $response;
}


//	Fonction qui importe les fichiers excels Table_echange_Ipc  : des données initiales de la base
public function importAction() {
	$this->initialisation();
	$fichieripc	= new FichierIpc();
	// Création du formulaire grâce à la méthode du contrôleur
	$form = $this->createForm(new FichierIpcType, $fichieripc);
	// On récupère la requête
	$requete = $this->get('request');
	// Gestion de la connexion à la base de donnée
	$connexion = $this->get('ipc_prog.connectbd');
	$dbh = $connexion->getDbh();
	// On passe en argument un tableau contenant la liste des site et des localisations associées
	$tabDesLocalisations = array();
	$sites = $this->em->getRepository('IpcProgBundle:Site')->findAll();
	foreach ($sites as $site) {
		$tmpIdSite = $site->getId();
		$tabDesLocalisations[$tmpIdSite]['SiteName'] = $site->getIntitule();
		$localisations = $site->getLocalisations();
		foreach ($localisations as $entityLocalisation) {
			$tmpIdLocalisation = $entityLocalisation->getId();
			$tabDesLocalisations[$tmpIdSite]['Localisations'][$tmpIdLocalisation]['LocalisationName'] = $entityLocalisation->getDesignation();
		}
	}
	// On vérifie qu'elle est de type POST
	if ($requete->getMethod() == 'POST') {
		if (isset($_POST['valider'])) {
			$choixProg = $_POST['choixProg'];
			// Si une demande de  mise à jour est effectuée : Pas de modification de la date de fin 
			if ($choixProg == 'maj') {
				$this->session->set('dateDebutMode', 'maj');
			}
			if ($choixProg == 'nouveau') {
				$date_prog_deb = trim($_POST["date_deb_mode"]);
				//  La date est passée au format YYYY-mm-dd. Il faut la transformer au format YYYY/mm/dd HH:ii:ss 
				$date_prog_deb = $this->fillnumbers->changeFormatDate($date_prog_deb, 'sql', 'setHour');
				$pattern = '/^\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2}$/';
				if (! preg_match($pattern, $date_prog_deb, $analyse_date)) {
					$this->get("session")->getFlashBag()->add('info', 'Date incorrectement formatée');	
					$dbh = $connexion->disconnect();
					return $this->render('IpcConfigurationBundle:Configuration:import_table_echange_ipc.html.twig', array(
						'form' => $form->createView(),
						'choixSection' => 'choixLocalisation',
						'sessionCourante' => $this->session->getSessionName(),
        				'tabSessions' => $this->session->getTabSessions()
					));
				}
				$this->session->set('dateDebutMode', $date_prog_deb);
			}
			if ($_POST['description'] != null) {
				$descriptionMode = array();
				$descriptionMode['type'] = $choixProg;
				$descriptionMode['texte'] = htmlspecialchars($_POST['description']);
				$this->session->set('descriptionMode', $descriptionMode);
			}
			$dbh = $connexion->disconnect();
			return $this->render('IpcConfigurationBundle:Configuration:import_table_echange_ipc.html.twig', array(
				'form' => $form->createView(),
				'choixSection' => 'choixFichier',
				'sessionCourante' => $this->session->getSessionName(),
        		'tabSessions' => $this->session->getTabSessions()
			));
		} else {
			// On fait le lien requete<->formulaire
			$form->handleRequest($requete);
			// A partir de maintenant le fichier contient les données du formulaire
			if ($form->isValid()) {
				$nomfichier = $fichieripc->getNomdOrigine();
				if ($nomfichier === null) {
					$this->getRequest()->getSession()->getFlashBag()->add('info', "Aucun fichier sélectionné. Veuillez indiquer le fichier de la table d'échange");
					$dbh = $connexion->disconnect();
					return $this->render('IpcConfigurationBundle:Configuration:import_table_echange_ipc.html.twig', array(
						'form' => $form->createView(),
						'choixSection' => 'choixLocalisation',
						'sessionCourante' => $this->session->getSessionName(),
        				'tabSessions' => $this->session->getTabSessions()
					));
				}
				$pattern = '/^tei_(.+?)_(.+?)_#(.+?)#_.+?$/';
				if (! preg_match($pattern, $nomfichier, $tabNomFichier)) {
					 $this->getRequest()->getSession()->getFlashBag()->add('info', 'Nomenclature du fichier attendue : TEI_CodeAffaire_NumLocalisation_#(CodeProgramme || noprog)#_Horodatage. ( Fichier reçu '.$nomfichier.' )');
					$dbh = $connexion->disconnect();
					return $this->render('IpcConfigurationBundle:Configuration:import_table_echange_ipc.html.twig', array(
						'form' => $form->createView(),
						'choixSection' => 'choixLocalisation',
						'sessionCourante' => $this->session->getSessionName(),
        				'tabSessions' => $this->session->getTabSessions()
					));
				}
				$designation_mode = $tabNomFichier[3];
				$dateDebutMode = $this->session->get('dateDebutMode');
				// On récupère le service qui permet l'importation des données du fichier en base
				$service_importipc = $this->container->get('ipc_prog.importipc');
				$fichieripc = $service_importipc->importation($fichieripc, $dateDebutMode);
				// Si le retour de la fonction est une chaine de caractère s'est qu'une erreur s'est produite 
				if (gettype($fichieripc) == 'string') {
					$this->getRequest()->getSession()->getFlashBag()->add('info', $fichieripc);
				} else {
					// Indication de la désignation du programme si elle est indiquée et si le programme fonctionne en multi-sites
					$description_mode = $this->session->get('descriptionMode');
					if (! empty($description_mode)) {
						$mode = $this->em->getRepository('IpcProgBundle:Mode')->findOneByDesignation($designation_mode);
						if ($description_mode['type'] == 'maj') {
							$mode->setDescription($mode->getDescription().'; '.$description_mode['texte']);
						} else {
							$mode->setDescription($description_mode['texte']);
						}
						$this->em->flush();
					}
					$message = 'Le fichier '.$fichieripc->getNom().' a bien été traité : '.$fichieripc->getNombreMessages().' données analysées';
					$this->getRequest()->getSession()->getFlashBag()->add('info', $message);
					// Réinitialisation de la variable des modules
					$this->container->get('ipc_prog.session.boilerbox')->reinitialisationSession('liste_des_requetes');

					// Retour à la page d'accueil avec un message indiquant la prise en compte du fichier
				}
				$this->definePeriode();
				$dbh = $connexion->disconnect();
				return ($this->render('IpcProgBundle:Prog:accueil.html.twig', array(
					'messagePeriode' => $this->messagePeriode,
					'liste_heures' => $this->liste_heures,
					'liste_minutes' => $this->liste_minutes,
					'datedebut' => $this->datedebut,
					'datefin' => $this->datefin,
					'sessionCourante' => $this->session->getSessionName(),
        			'tabSessions' => $this->session->getTabSessions()
				)));
			}
		}
	}
	// Ici soit le formulaire n'est pas valide, soit la requête est de type GET donc signifiant que le visiteur vient d'arriver sur la page
	// On passe la méthode createView à la vue pour pouvoir afficher le formulaire
	$dbh = $connexion->disconnect();
	$response = new Response($this->renderView('IpcConfigurationBundle:Configuration:import_table_echange_ipc.html.twig', array(
		'form' => $form->createView(),
		'tabDesLocalisations' => $tabDesLocalisations,
		'choixSection' => 'choixLocalisation',
		'sessionCourante' => $this->session->getSessionName(),
        'tabSessions' => $this->session->getTabSessions()
	)));
	$response->setETag(md5($response->getContent()));
	return $response;
}

public function voiripcAction(Fichier $fichier) {
	$this->initialisation();
	$response = new Response($this->renderView('IpcConfigurationBundle:Configuration:voiripc.html.twig', array(
		'fichier' => $fichier,
		'sessionCourante' => $this->session->getSessionName(),
        'tabSessions' => $this->session->getTabSessions()
	)));
	$response->setPublic();
	$response->setETag(md5($response->getContent()));
	return $response;
}
	
// Fonction qui retourne le formulaire permettant de paramétrer la configuration de l'IPC
// Cette fonction est également utilisée lors de la modification de paramètres
public function parametresipcAction(Request $requete) {
	$this->initialisation();
	$service_password = $this->get('ipc_prog.password');
	// Récupération des paramètres de configuration
	// Gestion de la connexion à la base de donnée
	$connexion = $this->get('ipc_prog.connectbd');
	$dbh = $connexion->getDbh();
	// Récupération de la requête
	$request = $this->get('request');
	if ($request->getMethod() == 'POST') {
		$choix = $_POST['valider'];
		// Si le choix de l'utilisateur est 'initialisation' : (Re)Définition des paramètres de configuration
		if ($choix == 'initialisation') {
			$this->configurationAuto('init');	
		} elseif ($choix == 'reinitialisation') {
			$this->configurationAuto('reinit'); 
		} elseif ($choix == 'newVersion') {
			$this->configurationAuto('newVersion');
		} else {
			$parametre = htmlspecialchars($_POST['parametre']);
			if (isset($_POST['parametreAdmin'])) {
				$parametreAdmin = true;
			} else {
				$parametreAdmin = false;
			}
			$designation = htmlspecialchars($_POST['designation']);
			$valeur = htmlspecialchars($_POST['valeur']);

			$idconf = intval(htmlspecialchars($_POST['idconf']));
			$choix = $_POST['valider'];
			// Gestion des ' ajout d'un \
			$pattern = '/^[\w\.]+$/';
			if (! preg_match($pattern, $parametre)) {
				$this->get("session")->getFlashBag()->add('info',"Veillez à ne pas mettre de caractère spéciaux ['\"é@-...] dans le nom de paramètre $parametre  svp");
			} else {
				// Si le paramètre contient le mot 'password', sa valeur est chiffrée
				$pattern = '/password/';
				if (preg_match($pattern, $parametre)) {
					$valeur = $service_password->hashPassword($valeur);
				}
				$pattern = '/\'/';
				$replacement = '\\\'';
				$designation = preg_replace($pattern, $replacement, $designation);
				$valeur	= preg_replace($pattern, $replacement, $valeur);
				$configuration = new Configuration();
				$configuration->setParametre($parametre);
				$configuration->setDesignation($designation);
				$configuration->setValeur($valeur);
				$configuration->setParametreAdmin($parametreAdmin);
				$configuration->setId($idconf);
				if (($parametre != '') && ($designation != '') || ($valeur != '')) {
					// Si l'identifiant est 0 c'est que la demande concerne l'ajout d'un nouveau paramètre
					switch ($choix) {
					case 'Ajouter' :
						$configuration->SqlInsert($dbh);
						$message_tmp = "Nouveau paramètre enregistré".$this->setMessageConfiguration($parametre);
						break;
					case 'Modifier' :
						$acceptUpdate = true;
						# Gestion des conditions particulières de modification des paramètre ipc
						$this->get("session")->getFlashBag()->add('info',"Paramètre [ $parametre ]<br /><br />");
						switch ($parametre) {
						case 'ping_intervalle' : 
							// Pour la modification du ping, on refuse un ping de moins de 10 secondes pour ne pas engorger le trafic réseau
							if ($valeur < 10000) {
								$this->get("session")->getFlashBag()->add('info',"Valeur minimale acceptée : 10000 (10 secondes)"); 
								$acceptUpdate = false;
							}
							break;
						case 'ping_timeout' : 
							// Le timeout doit être inférieur à l'intervalle des pings
							$ping_intervalle = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('ping_intervalle')->getValeur();
							if ($valeur > $ping_intervalle) {
								$this->get("session")->getFlashBag()->add('info',"La valeur doit être inférieure à l'intervalle des pings ($ping_intervalle)");
								$acceptUpdate = false;
							}
							break;
						case 'popup_simplifiee' :
							//	La valeur doit etre 0 ou 1 
							if (($valeur != 0)  && ($valeur != 1)) {
								$this->get("session")->getFlashBag()->add('info',"Valeur non correcte");
								$acceptUpdate = false;
							} else {
								$this->container->get('ipc_prog.session.boilerbox')->reinitialisationSession('localisations_modules');
							}
							break;
						case 'live_timeout_automate' :
							// Le temps d'attente du chargement d'une localisation du Live ne peut pas être inférieur à 10 secondes
							if ($valeur < 10000) {
                                $this->get("session")->getFlashBag()->add('info',"Valeur minimale acceptée : 10000 (10 secondes)");
                                $acceptUpdate = false;
                            }
							break;
						case 'numero_version' :
							//	Le numéro de version ne peut être modifié que depuis le Controller Configuration
							$this->get("session")->getFlashBag()->add('info',"Paramètre non modifiable");
							$acceptUpdate = false;	
							break;
						case 'siecle' :
                            //  Le numéro de version ne peut être modifié que depuis le Controller Configuration
                            $this->get("session")->getFlashBag()->add('info',"Paramètre non modifiable");
                            $acceptUpdate = false;
							break;
						case 'live_refresh_listing' :
							$valeurRefreshGraphique = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('live_refresh_graphique')->getValeur();
							//  Le rafraichissement des données live de la partie Listing doit être inférieur à celui des Graphiques et > 3000 (3secondes)
							if ($valeur > 3000) { 
								$this->get("session")->getFlashBag()->add('info',"Valeur minimale acceptée : 3000 (3 secondes)");
								$acceptUpdate = false;
							} elseif ($valeur > $valeurRefreshGraphique) {
								$this->get("session")->getFlashBag()->add('info',"La valeur doit être inférieure à celle du paramétre 'live_refresh_graphique' ($valeurRefreshGraphique)");
								$acceptUpdate = false;
							}
							break;
						case 'live_refresh_graphique' :
							$valeurRefreshListing = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('live_refresh_listing')->getValeur();
							if ($valeur < $valeurRefreshListing) {
								$this->get("session")->getFlashBag()->add('info',"La valeur doit être supérieure à celle du paramétre 'live_refresh_listing' ($valeurRefreshListing)");
                                $acceptUpdate = false;
							}
							break;
						default :
							$pattern_param_loc = '/^192.+?_(.+?)$/';
							if (preg_match($pattern_param_loc, $parametre, $tabParamLoc)) {
								if (substr($tabParamLoc[1], 0, 14) == "defaut_bruleur") {
									$parametreDeLocalisation = substr($tabParamLoc[1], 0, 14);
									$this->session->remove('tabdefautsbruleurs');
								}
							}
							break;
						}
						if ($acceptUpdate === true) {
							$old_entity_configuration = $this->em->getRepository('IpcProgBundle:Configuration')->find($idconf);
							// Mise à jour du paramètre + Enregistrement dans le fichier de log
							$configuration->SqlUpdate($dbh);
							$this->log->setLog("Modification du paramètre ".$configuration->getParametre(), $this->fichier_log);
							$this->log->setLog("Anciennes données : ".$old_entity_configuration->getDesignation()." [ ".$old_entity_configuration->getValeur()." ]", $this->fichier_log);
							$this->log->setLog("Nouvelles données : ".$configuration->getDesignation()." [ ".$configuration->getValeur()." ]", $this->fichier_log);
							$message_tmp = "Modification effectuée".$this->setMessageConfiguration($parametre);
							$this->get("session")->getFlashBag()->add('info',$message_tmp);
							return $this->configurationAction($requete);
						}
						break;
					case 'Supprimer' :
						$configuration->SqlDelete($dbh);
						$message_tmp = "Suppression effectuée".$this->setMessageConfiguration($parametre);
						break;
					}
					$this->get("session")->getFlashBag()->add('info',$message_tmp);
				} else {
					$this->get("session")->getFlashBag()->add('info',"Tous les champs doivent être renseignés");
				}
			}
		}
	}
	$liste_configurations = $this->em->getRepository('IpcProgBundle:Configuration')->findBy(array(), array('parametre' => 'ASC'));
	$dbh = $connexion->disconnect();
	$response = new Response($this->renderView('IpcConfigurationBundle:Configuration:configuration_ipc.html.twig', array(
		'liste_configurations' => $liste_configurations,
		'sessionCourante' => $this->session->getSessionName(),
        'tabSessions' => $this->session->getTabSessions()
	)));
	$response->setPublic();
	$response->setETag(md5($response->getContent()));
	return $response;
}

//	Fonction qui affiche l'indication de se relogguer ou pas pour prise en compte des paramètres de configuration
private function setMessageConfiguration($parametre) {
	$message = '';
	// Tableau des paramètres nécessitant la reconnexion pour prise en compte.
	$tab_reboot = array('activation_modbus', 'numero_version');
	if (in_array($parametre,$tab_reboot)) {
		$message = " - Veuillez vous reconnecter pour la prise en compte des changements";
	}
	return $message;
}


// Fonction qui crée la configuration par défaut
// Si type = init -> Réinstallation de la configuration d'origine !! Ne correspond pas forcément aux paramètres du site !!
// = reinit -> Réinstallation de la configuration d'origine - Peut être mise en place sur tous les sites
// autre	-> Installation des paramètres de la nouvelle version
public function configurationAuto($type) {
	$this->constructeur();
	$connexion = $this->get('ipc_prog.connectbd');
	$dbh = $connexion->getDbh();
	$liste_conf = array();
	// Configuration initialement appliquée lors du premier accès à l'interface web ipc
	// Ces valeurs ne sont pas raffraichis lors de la réinitialisation pour ne pas modifier les paramètres utilisateurs
	if ($type == 'init') {
		$liste_conf['date_dmes']['description'] = 'Compte Technicien : Date minimum de début de période de recherche (JJ-MM-YYYY)';
		$liste_conf['date_dmes']['value'] = '01-01-2014';
		$liste_conf['date_dmes']['parametreAdmin'] = true;
		$liste_conf['autorisation_dmes']['description'] = 'Compte Client : Date minimum de début de période de recherche (JJ-MM-YYYY)';
		$liste_conf['autorisation_dmes']['value'] = '01-01-2014';
		$liste_conf['autorisation_dmes']['parametreAdmin'] = true;
		$liste_conf['date_de_mise_en_service']['description'] = 'Compte Admin : Date de création de la base de donnée (format JJ-MM-YYYY)';
		$liste_conf['date_de_mise_en_service']['value']	= '01-01-2000';
		$liste_conf['date_de_mise_en_service']['parametreAdmin'] = true;
		$liste_conf['multi_sites']['description'] = "Prise en compte de la gestion multi-sites (0:'Non'  1:'Oui')";
		$liste_conf['multi_sites']['value'] = 1;
		$liste_conf['multi_sites']['parametreAdmin'] = true;
		// Paramètres LIVE / Modbus -------------------------------------
		$liste_conf['live_modules_nb']['description'] = "Nombre maximum de séries [live_modules] qui peuvent être affichées (affichage des X premières séries)";
		$liste_conf['live_modules_nb']['value'] = 4;
		$liste_conf['live_modules_nb']['parametreAdmin'] = false;
		$liste_conf['live_automate_nb']['description'] = "Nombre d'automates affichés";
		$liste_conf['live_automate_nb']['value'] = 2;
		$liste_conf['live_automate_nb']['parametreAdmin'] = false;
		$liste_conf['live_automate_1']['description'] = "Paramètres de l'automate 1 (adresse ip;numéro de la première série à afficher)";
		$liste_conf['live_automate_1']['value'] = "192.168.0.110;1;2;3;4";
		$liste_conf['live_automate_1']['parametreAdmin'] = false;
		$liste_conf['live_automate_2']['description'] = "Paramètres de l'automate 2 (adresse ip;numéro de la deuxième série à afficher)";
		$liste_conf['live_automate_2']['value'] = "192.168.0.120;1;2;3";
		$liste_conf['live_automate_2']['parametreAdmin'] = false;
		/* Liste des séries live */
		$liste_conf['live_modules1']['description'] = "Production;Modules utilisés pour l'affichage de la série 1 du Live (Categorie/NuméroModule/NuméroMessage XXYYZZ)";
		$liste_conf['live_modules1']['value'] = 'CV0102;LT0106;PT0106';
		$liste_conf['live_modules1']['parametreAdmin'] = false;
		$liste_conf['live_modules2']['description'] = "Gaz;Modules utilisés pour l'affichage de la série 2 du Live (Categorie/NuméroModule/NuméroMessage XXYYZZ)";
		$liste_conf['live_modules2']['value'] = 'PT0106;PT1106;TT3606;TT3706;CC0705;CC0805';
		$liste_conf['live_modules2']['parametreAdmin'] = false;
		$liste_conf['live_modules3']['description'] = "Ethylène;Modules utilisés pour l'affichage de la série 3 du Live (Categorie/NuméroModule/NuméroMessage XXYYZZ)";
		$liste_conf['live_modules3']['value'] = 'PT2406;PT2506;TT5606;TT5706;ZV0402;ZV0502';
		$liste_conf['live_modules3']['parametreAdmin'] = false;
		$liste_conf['live_modules4']['description'] = "Evazole;Modules utilisés pour l'affichage de la série 4 du Live (Categorie/NuméroModule/NuméroMessage XXYYZZ)";
		$liste_conf['live_modules4']['value'] = 'PT2806;PT2906;TT6106;TT6206;ZV0602;ZV0702';
		$liste_conf['live_modules4']['parametreAdmin'] = false;
		// Paramètres Clients	----------------------------------------
		$liste_conf['autorisation_genres_listing']['description'] = "Liste des genres autorisés au compte client pour l'affichage des listing";
		$liste_conf['autorisation_genres_listing']['value'] = null;
		$liste_conf['autorisation_genres_listing']['parametreAdmin'] = true;
		$liste_conf['autorisation_genres_graphique']['description'] = "Liste des genres autorisés au compte client pour l'affichage des graphiques";
		$liste_conf['autorisation_genres_graphique']['value'] = null;
		$liste_conf['autorisation_genres_graphique']['parametreAdmin'] = true;
		$liste_conf['autorisation_impression_listing']['description'] = "Définit le droit d'impression au compte client des listing";
		$liste_conf['autorisation_impression_listing']['value'] = 0;
		$liste_conf['autorisation_impression_listing']['parametreAdmin'] = true;
		$liste_conf['autorisation_impression_graphique']['description'] = "Définit le droit d'impression au compte client des graphiques";
		$liste_conf['autorisation_impression_graphique']['value'] = 0;
		$liste_conf['autorisation_impression_graphique']['parametreAdmin'] = true;
		$liste_conf['live_url']['description'] = "Url du live";
		$liste_conf['live_url']['value'] = "z4e4r6C693@2f6e8d_5q6h8j";
		$liste_conf['live_url']['parametreAdmin'] = true;
		// Paramètre pour le module INTERVENTION
		$liste_conf['trigramme_intervention']['description'] = "Trigramme désignant le module d'intervention";
		$liste_conf['trigramme_intervention']['value'] = "GE0804";
		$liste_conf['trigramme_intervention']['parametreAdmin'] = false;

        $liste_conf['192.168.0.110_frequence_rapport_ftp']['description'] = "Fréquence d'envoi des rapports : (dateRapport; NbRapportsEnErreurAvantEnvoi(-1 pour bloquer); DernierNombreDeRapportsEnErreur(0 pour tous))";
        $liste_conf['192.168.0.110_frequence_rapport_ftp']['value'] = "16-11-2015;4;0;1";
        $liste_conf['192.168.0.110_frequence_rapport_ftp']['parametreAdmin'] = true;
        $liste_conf['admin_ipcWeb']['description'] = "Email de l'administrateur boilerbox";
        $liste_conf['admin_ipcWeb']['value'] = "j.desurmont@lci-group.fr";
        $liste_conf['admin_ipcWeb']['parametreAdmin'] = true;
        $liste_conf['admin_email']['description'] = "Adresse email de l'administrateur";
        $liste_conf['admin_email']['value'] = 'Assistance_IBC@lci-group.fr';
        $liste_conf['admin_email']['parametreAdmin'] = true;

        $liste_conf['configuration_installation']['description'] = "Indique si la configuration automatique a été importée";
        $liste_conf['configuration_installation']['value'] = 1;
        $liste_conf['configuration_installation']['parametreAdmin'] = true;

        $liste_conf['dollar_0']['description'] = "Valeur affectée au caractère 'Dollar' lorsque la valeur est égale à 0";
        $liste_conf['dollar_0']['value'] = "Désactivation";
        $liste_conf['dollar_0']['parametreAdmin'] = false;
        $liste_conf['dollar_1']['description'] = "Valeur affectée au caractère 'Dollar' lorsque la valeur est égale à 1";
        $liste_conf['dollar_1']['value'] = "Activation";
        $liste_conf['dollar_1']['parametreAdmin'] = false;
        $liste_conf['timezone']['description'] = "Paramètre de gestion du fuseau horaire";
        $liste_conf['timezone']['value'] = "Europe/Paris";
        $liste_conf['timezone']['parametreAdmin'] = true;

        // Paramètres des dossiers de sauvegarde des fichiers
        $liste_conf['dossier_fichiers_originaux']['description'] = 'Dossier destination des fichiers à convertir en binaire pour mise en base';
        $liste_conf['dossier_fichiers_originaux']['value'] = $this->document_root.'/web/uploads/fichiers_origines';
        $liste_conf['dossier_fichiers_originaux']['parametreAdmin'] = true;
        $liste_conf['dossier_fichiers_tmpftp']['description'] = 'Dossier destination des fichiers transférés par Ftp';
        $liste_conf['dossier_fichiers_tmpftp']['value'] = $this->document_root.'/web/uploads/fichiers_tmpftp';
        $liste_conf['dossier_fichiers_tmpftp']['parametreAdmin'] = true;
        // Paramètre de configuration des alertes emails
        $liste_conf['nb_max_jours_sans_transfert']['description'] = "Nombre de jours sans transfert ftp avant l'envoi d'une alerte email";
        $liste_conf['nb_max_jours_sans_transfert']['value'] = 4;
        $liste_conf['nb_max_jours_sans_transfert']['parametreAdmin'] = false;
        $liste_conf['seuil_alerte_filesystem']['description'] = "Seuil d'alerte du filesystem pour l'envoi du rapport système (en %)";
        $liste_conf['seuil_alerte_filesystem']['value'] = 80;
        $liste_conf['seuil_alerte_filesystem']['parametreAdmin'] = true;
        // Paramètres modbus
        $liste_conf['activation_modbus']['description'] = "Activation de la fonction modbus (0:Non(défaut)  1:Oui).";
        $liste_conf['activation_modbus']['value'] = "1";
        $liste_conf['activation_modbus']['parametreAdmin'] = true;
	}
	if (($type == 'init') || ($type == 'reinit')) {
		// Paramètres Technicien	-----------------------------------
		$liste_conf['ecart_max']['description'] = 'Compte Technicien : Nombre de jours maximum pour la période';
		$liste_conf['ecart_max']['value'] = 90;
		$liste_conf['ecart_max']['parametreAdmin'] = false;
		$liste_conf['listing_nbmax_requetes']['description'] = 'Compte Technicien : Nombre maximum de requêtes listing';
		$liste_conf['listing_nbmax_requetes']['value'] = 3;
		$liste_conf['listing_nbmax_requetes']['parametreAdmin'] = false;
		$liste_conf['graphique_nbmax_requetes']['description'] = 'Compte Technicien : Nombre maximum de requêtes graphique';
		$liste_conf['graphique_nbmax_requetes']['value'] = 3;
		$liste_conf['graphique_nbmax_requetes']['parametreAdmin'] = false;
		// Paramètres Client	-----------------------------------
		$liste_conf['autorisation_ecart_max']['description'] = 'Compte Client : Nombre de jours maximum de la période';
		$liste_conf['autorisation_ecart_max']['value'] = 90;
		$liste_conf['autorisation_ecart_max']['parametreAdmin'] = false;
		$liste_conf['autorisation_listing_nbmax_requetes']['description'] = 'Compte Client : Nombre maximum de requêtes listing';
		$liste_conf['autorisation_listing_nbmax_requetes']['value'] = 3;
		$liste_conf['autorisation_listing_nbmax_requetes']['parametreAdmin'] = false;
		$liste_conf['autorisation_graphique_nbmax_requetes']['description'] = 'Compte Client : Nombre maximum de requêtes graphique';
		$liste_conf['autorisation_graphique_nbmax_requetes']['value'] = 3;
		$liste_conf['autorisation_graphique_nbmax_requetes']['parametreAdmin'] = false;
		// Paramètres Admin	-----------------------------------
		$liste_conf['admin_ecart_max']['description'] = 'Compte administrateur : Nombre de jours maximum de la période';
		$liste_conf['admin_ecart_max']['value'] = 90;
		$liste_conf['admin_ecart_max']['parametreAdmin'] = true;
		$liste_conf['autorisation_etat_nbmax_requetes']['description'] = 'Nombre de requêtes maximum autorisées pour la création de Etat1';
		$liste_conf['autorisation_etat_nbmax_requetes']['value'] = 5;
		$liste_conf['autorisation_etat_nbmax_requetes']['parametreAdmin'] = false;
		// Paramètre de configuration de requêtes sql
		$liste_conf['maximum_execution_time']['description'] = "Temps maximum d'execution des requêtes avant qu'elles ne soient killées (seconde)";
		$liste_conf['maximum_execution_time']['value'] = 120;
		$liste_conf['maximum_execution_time']['parametreAdmin'] = true;
    	// Paramètres pour le module GRAPHIQUE
    	$liste_conf['graphique_max_points']['description'] = "CRITIQUE : Nombre de points maximum par courbe (4000 par défaut. !!!  Augmenter cette limite peut entrainer un blocage applicatif)";
    	$liste_conf['graphique_max_points']['value'] = 4000;
		$liste_conf['graphique_max_points']['parametreAdmin'] = false;
    	$liste_conf['live_graph_nb_mois']['description'] = "La recherche Live graphique portera sur ces X derniers mois";
    	$liste_conf['live_graph_nb_mois']['value'] = 12;
		$liste_conf['live_graph_nb_mois']['parametreAdmin'] = false;
    	$liste_conf['live_graph_nb_points_max']['description'] = "La recherche Live graphique retournera X points maximum par courbe";
    	$liste_conf['live_graph_nb_points_max']['value'] = 500;
		$liste_conf['live_graph_nb_points_max']['parametreAdmin'] = false;
    	$liste_conf['live_nb_mois']['description'] = "La recherche Live des événements portera sur ces X derniers mois";
    	$liste_conf['live_nb_mois']['value'] = 6;
		$liste_conf['live_nb_mois']['parametreAdmin'] = false;
    	$liste_conf['live_nb_evenements']['description'] = "Nombre d'évenements retournés";
    	$liste_conf['live_nb_evenements']['value'] = 20;
		$liste_conf['live_nb_evenements']['parametreAdmin'] = false;
    	$liste_conf['live_refresh_listing']['description'] = "Durée de rafraichissement de la page live des listings (en millisecondes)";
    	$liste_conf['live_refresh_listing']['value'] = 3000;
		$liste_conf['live_refresh_listing']['parametreAdmin'] = true;
    	$liste_conf['live_refresh_graphique']['description'] = "Durée de rafraichissement de la page live des graphiques (en millisecondes)";
    	$liste_conf['live_refresh_graphique']['value'] = 60000;
		$liste_conf['live_refresh_graphique']['parametreAdmin'] = true;
    	$liste_conf['arrondi']['description'] = "Nombre de chiffres après la virgules à afficher";
    	$liste_conf['arrondi']['value'] = 4;
		$liste_conf['arrondi']['parametreAdmin'] = false;
    	$liste_conf['siecle']['description'] = 'Siècle actuel';
    	$liste_conf['siecle']['value'] = 21;
		$liste_conf['siecle']['parametreAdmin'] = true;
    	$liste_conf['live_timeout_automate']['description'] = 'Durée en millisecondes avant timeout pour la connexion live à un automate';
    	$liste_conf['live_timeout_automate']['value'] = 10000;
		$liste_conf['live_timeout_automate']['parametreAdmin'] = true;
    	$liste_conf['ping_timeout']['description'] = 'Durée en millisecondes avant timeout pour la réponse du ping';
    	$liste_conf['ping_timeout']['value'] = 5000;
		$liste_conf['ping_timeout']['parametreAdmin'] = true;
    	$liste_conf['ping_intervalle']['description'] = 'Intervalle entre les tentatives de ping';
    	$liste_conf['ping_intervalle']['value'] = 20000;
		$liste_conf['ping_intervalle']['parametreAdmin'] = true;
    	$liste_conf['192.168.0.110_defaut_bruleur_1']['description'] = "Code du message défaut bruleur 1 pour la localisation d'adresse ip 192.168.0.110";
    	$liste_conf['192.168.0.110_defaut_bruleur_1']['value'] = "CS7070";
    	$liste_conf['192.168.0.110_defaut_bruleur_1']['parametreAdmin'] = false;
    	$liste_conf['192.168.0.110_defaut_bruleur_2']['description'] = "Code du message défaut bruleur 2 pour la localisation d'adresse ip 192.168.0.110";
    	$liste_conf['192.168.0.110_defaut_bruleur_2']['value'] = "CS7062";
    	$liste_conf['192.168.0.110_defaut_bruleur_2']['parametreAdmin'] = false;
    	$liste_conf['duree_periode_analyse_bruleur']['description'] = "Durée de la période Analyse Bruleur (en seconde)";
    	$liste_conf['duree_periode_analyse_bruleur']['value'] = 30;
    	$liste_conf['duree_periode_analyse_bruleur']['parametreAdmin'] = false;
    	$liste_conf['envoi_rapports_journaliers']['description'] = "Envoi systématique du rapport journalier (même si aucune erreur détectée) (0:Non(défaut)  1:Oui).";
    	$liste_conf['envoi_rapports_journaliers']['value'] = false;
    	$liste_conf['envoi_rapports_journaliers']['parametreAdmin'] = true;
    	$liste_conf['sauvegarde_rapports_journaliers']['description'] = "Sauvegarde du rapport journalier au format html (0:Non  1:Oui(défaut)).";
    	$liste_conf['sauvegarde_rapports_journaliers']['value'] = true;
    	$liste_conf['sauvegarde_rapports_journaliers']['parametreAdmin'] = true;
    	$liste_conf['autorisation_mails']['description'] = "Autorisation de l'envoi des mails";
    	$liste_conf['autorisation_mails']['value'] = true;
    	$liste_conf['autorisation_mails']['parametreAdmin'] = true;
    $liste_conf['rapport_pourcentage_messages_max']['description'] = "Pourcentage maximum avant déclanchement d'une erreur dans le rapport journalier";
    $liste_conf['rapport_pourcentage_messages_max']['value'] = 90;
    $liste_conf['rapport_pourcentage_messages_max']['parametreAdmin'] = true;

    $liste_conf['rapport_nombre_messages_max']['description'] = "Nombre maximum d'occurences d'un message (associé au pourcentage maximum) avant déclanchement d'une erreur dans le rapport journalier";
    $liste_conf['rapport_nombre_messages_max']['value'] = 1000;
    $liste_conf['rapport_nombre_messages_max']['parametreAdmin'] = true;

    $liste_conf['rapport_nombre_max_messages']['description'] = "Nombre maximum d'occurences d'un message avant déclanchement d'une erreur dans le rapport journalier";
    $liste_conf['rapport_nombre_max_messages']['value'] = 80000;
    $liste_conf['rapport_nombre_max_messages']['parametreAdmin'] = true;

    // Variable de la nouvelle version
    $liste_conf['etat_amc_codes_syst_stat_io_avert']['description'] = "Listes des modules de type Syst. Stat. Io Avert.";
    $liste_conf['etat_amc_codes_syst_stat_io_avert']['value'] = "GE2094;GE2095;GE2096;GE2097;GE2098;GE2099";
    $liste_conf['etat_amc_codes_syst_stat_io_avert']['parametreAdmin'] = true;


    $liste_conf['popup_simplifiee']['description'] = "Indique si la popup ne doit afficher que les messages enregistrés en base  (0:'Non' 1:'Oui')";
    $liste_conf['popup_simplifiee']['value'] = 1;
    $liste_conf['popup_simplifiee']['parametreAdmin'] = false;

    // Paramètres rapports : Remplace l'ancien paramètre 'rapports_erreur' -----------------------------------------
    $liste_conf['autorisation_rapports_erreur']['description'] = "Autorisation d'envoi des rapports d'erreurs (0:Non  1:Oui)";
    $liste_conf['autorisation_rapports_erreur']['value'] = 1;
    $liste_conf['autorisation_rapports_erreur']['parametreAdmin'] = false;


    // Paramètres pour les impressions des fichiers CSV
    $liste_conf['limitation_excel_graphique']['description'] = "Limitation du nombre de lignes autorisées dans les fichiers excel lors des impressions des données graphiques";
    $liste_conf['limitation_excel_graphique']['value'] = 500000;
    $liste_conf['limitation_excel_graphique']['parametreAdmin'] = false;


    $liste_conf['limitation_export_sql_graphique']['description'] = "Limitation sql du nombre de lignes autorisées lors de l'exportation des données graphiques";
    $liste_conf['limitation_export_sql_graphique']['value'] = 200000;
    $liste_conf['limitation_export_sql_graphique']['parametreAdmin'] = false;





    $liste_conf['limitation_excel_listing']['description'] = "Limitation du nombre de lignes autorisées dans les fichiers excel lors des impressions des données de listing";
    $liste_conf['limitation_excel_listing']['value'] = 200000;
    $liste_conf['limitation_excel_listing']['parametreAdmin'] = false;


    $liste_conf['limitation_export_sql_listing']['description'] = "Limitation sql du nombre de lignes autorisées lors de l'exportation des données de listing";
    $liste_conf['limitation_export_sql_listing']['value'] = 200000;
    $liste_conf['limitation_export_sql_listing']['parametreAdmin'] = false;


	}
	// Variable de la nouvelle version
	$liste_conf['numero_version']['description'] = "Numéro de version du site web";
	$liste_conf['numero_version']['value'] = "2.11.0";
	$liste_conf['numero_version']['parametreAdmin'] = true;

	$liste_conf['nb_jours_nb_db_donnees']['description'] = "Nombre de jours pour la recherche du nombre de données dans la table t_donnee";
	$liste_conf['nb_jours_nb_db_donnees']['value'] = "3";
	$liste_conf['nb_jours_nb_db_donnees']['parametreAdmin'] = true;


		

	// Pour chaque paramètre de configuration / Insert Ou Update
	foreach ($liste_conf as $intitule => $conf) {
		$configuration = new Configuration();
		$configuration->setValeur($conf['value']);
		$configuration->setDesignation($conf['description']);
		$configuration->setParametre($intitule);
		$configuration->setParametreAdmin($conf['parametreAdmin']);
		$id_config = $configuration->SqlGetId($dbh, $intitule);
		// Si le paramètre existe : Mise à jour
		if ($id_config) {
			$configuration->setId($id_config);
			$configuration->SqlUpdate($dbh);
		} else {
			// Sinon création du paramètre
			$configuration->SqlInsert($dbh);
		}
	}
	$dbh = $connexion->disconnect();
	// Réinitialisation des variables de session
	$this->container->get('ipc_prog.session.boilerbox')->reinitialisationSession('localisations_modules');
}

public function creationUserAction() {
	$this->initialisation();
	// Service de connexion à la base de donnée IPC
	$connexion = $this->get('ipc_prog.connectbd');
	$dbh = $connexion->getDbh();
	// Récupération des informations sur l'utilisateur courant
	// Récupération du service
	$security = $this->container->get('security.context');
	// Récupération du token
	$token = $security->getToken();
	// Récupération de l'utilisateur: = anon pour un utilisateur anonyme
	$id_current_user = $token->getUser()->getId();
	$request = $this->get('request');
	if ($request->getMethod() == 'POST') {
		$choix = $_POST['choix'];
		switch ($choix) {
		case 'Supprimer':
			$id = $_POST['userid'];
			$user = $this->em->getRepository('IpcUserBundle:User')->find($id);
			$this->em->remove($user);
			$this->em->flush();
			break;
		case 'Activation':
			$id = $_POST['userid'];
			$user = $this->em->getRepository('IpcUserBundle:User')->find($id);
			$user->changeActivation();
			$this->em->flush();
			break;
		}
	}


	$utilisateur = new User();
	//$form_registration = $this->container->get('fos_user.registration.form');
	//$form_registration = $this->get('form.factory')->create(new RegistrationType, $utilisateur);
	$form_registration = $this->createForm(new RegistrationType, $utilisateur, array(
		'action' => $this->generateUrl('fos_user_registration_register')
	));
	
	$liste_utilisateurs = $this->em->getRepository('IpcUserBundle:User')->findAll();
	$dbh = $connexion->disconnect();
	$response = new Response($this->renderView('IpcUserBundle:Configuration:creationUser.html.twig', array(
		'liste_utilisateurs' => $liste_utilisateurs,
		'id_utilisateur_courant' => $id_current_user,
		'form' => $form_registration->createView(),
		'sessionCourante' => $this->session->getSessionName(),
        'tabSessions' => $this->session->getTabSessions()
	)));
	$response->setPublic();
	$response->setETag(md5($response->getContent()));
	return $response;
}

//      Fonction de création d'un nouveau Site
public function creationSiteAction($numfresh) {
	$this->initialisation();
	$new_session_pageTitle = $this->pageTitle;
	$returnMenu	= false;
	// Service de connexion à la base de donnée IPC
	$connexion = $this->get('ipc_prog.connectbd');
	$dbh = $connexion->getDbh();
	$site = new Site();
	$form = $this->createForm(new SiteType(), $site);
	$localisation = new Localisation();
	$form_localisation = $this->createForm(new LocalisationType(), $localisation);
	// Récupération de la requête
	$request = $this->get('request');
	// Réinitialisation des variables de session
	$this->container->get('ipc_prog.session.boilerbox')->reinitialisationSession('localisations_modules');

	// La liste des types du générateurs sont enregistrés en dur dans le fichiers HTML (dans un champs crée par le formulaire boilerbox et utilisée par javascript)
	// Le nombre de type est censé est fixe. En cas de modification du nombre de type : Création d'un message d'alerte
	// Liste des Types de générateur enregistrés en base
	$entities_typeGenerateur = $this->em->getRepository('IpcProgBundle:TypeGenerateur')->findAll();
	// Si ils différent de la liste entrée en dure dans le fichier html -> annonce par un message
	$tab_TypeGenerateur = array('VP', 'ES', 'SU', 'AC');	//Vapeur','Eau surchauffée','Surchauffeur','Automate des communs'
	$validationTypeGenerateur = true;
	$messageErreur = null;
	// Vérification que le nombre de données est identique
	if (count($tab_TypeGenerateur) != count($entities_typeGenerateur)) {
		$messageErreur = "Nombre de données d'entête non conforme.Merci de voir avec l'administrateur";
		$validationTypeGenerateur = false;
	} else {
		foreach ($entities_typeGenerateur as $entity_typeGenerateur) {
			if (! in_array($entity_typeGenerateur->getMode(), $tab_TypeGenerateur)) {
				$validationTypeGenerateur = false;
				$messageErreur = "Le type de générateur : ".$entity_typeGenerateur->getDesignation()." n'est pas enregistré en base !";
				break;
			}
		}
	}
	if ($messageErreur != null) {
		$this->container->get("session")->getFlashBag()->add('info', $messageErreur);
	}
	// Récupération de la requête
	if ($request->getMethod() == 'POST') {
		$this->session->remove('liste_req');
		$this->session->remove('liste_req_pour_listing');
		$this->session->remove('liste_req_pour_graphique');
		// Si la demande concerne la modification ou la suppression d'un site ou d'une localisation
		$choix = $_POST['valider'];
		switch ($choix) {
		case 'Valider': 
			// Ajout d'un nouveau Site : Vérification que l'affaire indiquée n'existe pas déjà
			$site = new Site();
			$intitule = htmlspecialchars($_POST['Site']['intitule']);
			$affaire = htmlspecialchars($_POST['Site']['affaire']);
			$login_ftp = htmlspecialchars($_POST['Site']['login_ftp']);
			$mot_de_passe_ftp = htmlspecialchars($_POST['Site']['password_ftp']['first']);
			$confirmation = htmlspecialchars($_POST['Site']['password_ftp']['second']);
			// Vérification de la conformité des mdp
			if ($mot_de_passe_ftp != $confirmation) {
				$this->container->get("session")->getFlashBag()->add('info', "Les mots de passes ne sont pas identiques");
				$liste_sites = $this->em->getRepository('IpcProgBundle:Site')->findAll();
				$dbh = $connexion->disconnect();
				$response = new Response($this->renderView('IpcConfigurationBundle:Configuration:creationSite.html.twig', array(
					'liste_sites' => $liste_sites,
					'form_loc' => $form_localisation->createView(),
					'form' => $form->createView(),
					'hasError' 	=> $request->getMethod() == 'POST' && !$form->isValid(),
					'sessionCourante' => $this->session->getSessionName(),
        			'tabSessions' => $this->session->getTabSessions()
				)));
				$response->setPublic();
				$response->setETag(md5($response->getContent()));
				return $response;
			}
			$nbAutomates = 0;
			if (isset($_POST['Site']['siteCourant'])) {
				$siteCourant = 1;
			} else {
				$siteCourant = 0;
			}
			$site->setIntitule($intitule);
			$site->setAffaire($affaire);
			$site->setSiteCourant($siteCourant);
			$site->setLoginFtp($login_ftp);
			$site->setPasswordFtp($mot_de_passe_ftp);
			if ($site->getSiteCourant() == true) {
				// Le précédent Site courant passe à false et sa date de fin d'exploitation est mise à jour
				$site->SqlUncheck($dbh, $site->SqlGetIdCourant($dbh), $site->getDebutExploitationStr());
			}
			$error_donnees = false;
			// Création des nouvelles localisations
			if (isset($_POST['Contact'])) {
				$tabLocalisation = array();
				$nbAutomates = count($_POST['Contact']['Localisations']);
				foreach ($_POST['Contact'] as $localisations) {
					foreach ($localisations as $loc) {
						// Vérification qu'un numéro de localisation ou qu'une adresse ip identique ne sont pas demandés en doublon
						$localisation = new Localisation();	
						$numeroLocalisation = htmlspecialchars($loc['numeroLocalisation']);
						$adresseIp = htmlspecialchars($loc['adresseIp']);
						$adresseModbus = htmlspecialchars($loc['adresseModbus']);
						$designation = htmlspecialchars($loc['designation']);
						$typeGenerateur = $this->em->getRepository('IpcProgBundle:TypeGenerateur')->findOneByMode(htmlspecialchars($loc['typeGenerateur']));
						$loginloc_ftp = htmlspecialchars($loc['login_ftp']);
						$mot_de_passeloc_ftp = htmlspecialchars($loc['password_ftp']['first']);
						$confirmationloc_ftp = htmlspecialchars($loc['password_ftp']['second']);
						// Vérification de la conformité des mdp
						if ($mot_de_passeloc_ftp != $confirmationloc_ftp) {
							$this->container->get("session")->getFlashBag()->add('info', "Les mots de passes d'une localisation ne sont pas identiques");
							$liste_sites = $this->em->getRepository('IpcProgBundle:Site')->findAll();
							$dbh = $connexion->disconnect();
							$response = new Response($this->renderView('IpcConfigurationBundle:Configuration:creationSite.html.twig', array(
								'liste_sites'=> $liste_sites,
								'form' => $form->createView(),
								'form_loc' => $form_localisation->createView(),
								'hasError' => $request->getMethod() == 'POST' && !$form->isValid(),
								'sessionCourante' => $this->session->getSessionName(),
        						'tabSessions' => $this->session->getTabSessions()
							)));
							$response->setPublic();
							$response->setETag(md5($response->getContent()));
							return $response;
						}
						if ((! in_array($adresseIp, $tabLocalisation)) && (! array_key_exists($numeroLocalisation, $tabLocalisation))) {
							// Vérification que le paramètre de configuration 'adressip_frequence_rapport_ftp' existe/ Si il n'existe pas création de celui-ci.
							$parametreFrequenceRapport = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre($adresseIp.'_frequence_rapport_ftp');
							if ($parametreFrequenceRapport === null) {
								$parametreFrequenceRapport = new Configuration();
								$parametreFrequenceRapport->setParametre($adresseIp.'_frequence_rapport_ftp');
								$valeurFrequenceRapport = date('d-m-Y').';4;0;0';
								$parametreFrequenceRapport->setValeur($valeurFrequenceRapport);
								$parametreFrequenceRapport->setDesignation("Fréquence d'envoi des rapports : (dateRapport;MaxRapportAvantEnvoi(-1 pour bloquer,0 pour tous); NombreDeRapport)");
								$parametreFrequenceRapport->setParametreAdmin(true);
								$this->em->persist($parametreFrequenceRapport);
								$this->em->flush();
							}
							$localisation->setNumeroLocalisation($numeroLocalisation);
							$localisation->setAdresseIp($adresseIp);
							$localisation->setAdresseModbus($adresseModbus);
							$localisation->setDesignation($designation);
							$localisation->setTypeGenerateur($typeGenerateur);
							$localisation->setLoginFtp($loginloc_ftp);
							$localisation->setPasswordFtp($mot_de_passeloc_ftp);
							$site->addLocalisation($localisation);
							$tabLocalisation[$numeroLocalisation] = $adresseIp;
						} else {
							$error_donnees = true;
							$this->container->get("session")->getFlashBag()->add('info', "Un numéro de localisation ou une adresse ip est en doublon");
						}
					}
				}
			}
			# Error données = false si aucune erreur n'est rencontrée
			if ($error_donnees == false) {
				$site->setNbAutomates($nbAutomates);
				// Insertion du nouveau Site : Retourne False en cas d'echec
				$this->em->persist($site);
				try {
					$this->em->flush();
					// Si il y a eu modification du site courant : Modification du titre
					if ($siteCourant == 1) {
						$new_session_pageTitle['title'] = $affaire.' : '.$intitule;
						$this->session->set('pageTitle', $new_session_pageTitle);
						$this->service_configuration->setInfoLimitePeriode();
					}
				} catch (\Exception $e) {
					$this->get("session")->getFlashBag()->add('info', "Un Site avec le même nom d'affaire existe déjà");
				}
			}
			break;
		case 'Modifier le site':
			$id = intval(htmlspecialchars($_POST['idconf']));
			$intitule = htmlspecialchars($_POST['intitule']);
			$affaire = htmlspecialchars($_POST['affaire']);
			$login_ftp = htmlspecialchars($_POST['loginFtp']);
			$mot_de_passe = htmlspecialchars($_POST['passwordFtp']);
			$confirmation = htmlspecialchars($_POST['confirmation']);
			$debExploit	= htmlspecialchars($_POST['debutExploitation']);
			$finExploit = htmlspecialchars($_POST['finExploitation']);
			// Si un des paramètres n'est pas définit, retour d'un message d'erreur
			if (($intitule == '') || ($affaire == '') || ($debExploit == '') || ($login_ftp == '')) {
				$this->get("session")->getFlashBag()->add('info', "Veuillez remplir tous les champs svp");
				break;
			}
			// Récupération du site dont l'id est $id
			$site = $this->em->getRepository('IpcProgBundle:Site')->find($id);
			// Si le login ftp diffère du précédent un mot de passe ftp est requis
			if ($login_ftp != $site->getLoginFtp()) {
				if ((! $mot_de_passe) || (! $confirmation)) {
					$this->get("session")->getFlashBag()->add('info', 'Veuillez entrer le mdp pour le nouvel accès ftp svp');
					break;
				}
			}
			if ($mot_de_passe != $confirmation) {
				$this->container->get("session")->getFlashBag()->add('info', "Les mots de passes ne sont pas identiques");
				$liste_sites = $this->em->getRepository('IpcProgBundle:Site')->findAll();
				$dbh = $connexion->disconnect();
				$response = new Response($this->renderView('IpcConfigurationBundle:Configuration:creationSite.html.twig', array(
					'liste_sites' => $liste_sites,
					'form' => $form->createView(),
					'form_loc' => $form_localisation->createView(),
					'hasError' => $request->getMethod() == 'POST' && !$form->isValid(),
					'sessionCourante' => $this->session->getSessionName(),
        			'tabSessions' => $this->session->getTabSessions()
				)));
				$response->setPublic();
				$response->setETag(md5($response->getContent()));
				return $response;
			}
			if (isset($_POST['siteCourant'])) {
				$siteCourant = 1;
			} else {
				$siteCourant = 0;
			}
			$site->setIntitule($intitule);
			$site->setAffaire($affaire);
			$site->setLoginFtp($login_ftp);
			$site->setSiteCourant($siteCourant);
			if ($site->getSiteCourant() == true) {
				// Le précédent Site courant passe à false et sa date de fin d'exploitation est mise à jour
				$site->SqlUncheck($dbh, $site->SqlGetIdCourant($dbh), $site->getDebutExploitationStr());
			}
			// Si les dates sont définies : création des objets Date sinon null
			if ($debExploit) {
				$site->setDebutExploitation(new \Datetime($debExploit));		
			}
			if ($finExploit) {
				$site->setFinExploitation(new \Datetime($finExploit));
			}
			// Mise à jour avec modification des paramètres ftp si un mot de passe est entré
			if ($mot_de_passe) {
				$site->setPasswordFtp($mot_de_passe);
			}
			try {
				$this->em->flush();
				$this->get("session")->getFlashBag()->add('info', "Modification du site effectuée");
				// Si il y a eu modification du site courant : Modification du titre
				if ($siteCourant == 1) {
					$new_session_pageTitle['title'] = $affaire.' : '.$intitule;
					$this->session->set('pageTitle', $new_session_pageTitle);
				}
			} catch(\Exception $e) {
				$this->get("session")->getFlashBag()->add('info', 'Aucune modification effectuée. Veuillez vérifier les paramètres entré svp');	
			}
			break;
		case 'Ajouter la localisation':
			$numeroLocalisation = $this->supprimeZero(htmlspecialchars($_POST['Localisation']['numeroLocalisation']));
			$adresseIp = htmlspecialchars($_POST['Localisation']['adresseIp']);
			$adresseModbus = htmlspecialchars($_POST['Localisation']['adresseModbus']);
			$designation = htmlspecialchars($_POST['Localisation']['designation']);
			$typeGenerateur = $this->em->getRepository('IpcProgBundle:TypeGenerateur')->find(htmlspecialchars($_POST['Localisation']['typeGenerateur']));
			$loginloc_ftp = htmlspecialchars($_POST['Localisation']['login_ftp']);
			$mot_de_passeloc_ftp = htmlspecialchars($_POST['Localisation']['password_ftp']['first']);
			$confirmationloc_ftp = htmlspecialchars($_POST['Localisation']['password_ftp']['second']); 
			// Si un des paramètres n'est pas définit, retour d'un message d'erreur
			if (($adresseIp == '') || ($designation == '') || ($numeroLocalisation == '') || ($loginloc_ftp == '') || ($mot_de_passeloc_ftp == '') || ($adresseModbus == '')) {
				$this->get("session")->getFlashBag()->add('info', "Veuillez remplir tous les champs svp");
				break;
			}
			// Vérification de la conformité des mdp
			if ($mot_de_passeloc_ftp != $confirmationloc_ftp) {
				$this->container->get("session")->getFlashBag()->add('info', "Les mots de passes ne sont pas identiques");
				$liste_sites = $this->em->getRepository('IpcProgBundle:Site')->findAll();
				$dbh = $connexion->disconnect();
				$response = new Response($this->renderView('IpcConfigurationBundle:Configuration:creationSite.html.twig', array(
					'liste_sites' => $liste_sites,
					'form' => $form->createView(),
					'form_loc' => $form_localisation->createView(),
					'hasError' => $request->getMethod() == 'POST' && !$form->isValid(),
					'sessionCourante' => $this->session->getSessionName(),
        			'tabSessions' => $this->session->getTabSessions()
				)));
				$response->setPublic();
				$response->setETag(md5($response->getContent()));
				return $response;
			}
			$id_site = intval(htmlspecialchars($_POST['idconfsite']));
			$site = $this->em->getRepository('IpcProgBundle:Site')->find($id_site);
			$localisation = new Localisation();
			$localisation->setNumeroLocalisation($numeroLocalisation);
			$localisation->setAdresseIp($adresseIp);
			$localisation->setAdresseModbus($adresseModbus);
			$localisation->setDesignation($designation);
			$localisation->setTypeGenerateur($typeGenerateur);
			$localisation->setLoginFtp($loginloc_ftp);
			$localisation->setPasswordFtp($mot_de_passeloc_ftp);
			$localisation->setSite($site);
			// Récupération de la liste des localisations
			$localisations = $this->em->getRepository('IpcProgBundle:Site')->find($id_site)->getLocalisations();
			$tabLocalisation = array();
			// Création d'un tableau répertoriant les numéros des localisations et leur adresses IP
			foreach ($localisations as $tmp_localisation) {
				$tabLocalisation[$tmp_localisation->getNumeroLocalisation()] = $tmp_localisation->getAdresseIp();
			}
			// Si une localisation avec numéro de localisation ou une adresse identique n'existe pas déjà en base de donnée on effectue les modifications
			if (in_array($adresseIp, $tabLocalisation)) {
				$this->get("session")->getFlashBag()->add('info', "l'adresse ip existe déjà");
			} elseif (array_key_exists($numeroLocalisation, $tabLocalisation)) {
				$this->get("session")->getFlashBag()->add('info', 'Le numéro de localisation existe déjà');
			} else {
				// Vérification que le paramètre de configuration 'adressip_frequence_rapport_ftp' existe/ Si il n'existe pas création de celui-ci.
				$parametreFrequenceRapport = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre($adresseIp.'_frequence_rapport_ftp');
				if ($parametreFrequenceRapport === null) {
					$parametreFrequenceRapport = new Configuration();
					$parametreFrequenceRapport->setParametre($adresseIp.'_frequence_rapport_ftp');
					$valeurFrequenceRapport = date('d-m-Y').';4;0;0';
					$parametreFrequenceRapport->setValeur($valeurFrequenceRapport);
					$parametreFrequenceRapport->setDesignation("Fréquence d'envoi des rapports : (date de la connexion;Nb erreurs avant mail(-1 = bloquer,0 = tous); Nb erreurs en cours; Etat de la connexion courante)");
					$parametreFrequenceRapport->setParametreAdmin(true);
					$this->em->persist($parametreFrequenceRapport);
					$this->em->flush();
				}
				$this->em->persist($localisation);
				$this->em->flush();
				$this->get("session")->getFlashBag()->add('info', 'Ajout de la localisation effectué');
				$returnMenu = true;
			}
			break;
		case 'Modifier la localisation':
			$id = intval(htmlspecialchars($_POST['idconfloc']));
			$id_site = intval(htmlspecialchars($_POST['idconfsite']));
			$numeroLocalisation = $this->supprimeZero(htmlspecialchars($_POST['Localisation']['numeroLocalisation']));
			$adresseIp = htmlspecialchars($_POST['Localisation']['adresseIp']);
			$adresseModbus = htmlspecialchars($_POST['Localisation']['adresseModbus']);
			$designation = htmlspecialchars($_POST['Localisation']['designation']);
			$typeGenerateur = $this->em->getRepository('IpcProgBundle:TypeGenerateur')->find(htmlspecialchars($_POST['Localisation']['typeGenerateur']));
			$loginloc_ftp = htmlspecialchars($_POST['Localisation']['login_ftp']);
			$mot_de_passeloc_ftp = htmlspecialchars($_POST['Localisation']['password_ftp']['first']);
			$confirmationloc_ftp = htmlspecialchars($_POST['Localisation']['password_ftp']['second']);
			// Si un des paramètres n'est pas définit, retour d'un message d'erreur
			if (($adresseIp == '') || ($designation == '') || ($numeroLocalisation == '') || ($loginloc_ftp == '')) {
				$this->get("session")->getFlashBag()->add('info', "Veuillez remplir tous les champs svp");
				break;
			}
			// Récupération de l'entité localisation d'id $id
			$localisation = $this->em->getRepository('IpcProgBundle:Localisation')->find($id);
			$localisation->setNumeroLocalisation($numeroLocalisation);
			$localisation->setAdresseIp($adresseIp);
			$localisation->setAdresseModbus($adresseModbus);
			$localisation->setDesignation($designation);
			$localisation->setTypeGenerateur($typeGenerateur);
			// Si un nouveau login Ftp est donnée, un mot de passe doit être renseigné
			if ($loginloc_ftp != $localisation->getLoginFtp()) {
				if ((! $mot_de_passeloc_ftp) || (! $confirmationloc_ftp)) {
					$this->get("session")->getFlashBag()->add('info', "Veuillez entrer le mot de passe pour le nouvel accés ftp svp");
					break;
				}
			}
			// Si un mot de passe est définit :  Vérification de la conformité des mdp
			if ($mot_de_passeloc_ftp) {
				if ($mot_de_passeloc_ftp != $confirmationloc_ftp) {
					$this->container->get("session")->getFlashBag()->add('info', "Les mots de passes ne sont pas identiques");
					$liste_sites = $this->em->getRepository('IpcProgBundle:Site')->findAll();
					$dbh = $connexion->disconnect();
					$response = new Response($this->renderView('IpcConfigurationBundle:Configuration:creationSite.html.twig', array(
						'liste_sites' => $liste_sites,
						'form' => $form->createView(),
						'form_loc' => $form_localisation->createView(),
						'hasError' => $request->getMethod() == 'POST' && !$form->isValid(),
						'sessionCourante' => $this->session->getSessionName(),
        				'tabSessions' => $this->session->getTabSessions()
					)));
					$response->setPublic();
					$response->setETag(md5($response->getContent()));
					return $response;
				}
				// Déjà enregistré auparavent $mot_de_passeloc_ftp       = htmlspecialchars($_POST['passwordlocFtp']);
				$localisation->setLoginFtp($loginloc_ftp);
				$localisation->setPasswordFtp($mot_de_passeloc_ftp);
				// Récupération de la liste des localisations du site
				$localisations = $this->em->getRepository('IpcProgBundle:Site')->find($id_site)->getLocalisations();
				$tabLocalisation = array();
				// Création d'un tableau répertoriant les numéros des localisations et leur adresses IP
				foreach ($localisations as $tmp_localisation) {
					if ($tmp_localisation->getId() != $id) {
						$tabLocalisation[$tmp_localisation->getNumeroLocalisation()] = $tmp_localisation->getAdresseIp();
					}
				}
				// Si une localisation avec numéro de localisation ou une adresse identique n'existe pas déjà en base de donnée on effectue les modifications
				if (in_array($adresseIp, $tabLocalisation)) {
					$this->get("session")->getFlashBag()->add('info', "l'adresse ip existe déjà");
				} elseif (array_key_exists($numeroLocalisation, $tabLocalisation)) {
					$this->get("session")->getFlashBag()->add('info', 'Le numéro de localisation existe déjà');
				} else {
					// Vérification que le paramètre de configuration 'adressip_frequence_rapport_ftp' existe/ Si il n'existe pas création de celui-ci.
					$parametreFrequenceRapport = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre($adresseIp.'_frequence_rapport_ftp');
					if ($parametreFrequenceRapport === null) {
						$parametreFrequenceRapport = new Configuration();
						$parametreFrequenceRapport->setParametre($adresseIp.'_frequence_rapport_ftp');
						$valeurFrequenceRapport = date('d-m-Y').';4;0';
						$parametreFrequenceRapport->setValeur($valeurFrequenceRapport);
						$parametreFrequenceRapport->setDesignation("Fréquence d'envoi des rapports : (dateRapport;MaxRapportAvantEnvoi(-1 pour bloquer,0 pour tous); NombreDeRapport)");
						$parametreFrequenceRapport->setParametreAdmin(true);
						$this->em->persist($parametreFrequenceRapport);
						$this->em->flush();
					}
					$this->em->flush();
					// Retour à la page configuation.html.twig
					$this->get("session")->getFlashBag()->add('info', 'Modification de la localisation effectuée');
				}
			} else {
				// Récupération de la liste des localisations du site
				$localisations = $this->em->getRepository('IpcProgBundle:Site')->find($id_site)->getLocalisations();
				$tabLocalisation = array();
				// Création d'un tableau répertoriant les numéros des localisations et leur adresses IP
				// On n'inclue pas dans le tableau la localisation en cours de modification
				foreach ($localisations as $tmp_localisation) {
					if ($tmp_localisation->getId() != $id) {
						$tabLocalisation[$tmp_localisation->getNumeroLocalisation()] = $tmp_localisation->getAdresseIp();
					}
				}
				// Si une localisation avec numéro de localisation ou une adresse identique n'existe pas déjà en base de donnée on effectue les modifications
				if (in_array($adresseIp, $tabLocalisation)) {
					$this->get("session")->getFlashBag()->add('info', "l'adresse ip existe déjà");
				} elseif (array_key_exists($numeroLocalisation, $tabLocalisation)) {
					$this->get("session")->getFlashBag()->add('info', 'Le numéro de localisation existe déjà');
				} else {
					// Vérification que le paramètre de configuration 'adressip_frequence_rapport_ftp' existe/ Si il n'existe pas création de celui-ci.
					$parametreFrequenceRapport = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre($adresseIp.'_frequence_rapport_ftp');
					if ($parametreFrequenceRapport === null) {
						$parametreFrequenceRapport = new Configuration();
						$parametreFrequenceRapport->setParametre($adresseIp.'_frequence_rapport_ftp');
						$valeurFrequenceRapport = date('d-m-Y').';4;0';
						$parametreFrequenceRapport->setValeur($valeurFrequenceRapport);
						$parametreFrequenceRapport->setDesignation("Fréquence d'envoi des rapports : (dateRapport;MaxRapportAvantEnvoi(-1 pour bloquer,0 pour tous); NombreDeRapport)");
						$parametreFrequenceRapport->setParametreAdmin(true);
						$this->em->persist($parametreFrequenceRapport);
						$this->em->flush();
					}
					$this->em->flush();
					$this->get("session")->getFlashBag()->add('info', 'Modification de la localisation effectuée');
				}
			}
			break;
		case 'Supprimer le site':
			$id	= intval(htmlspecialchars($_POST['idconf']));
			$site = $this->em->getRepository('IpcProgBundle:Site')->find($id);
			$this->em->remove($site);
			try {
				$this->em->flush();
			} catch(\Exception $e) {
				$this->get("session")->getFlashBag()->add('info', 'Suppression du site non autorisée');
			}
			break;
		case 'Supprimer la localisation':
			$id = intval(htmlspecialchars($_POST['idconfloc']));
			$localisation = $this->em->getRepository('IpcProgBundle:Localisation')->find($id);
			$this->em->remove($localisation);
			try {
				$this->em->flush();
			} catch(\Exception $e) {
				$this->get("session")->getFlashBag()->add('info', 'Suppression de la localisation non autorisée');
			}
			break;
		case 'Modifsitec':
			// Modification du site courant 
			$id = intval(htmlspecialchars($_POST['idconf']));
			$site->setId($id);
			$siteCourant = true;
			// Le précédent Site courant passe à false et sa date de fin d'exploitation est mise à jour
			$id_site = $site->SqlGetIdCourant($dbh);
			if ($id_site) {
				$site->SqlUncheck($dbh, $id_site, $site->getDebutExploitationStr());
			}
			$site->SqlActive($dbh);
			// Modification du titre indiquant le site courant
			$site = $this->em->getRepository('IpcProgBundle:Site')->find($id);
			$new_session_pageTitle['title']	= $site->getAffaire().' : '.$site->getintitule();
			$this->session->set('pageTitle', $new_session_pageTitle);
   			$this->service_configuration->setInfoLimitePeriode();
			$this->session->remove('session_date');
			$this->session->remove('tabModules');
			$this->session->remove('liste_req');
			$this->session->remove('liste_req_pour_listing');
			$this->session->remove('liste_req_pour_graphique');
			// Modification des paramètres de configurations afin d'indiquer l'affaire du site courant dans les mails
			break;
		}
	}
	$dbh = $connexion->disconnect();
	if ($returnMenu == true) {
		$response = new Response($this->renderView('IpcConfigurationBundle:Configuration:configuration.html.twig', array(
			'sessionCourante' => $this->session->getSessionName(),
        	'tabSessions' => $this->session->getTabSessions()
		)));
	} else {
		$liste_sites = $this->em->getRepository('IpcProgBundle:Site')->findAll();
		$response = new Response($this->renderView('IpcConfigurationBundle:Configuration:creationSite.html.twig', array(
			'liste_sites'=> $liste_sites,
			'form' => $form->createView(),
			'form_loc' => $form_localisation->createView(),
			'hasError' => $request->getMethod() == 'POST' && !$form->isValid(),
			'sessionCourante' => $this->session->getSessionName(),
        	'tabSessions' => $this->session->getTabSessions()
		)));
	}
	$response->setPublic();
	$response->setETag(md5($response->getContent()));
	return $response;
}


// Détermine quels sont les droits d'accès fournis au client
public function autorisationClientAction() {
	$this->initialisation();
	// Récupération de la liste des genres présents en base
	$connexion = $this->get('ipc_prog.connectbd');
	$dbh = $connexion->getDbh();
	$this->em = $this->em;
	// Récupération de la liste des genres présents en base de données
	if (count($this->session->get('tabgenres')) == 0) {
		$tmp_genre = new Genre();
		$liste_genres = $tmp_genre->SqlGetAllGenre($dbh);
		$this->session->set('tabgenres', $liste_genres);
	} else{ 
		$liste_genres = $this->session->get('tabgenres');
	}
	$requete = $this->get('request');
	if ($requete->getMethod() == 'POST') {
		isset($_POST['listing']) ? $listing	= $_POST['listing'] : $listing = null;
		isset($_POST['graphique']) ? $graphique = $_POST['graphique'] : $graphique = null;
		// Si une configuration de la partie listing est demandée : Récupération de la liste des genres autorisés (qui seront affichés dans les listes déroulantes)
		$liste_genres_autorises = '';
		if ($listing != null) {
			// Pour chaque intitulé de genre présent en base : Si son intitulé est coché dans la page autorisationClient.html.twig, le genre est autorisé pour les listing
			foreach ($liste_genres as $genres) {
				$intituleGenre = $genres['intitule_genre'];
				// Remplacement des espaces par des undescores : ex Valeur analogique avec Valeur_analogique
				$pattern = '/\s/';
				$replacement = '_';
				$intituleGenre = preg_replace($pattern, $replacement, $intituleGenre);
				if (isset($_POST[$intituleGenre])) { $liste_genres_autorises .= $_POST[$intituleGenre].','; }
			}
			// Supression de la , en fin de liste si celle-ci n'est pas vide
			if (strlen($liste_genres_autorises) != 0) {
				$liste_genres_autorises = substr($liste_genres_autorises, 0, -1);
			}
			// Définition de la valeur du paramètre 'autorisation_genres_listing'
			// Si le paramètre existe : Update Sinon Création
			$configuration = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('autorisation_genres_listing');
			if (! $configuration) {
				// Définition de la nouvelle configuration
				$configuration = new configuration();
				$configuration->setParametre('autorisation_genres_listing');
				$configuration->setDesignation("Liste des genres autorisés au compte client pour l'affichage des listing");
				// Définition de la nouvelle configuration
				$configuration->setValeur($liste_genres_autorises);
				$configuration->SqlInsert($dbh);
			} else {
				$configuration->setDesignation("Liste des genres autorisés au compte client pour l'affichage des listing");
				// Définition de la nouvelle configuration
				$configuration->setValeur($liste_genres_autorises);
				$configuration->SqlUpdate($dbh);
			}
			$configuration = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('autorisation_impression_listing');
			if (! $configuration) {
				$configuration = new configuration();
				$configuration->setParametre('autorisation_impression_listing');
				$configuration->setDesignation("Définit le droit d'impression au compte client des listing");
				if (isset($_POST['impression_listing'])) {
					$configuration->setValeur(1);
				} else{ 
					$configuration->setValeur(0);
				}
				$configuration->SqlInsert($dbh);
			} else { 
				$configuration->setDesignation("Définit le droit d'impression au compte client des listing");
				if (isset($_POST['impression_listing'])) {
					$configuration->setValeur(1);
				} else {
					$configuration->setValeur(0);
				}
				$configuration->SqlUpdate($dbh);
			}
		}
		// Si une configuration de la partie graphique est demandée : Selection des choix des genres qui seront affichés lors de la selection de la liste déroulante
		$liste_genres_autorises = '';
		if ($graphique != null) {
			foreach ($liste_genres as $genres) {
				$intituleGenre = "graph_".$genres['intitule_genre'];
				// Remplacement des espaces par des undescores : ex Valeur analogique avec Valeur_analogique
				$pattern = '/\s/';
				$replacement = '_';
				$intituleGenre = preg_replace($pattern, $replacement, $intituleGenre);
				if (isset($_POST[$intituleGenre])) { $liste_genres_autorises .= $_POST[$intituleGenre].','; }
			}
			// Suppression des la , en fin de liste si celle-ci n'est pas vide
			if (strlen($liste_genres_autorises) != 0) {
				$liste_genres_autorises = substr($liste_genres_autorises, 0, -1);
			}
			// Définition de la valeur du paramètre 'autorisation_genres_graphique'
			// Si le paramètre existe : Update Sinon Création
			$configuration = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('autorisation_genres_graphique');
			if (! $configuration) {
				// Définition de la nouvelle configuration
				$configuration = new configuration();
				$configuration->setParametre('autorisation_genres_graphique');
				$configuration->setDesignation("Liste des genres autorisés au compte client pour l'affichage des graphiques");
				$configuration->setValeur($liste_genres_autorises);
				$configuration->SqlInsert($dbh);
			} else {
				$configuration->setDesignation("Liste des genres autorisés au compte client pour l'affichage des graphiques");
				$configuration->setValeur($liste_genres_autorises);
				$configuration->SqlUpdate($dbh);
			}
			$configuration = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('autorisation_impression_graphique');
			if (! $configuration) {
				$configuration = new configuration();
				$configuration->setParametre('autorisation_impression_graphique');
				$configuration->setDesignation("Définit le droit d'impression au compte client des graphiques");
				if (isset($_POST['impression_graphique'])) {
					$configuration->setValeur(1);
				} else { 
					$configuration->setValeur(0);
				}
				$configuration->SqlInsert($dbh);
			} else {
				$configuration->setDesignation("Définit le droit d'impression au compte client des graphiques");
				if (isset($_POST['impression_graphique'])) {
					$configuration->setValeur(1);
				} else { 
					$configuration->setValeur(0);
				}
				$configuration->SqlUpdate($dbh);
			}
		}
	}
	// Récupération des genres autorisés pour la partie Listing
	$liste_genres_listing_autorises = null;
	$configuration_listing = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('autorisation_genres_listing');
	if ($configuration_listing) {
		$liste_genres_listing_autorises = explode(',', $configuration_listing->getValeur());
	}
	// Récupération des genres autorisés pour la partie Graphique
	$liste_genres_graphique_autorises = null;
	$configuration_graphique = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('autorisation_genres_graphique');
	if ($configuration_graphique) {
		$liste_genres_graphique_autorises = explode(',', $configuration_graphique->getValeur());
	}
	// Récupération de droits d'impression
	$impression_listing = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('autorisation_impression_listing')->getValeur();
	$impression_graphique = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('autorisation_impression_graphique')->getValeur();
	$dbh = $connexion->disconnect();
	$response = new Response($this->renderView('IpcConfigurationBundle:Configuration:autorisationClient.html.twig', array(
		'liste_genres' => $liste_genres,
		'liste_genres_listing_autorises' => $liste_genres_listing_autorises,
		'liste_genres_graphique_autorises' => $liste_genres_graphique_autorises,
		'impression_listing' => $impression_listing,
		'impression_graphique' => $impression_graphique,
		'sessionCourante' => $this->session->getSessionName(),
        'tabSessions' => $this->session->getTabSessions()
	)));
	$response->setPublic();
	$response->setETag(md5($response->getContent()));
	return $response;
}

//      Mise en place des variables de session datedebut et datefin : FONCTION APPELLEE PAR LE SCRIPT AJAX : getenv("DOCUMENT_ROOT")/app/Resources/views/Prog/defineDates.js
public function defineDateAction() {
	$this->initialisation();
	$session_date['datedebut'] = $_POST['datedebut'];
	$session_date['datefin'] = $_POST['datefin'];
	$session_limite_periode	= $this->session->get('infoLimitePeriode');
	// Récupération de la variable de dernière mise en service
	$connexion = $this->get('ipc_prog.connectbd');
	$dbh = $connexion->getDbh();
	// Si l'utilisateur est un client la date minimale de recherche autorisée correspond à celle indiquée par la variable 'autorisation_dmes'
	// Si l'utilisateur est un technicien, la date minimale de recherche autorisée correspond à celle indiquée par la variable 'date_dmes'
	// Si l'utilisateur est un administrateur, aucune limitation de la date
	$message_error = null;
	// Recherche de la date minimum autorisée pour les recherches
	$limitFirstDate = null;
	$service_security = $this->get('security.context');
	$configuration = new Configuration();
	if ($service_security->isGranted('ROLE_SUPERVISEUR')) {
		$limitFirstDate = $configuration->SqlGetParam($dbh, 'date_de_mise_en_service');
		if (! $limitFirstDate) {
			$message_error = "Date de début de période autorisée non trouvée. Veuillez renseigner la variable 'date_de_mise_en_service' svp";
		}
	} else if ($service_security->isGranted('ROLE_TECHNICIEN')) {
		$limitFirstDate = $configuration->SqlGetParam($dbh, 'date_dmes');
		if (! $limitFirstDate) {
			$message_error = "Date de début de période autorisée non trouvée. Veuillez renseigner la variable 'date_dmes' svp";
		}
	} else if ($service_security->isGranted('ROLE_USER')) {
		$limitFirstDate = $configuration->SqlGetParam($dbh, 'autorisation_dmes');
		if (! $limitFirstDate) {
			$message_error = "Date de début de période autorisée non trouvée. Veuillez renseigner la variable 'autorisation_dmes' svp";
		}
	}
	// Si la variable n'est pas définie le retour sera un message indiquant qu'il faut créer la variable
	if ($message_error) {
		echo $message_error;
	} else {
		// Si la variable est incorrectement formatée retour d'un message indiquant le format attendu
		$pattern = '/^\d\d-\d\d-\d\d\d\d$/';
		if (! preg_match($pattern, $limitFirstDate)) {
			echo "Date de début de période autorisée [ $limitFirstDate ] incorrectement formatée : Format attendu DD-MM-YYYY";
		} else {
			// Si la date de début est < à la date de dernière mise en service, retour d'un message indiquant de modifier la période de recherche
			if (strtotime($session_date['datedebut']) < strtotime($limitFirstDate)) {
				echo "La date de début ne peut être antérieur au $limitFirstDate (date de mise en service)";
			} else {
				// Si la date de début est > à la date de fin, retour d'un message indiquant de modifier la période de recherche
				if (strtotime($session_date['datedebut']) >= strtotime($session_date['datefin'])) {
					echo "! La date de début est supérieur à la date de fin !";
				} else {
					// Récupération de l'ecart maximum autorisé pour la période de recherche
					// Pour le client le paramètre est : autorisation_ecart_max, pour les techniciens le paramètre est ecart_max
					// La recherche ne peut exceder une année : 365 jours : Définie par le paramètre de configuration ecart_max
					$max_day = null;
					if ($this->get('security.context')->isGranted('ROLE_SUPERVISEUR')) {
						$max_day = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('admin_ecart_max');
						if (! $max_day) {
							$message_error = "Ecart maximum autorisé pour le compte admin non trouvé. - Veuillez renseigner la variable admin_ecart_max svp";
						}
					} else if ($this->get('security.context')->isGranted('ROLE_TECHNICIEN')) {
						$max_day = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('ecart_max');
						if (! $max_day) {
							$message_error = "Ecart maximum autorisé non trouvé. - Veuillez renseigner la variable ecart_max svp";
						}
					} else if ($this->get('security.context')->isGranted('ROLE_USER')) {
						$max_day = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('autorisation_ecart_max');
						if (! $max_day) {
							$message_error = "Ecart maximum autorisé non trouvé. - Veuillez renseigner la variable autorisation_ecart_max";
						}
					}
					if ($message_error) {
						// Si une erreur est detectée pas de modification de la période
						echo "$message_error";
					} else { 
						// Si la variable est incorrectement formatée retour d'un message indiquant le format attendu
						$pattern = '/^\d+$/';
						$ecart_max = $max_day->getValeur();
						if (! preg_match($pattern, $ecart_max)) {
							echo "Ecart maximum (variable ecart_max) incorrectement formaté - Format attendu : Nombre représentant le nombre de jours de l'ecart";
						} else {
							// Si le nombre de jours séparant la date de début de recherche et la date de fin de recherche est supérieur à l'ecart défini en configuration
							// La période n'est pas autorisée
							$ecart = ceil((strtotime($session_date['datefin']) - strtotime($session_date['datedebut'])) / 86400);
							if ($ecart > $ecart_max) {
								echo "Période de recherche trop grande - Nombre maximum de jours autorisés : $ecart_max";
							} else {
								// Vérification si une des dates de la période ne fait pas partie d'une période d'analyse pour une des localisations du site courant
								// Dans ce cas on affiche le bouton d'information
								$checkDate = $this->checkDeDate($session_date['datedebut'], $session_date['datefin']);
								$numCheckDate = 0;
								if ($checkDate == true) {
									$numCheckDate = 1;
								}
								setlocale (LC_TIME, 'fr_FR.utf8', 'fra');
								$messagePeriode = 'check:'.$numCheckDate."Du ".strftime("%A %d %B %Y - %H:%M:%S", strtotime($session_date['datedebut'])).' au '.strftime("%A %d %B %Y - %H:%M:%S", strtotime($session_date['datefin']));
								$session_date['messagePeriode'] = $messagePeriode;
								$this->session->set('session_date', $session_date);
								echo $messagePeriode;
							}
						}
					}
				}
			}
		}
	}
	$dbh = $connexion->disconnect();
	return new Response();
}


// Fonction appelée en AJAX retournant la date de début ou de fin de période si elle est définie. Null sinon
public function ajaxGetDateAction() {
	$this->initialisation();
	$session_date = $this->session->get('session_date');
	if (! empty($session_date)) {
		$typeDate = $_POST['typeDate'];
		$tabDate = array();
		if ($typeDate == 'debut') {
			$tabDate = $this->coupeDate($session_date['datedebut']);
		} else if ($typeDate == 'fin') {
			$tabDate = $this->coupeDate($session_date['datefin']);
		} else {	
			$tabDate['debut'] = $this->coupeDate($session_date['datedebut']);  
			$tabDate['fin'] = $this->coupeDate($session_date['datefin']);
		}
		if (! empty($tabDate)) {
			echo json_encode($tabDate);
		}
	}
	return new Response();
}

// Fonction appelée en ajax : Qui retourne la date de la dernière donnée enregistrée en base dans un format texte
public function ajaxGetLastDataAction() {
    $service_config = $this->container->get('ipc_prog.configuration');
    $message_last_data = $service_config->getLastDataTime();
	echo $message_last_data;
    return new Response();
}

//	Fonction qui recoit une date (jj-mm-aaaa) et retourne un tableau contenant l'année et le mois
function coupeDate($date) {
	$tabRetourDate = array();
	$pattern = '/^(\d+)-(\d+)-(\d+) (\d+):(\d+)/';
	if (preg_match($pattern, $date, $tabDate)) {
		$tabRetourDate['aaaa'] = $tabDate[3];
		$tabRetourDate['mm'] = $tabDate[2];
		$tabRetourDate['jj'] = $tabDate[1];
		$tabRetourDate['hh'] = $tabDate[4];
		$tabRetourDate['ii'] = $tabDate[5];
	} 
	return($tabRetourDate);
}

// Fonction qui supprime les 0 en début de texte
public function supprimeZero($nombre) {
	$this->constructeur();
	$pattern = '/^0.+/';
	while(preg_match($pattern, $nombre)) {
		$nombre = substr($nombre,1);
	}
	return($nombre);
}



// Fonction qui effectue l'exportation de la table d'échange
public function exportationTableAction() {
	$this->constructeur();
	$requete = $this->get('request');
	// Si la requête provient du retour du formulaire : Exportation de la table d'échange - De la localisation précisée - De toutes la table d'échange de la base de donnée
	if ($requete->getMethod() == 'POST') {
		// Récupération des id des localisations dont la table d'échange est demandée
		$service_importipc = $this->container->get('ipc_prog.importipc');
		$idLocalisation = $_POST['radioIdLoc'];
		// Récupération de la désignation de la localisation si un identifiant de localisation est récupéré 
		$designationLocalisation = null;
		$numeroLocalisation = null;
		if ($idLocalisation != 'allLoc') {
			$numeroLocalisation	= $this->em->getRepository('IpcProgBundle:Localisation')->find($idLocalisation)->getNumeroLocalisation();
		}
		$fichierATelecharger = $service_importipc->exportationTableEchange($idLocalisation, $numeroLocalisation);
		return $fichierATelecharger;
	} 
	// Création des variables : $this->pageTitle et $this->pageActive
	$this->initialisation();
	// Récupération de la liste des localisations sous la forme d'un tableau : ID - numéro - adresseIp
	$liste_localisations = null;
	// Récupération de la liste des sites et de leurs localisations associées
	$objSites = $this->em->getRepository('IpcProgBundle:Site')->findAll();
	$response = new Response($this->renderView('IpcConfigurationBundle:Configuration:export_table_echange_ipc.html.twig', array(
		'liste_sites' => $objSites,
		'sessionCourante' => $this->session->getSessionName(),
        'tabSessions' => $this->session->getTabSessions()
	)));
	$response->setPublic();
	$response->setETag(md5($response->getContent()));
	return $response;
}

	
// Fonction qui définie la variable de session pageActive en fonction de la page selectionnée (listing, graphique, configuration etc.)
public function definePageActiveAction() {
	$this->initialisation();
	$nom_page = $_POST['pageActive'];
	$this->session->set('page_active', $nom_page);
	echo 'Nouvelle page : '.$nom_page;
	return new Response();
}

// Fonction qui retourne une nouvelle liste d'options à placer dans une balise SELECT (Pour les genres, les modules , les messages des modules)
// Appelée depuis les scripts AJAX
public function selectionAction($type, $withAll) {
	$this->constructeur();
	$intitule_module = $_POST['modules'];
	$id_genre = $_POST['genres'];
	$id_localisation = $_POST['localisations'];
	if ($type == 'module') {
		$this->newSelection($id_localisation, $id_genre, $intitule_module, 'module', $withAll);
	}
	if ($type == 'genre') {
		$this->newSelection($id_localisation, $id_genre, $intitule_module, 'genre', $withAll);
		echo "ListeSuivante";
		$this->newSelection($id_localisation, $id_genre, $intitule_module, 'module', $withAll);
	}
	return new Response();
}

protected function newSelection($id_localisation, $id_genre, $intitule_module, $type, $withAll) {
	$this->initialisation();
	$this->tabmodulesC = $this->session->get('tabModules');
	// Si la demande d'affichage concerne la liste des message et si la paramètre withAll est définit à False on n'affichage pas le champs All
	// Si le type est module et que withAll = false, pas d'affichage du champs 'tous'
	// Si le type est module et que withAll = true, affichage du champs 'tous' sur Message
	// Si le type est genre et que withAll = true, affichage du champs 'tous' sur Module
	if ( ! (($type == 'module') && ($withAll == false))) {
		if ($type == 'module') {
			echo "<option value='all'>Tous les messages</option>";
		}
		if ($type == 'genre') {
			echo "<option value='all'>Tous les modules</option>";
		}
	}
	$tabIntitule = array();
	$tabMessage = array();
	$correspondance_message_code = $this->session->get('correspondance_Message_Code');
	foreach ($this->tabmodulesC as $key => $module) {
		if ($id_genre != 'all') {
			if ($id_genre == $module['genre']) {
				if (in_array($module['intitule'],$tabIntitule) == false) {
					if ((($id_localisation != 'all') && (in_array($id_localisation,$module['localisation']))) || ($id_localisation == 'all')) {
						// TAB INTITULE : Genre Correct
						array_push($tabIntitule,$module['intitule']);
					}
				}
				if ($intitule_module != 'all') {
					if ($intitule_module == $module['intitule']) {
						if ((($id_localisation != 'all') && (in_array($id_localisation,$module['localisation']))) || ($id_localisation == 'all')) {
							// TAB MESSAGE : Module Correct + Genre Correct
							$tabMessage[$key]=$correspondance_message_code[$key]." - ".$this->suppressionDesCaracteres($module['message']);
						}
					}
				} else { 
					if ((($id_localisation != 'all') && (in_array($id_localisation,$module['localisation']))) || ($id_localisation == 'all')) {
						// TAB MESSAGE : Tous Modules + Genre Correct
						$tabMessage[$key]=$correspondance_message_code[$key]." - ".$this->suppressionDesCaracteres($module['message']);
					}
				}
			}
			// On ajoute les modules selectionnés qui n'ont pas de messages avec le genre désiré -> Permet de garder la selection d'origine du select
			if ($intitule_module != 'all') {
				if (in_array($intitule_module,$tabIntitule) == false) {
					if ((($id_localisation != 'all') && (in_array($id_localisation,$module['localisation']))) || ($id_localisation == 'all')) {
						// TAB INTITULE : Module Selectionné
						array_push($tabIntitule,$intitule_module); 
					}
				}
			}
		} else {
			if (in_array($module['intitule'],$tabIntitule) == false) {
				if ((($id_localisation != 'all') && (in_array($id_localisation,$module['localisation']))) || ($id_localisation == 'all')) {
					// TAB INTITULE : Tous les Genres
					array_push($tabIntitule,$module['intitule']); 
				}
			}
			if ($intitule_module != 'all') {
				if ($intitule_module == $module['intitule']) {
					if ((($id_localisation != 'all') && (in_array($id_localisation,$module['localisation']))) || ($id_localisation == 'all')) {
						// TAB MESSAGE : Module Correct + Tous les Genres
						$tabMessage[$key]=$correspondance_message_code[$key]." - ".$this->suppressionDesCaracteres($module['message']);
					}
				}
			} else { 
				if ((($id_localisation != 'all') && (in_array($id_localisation,$module['localisation']))) || ($id_localisation == 'all')) {
					// TAB MESSAGE : Tous Modules + Tous Genres
					$tabMessage[$key]=$correspondance_message_code[$key]." - ".$this->suppressionDesCaracteres($module['message']);	
				}
			}
		}
	}
	if ($type == 'genre') {
		sort($tabIntitule);
		foreach ($tabIntitule as $intitule) { 
			echo '<option value="'.$intitule.'">'.$intitule.'</option>'; 
		}
	} else {
		asort($tabMessage);
		foreach ($tabMessage as $key=>$message) { 
			echo '<option value="'.$key.'">'.$message.'</option>'; 
		}
		if ($withAll == false) {
			// Partie Graphique
			$this->session->set('liste_messages_modules_graphique',$tabMessage);
			$this->session->set('liste_messages_modules_etat', $tabMessage);
		} else {
			// Partie Listing
			$this->session->set('liste_messages_modules_listing',$tabMessage);
		}
	}
	return;
}


// Fonction appelée par AJAX pour retourner une entité
public function getEntityAction() {
	$this->constructeur();
	$entityName = $_POST['entityName'];
	$entityId = $_POST['entityId'];
	$tabRetour = array();
	switch ($entityName) {
	case 'etat':
		$entite = $this->em->getRepository('IpcProgBundle:Etat')->find($entityId);
		$tabRetour['intitule'] = $entite->getIntitule();
		$tabRetour['periodique'] = $entite->getPeriodique();
		$tabRetour['periode'] = $entite->getPeriode();
		$tabRetour['nb_periodique'] = $entite->getNbPeriodique();
		$tabRetour['periode'] = $entite->getPeriode();
		$tabRetour['nb_periode'] = $entite->getNbPeriode();
		break;
	case 'calcul':
		$entite = $this->em->getRepository('IpcProgBundle:Calcul')->find($entityId);
		$tabRetour['intitule'] = $entite->getIntitule();
		$tabRetour['type_affichage'] = $entite->getTypeAffichage();
		$tabRetour['type_calcul'] = $entite->getTypeCalcul();
		$tabRetour['id_localisation1'] = $entite->getIdLocalisation1();
		$tabRetour['id_localisation2'] = $entite->getIdLocalisation2();
		$tabRetour['id_message1'] = $entite->getIdMessage1();
		$tabRetour['id_message2'] = $entite->getIdMessage2();
		$tabRetour['module1_condition']	= $entite->getModule1Condition();
		$tabRetour['module2_condition'] = $entite->getModule2Condition();
		$tabRetour['module1_valeur1'] = $entite->getModule1Valeur1();
		$tabRetour['module1_valeur2'] = $entite->getModule1Valeur2();
		$tabRetour['module2_valeur1'] = $entite->getModule2Valeur1();
		$tabRetour['module2_valeur2'] = $entite->getModule2Valeur2();
		$tabRetour['supplements'] = $entite->getSupplements();
		break;
	}
	echo json_encode($tabRetour);
	return new Response();
}

public function infosLocalisationAction() {
	$this->initialisation();
	// Récupération du site courant
	// Récupération des informations concernants les localisations du site courant
	$site = new Site();
	$connexion = $this->get('ipc_prog.connectbd');
	$dbh = $connexion->getDbh();
	$id_site_courant = $site->SqlGetIdCourant($dbh);
	$site_courant = $this->em->getRepository('IpcProgBundle:Site')->find($id_site_courant);
	$tab_infos = array();
	$tab_localisation_id = array();
	foreach ($site_courant->getLocalisations() as $localisation) {
		$liste_infoLoc = $this->em->getRepository('IpcProgBundle:InfosLocalisation')->findBy(array('localisation'=>$localisation),array('localisation'=>'ASC'));
		foreach ($liste_infoLoc as $infos) {
			array_push($tab_infos,$infos);
		}
	}
	$liste_mode = $this->em->getRepository('IpcProgBundle:Mode')->findAll();
	return $this->render('IpcConfigurationBundle:Configuration:modificationLocalisation.html.twig',array(
		'siteCourant' => $site_courant,
		'listeMode' => $liste_mode,
		'listeLocalisations' => $site_courant->getLocalisations(),
		'tabInfos' => $tab_infos,
		'sessionCourante' => $this->session->getSessionName(),
        'tabSessions' => $this->session->getTabSessions()
	));
	return new Response();
}

// Fonction AJAX : Appelée lors d'un clic pour modification de période d'analyse (page modificationLocalisation.html.twig)
public function majPeriodeAnalyseAction() {
	$this->initialisation();
	// Nouvelle ligne d'info
	$infoId = $_POST['infoModeId'];
	// Nouvelle Localisation
	$infoIdLoc = $_POST['infoLocId'];
	// Nouveau Mode Programme
	$infoIdProg	= $_POST['infoProg'];
	$serviceFillNumber 	= $this->container->get('ipc_prog.fillnumbers');
	// Entité infoMode
	$infoMode = $this->em->getRepository('IpcProgBundle:InfosLocalisation')->find($infoId);
	// Modification des liens localisations / modules
	if (! empty($infoMode)) {
		// Recherche de la localisation impactée par le changement de programme
		$localisation = $this->em->getRepository('IpcProgBundle:Localisation')->find($infoIdLoc);
		// Recherche du nouveau progamme installé / mise en place sur la localisation : si aucun programme n'était désigné, recherche des modules associés à un mode == null
		$mode = $this->em->getRepository('IpcProgBundle:Mode')->find($infoIdProg);
		// Modification des liens localisations / modules
		$service_import_ipc = $this->container->get('ipc_prog.importipc');
		if (empty($mode)) {
			$service_import_ipc->changeProgramme($localisation);
		} else {
			$service_import_ipc->changeProgramme($localisation, $mode);
		}
		$modeLocalisationImpact = $this->em->getRepository('IpcProgBundle:InfosLocalisation')->findByLocalisation($localisation);
		foreach ($modeLocalisationImpact as $modeLocalisation) {
			$modeLocalisation->setPeriodeCourante(false);
		}
		$infoMode->setPeriodeCourante(true);
		$this->em->flush();
	}
	$this->service_configuration->setInfoLimitePeriode();
	// Réinitialisation de la variable de session
	$this->session->remove('tabModules');
	return new Response();
}

// Fonction appelée en AJAX pour obtenir la liste des périodes d'analyse pour les localisations du site courant
public function getInfosPeriodeAction() {
	$this->constructeur();
	// Récupération du tableau de limitation des requêtes en fonction des périodes d'analyse
	// Récupération du site courant
	$connexion = $this->get('ipc_prog.connectbd');
	$dbh = $connexion->getDbh();
	$site = new Site();
	$idSiteCourant = $site->SqlGetIdCourant($dbh);
	$site = $this->em->getRepository('IpcProgBundle:Site')->find($idSiteCourant);
	// Récupération des localisations du site courant
	$liste_localisation = $site->getLocalisations();
	// Pour chaque localisation : Récupération de la période d'analyse et définition de la variable de session
	$tabPeriodeAnalyse = array();
	foreach ($liste_localisation as $localisation) {
		$tabPeriodeAnalyse[$localisation->getId()]['numero'] = $localisation->getNumeroLocalisation();
		$tabPeriodeAnalyse[$localisation->getId()]['designation'] = $localisation->getDesignation();
		$periodeInfo = $this->em->getRepository('IpcProgBundle:infosLocalisation')->findBy(array('localisation' => $localisation, 'periodeCourante' => 1));
		if (isset($periodeInfo[0])) {
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
	echo json_encode($tabPeriodeAnalyse);
	return new Response();
}

public function checkDeDate($datedeb, $datefin) {
	$this->constructeur();
	$connexion = $this->get('ipc_prog.connectbd');
	$dbh = $connexion->getDbh();
	$site = new Site();
	$idSiteCourant = $site->SqlGetIdCourant($dbh);
	$site = $this->em->getRepository('IpcProgBundle:Site')->find($idSiteCourant);
	$liste_localisation = $site->getLocalisations();
	foreach($liste_localisation as $localisation) {
		$periodeInfo = $this->em->getRepository('IpcProgBundle:infosLocalisation')->findBy(array('localisation' => $localisation, 'periodeCourante' => 1));
		if (isset($periodeInfo[0]) ) {
			if ($periodeInfo[0]->getHorodatageDeb() != null) {
				if (strtotime($datedeb) < strtotime($periodeInfo[0]->getHorodatageDeb()->format('Y/m/d  H:i:s'))) {
					return(false);
				}
			}
			if ($periodeInfo[0]->getHorodatageFin() != null) {
				if (strtotime($datefin) > strtotime($periodeInfo[0]->getHorodatageFin()->format('Y/m/d H:i:s'))) {
					return(false);
				}
			}
		} else {
			// Ce cas se présente si aucune table d'échange n'a été installée pour la localisation en cours d'analyse
			return(false);
		}
	}
	return(true);
}

// AJAX : Fonction permettant de retourner les messages des modules contenant la chaine de caractère passée en argument
public function getMessagesAction() {
	$this->initialisation();
	$tabToSup = array();
	// Suppression des espaces de début et de fin de chaine + sécurisation de la chaine passée en paramètre car provenant d'une entrée clavier
	$chaine = strtoupper(trim(htmlspecialchars($_POST['chaine'])));
	// Création de l'expression régulière : Remplacement des espaces multiples par des espaces uniques et recherche de chacun des mots de la chaine
	$patternReplace = '/\s+/';
	$replacement = '.+?';
	$chaine = preg_replace($patternReplace, $replacement, $chaine);	
	$url = $_POST['url'];
	if ($url == 'ipc_listing') {
		$liste_message = $this->session->get('liste_messages_modules_listing');
	} elseif ($url == 'ipc_graphiques') {
		$liste_message = $this->session->get('liste_messages_modules_graphique');
	} elseif ($url == 'ipc_etat') {
		$liste_message = $this->session->get('liste_messages_modules_etat');
	}
	$pattern = "/$chaine/";
	// Parcours de la liste des messages de modules pour supprimer du tableau ceux ne contenant pas les caractères demandés
	foreach ($liste_message as $key => $message) {
		// Suppression des lignes ne contenant pas les caractères demandés
		if (! preg_match($pattern, strtoupper($message))) {
			$tabToSup[] = $key;
			unset($liste_message[$key]);
		}
	}
	foreach ($tabToSup as $key => $value) {
		unset($liste_message[$value]);
	}
	echo json_encode($liste_message);
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

// Lecture d'un mot par application modbus
public function readModbusAction() {
	$this->constructeur();
	$service_modbus = $this->get('ipc_prog.modbus');
	$service_modbus->readModbus('downloadFtp');
	return new Response();
}
    
// Ecriture d'un mot par application modbus
public function writeModbus($mot) {
	$this->constructeur();
	$service_modbus = $this->get('ipc_prog.modbus');
	switch ($mot) {
		case 'closeFtp':
			$retour = $service_modbus->writeModbus('activate', 'downloadFtp', 'all');
			break;
		}
	return(0);
}

public function modbusClotureFtpAction() {
	$this->constructeur();
	$this->writeModbus('closeFtp');
	return new Response();
}


public function accueilInterventionAction() {
	$this->initialisation();
	$response = new Response($this->renderView('IpcConfigurationBundle:Configuration:accueilIntervention.html.twig', array(
		'sessionCourante' => $this->session->getSessionName(),
        'tabSessions' => $this->session->getTabSessions()
	)));
	$response->setPublic();
	$response->setETag(md5($response->getContent()));
	return $response;
}

public function addTypeGenerateurAction() {
	$this->initialisation();
	$requete = $this->get('request');
	$typeGenerateur = new TypeGenerateur();
	$formTypeGenerateur	= $this->createForm(new TypeGenerateurType, $typeGenerateur);
	if ($requete->getMethod() == 'POST') {
		$formTypeGenerateur->handleRequest($requete);
		if ($formTypeGenerateur->isValid()) {
			// Si le mode d'exploitation existe déjà : Mise à jour
			$oldTypeGenerateur = $this->em->getRepository('IpcProgBundle:TypeGenerateur')->findOneByMode($typeGenerateur->getMode());
			if ($oldTypeGenerateur != null) {
				$oldTypeGenerateur->setDescription($typeGenerateur->getDescription());
				foreach ($oldTypeGenerateur->getModulesEnteteLive() as $moduleEntete) {
					$oldTypeGenerateur->removeModulesEnteteLive($moduleEntete);
				}
				foreach ($typeGenerateur->getModulesEnteteLive() as $moduleEntete) {
					$oldTypeGenerateur->addModulesEnteteLive($moduleEntete);
				}
			} else { 
				$this->em->persist($typeGenerateur);
			}
			$this->em->flush();
		}
	}
	$entitiesTypeGenerateur = $this->em->getRepository('IpcProgBundle:TypeGenerateur')->findAll();
	$response = new Response($this->renderView('IpcConfigurationBundle:Configuration:addTypeGenerateur.html.twig', array(
		'form' => $formTypeGenerateur->createView(),
		'entitiesTypeGenerateur' => $entitiesTypeGenerateur,
		'sessionCourante' => $this->session->getSessionName(),
        'tabSessions' => $this->session->getTabSessions()
	)));
	$response->setPublic();
	$response->setETag(md5($response->getContent()));
	return $response;
}


// Fonction qui permet de modifier la couleur des genres.
public function gestionGenresAction() {
	$this->initialisation();
	$requete = $this->get('request');
	// Si le formulaire des différentes couleurs de genre est renvoyé, Mise à jour de la table de genres
	if (isset($_GET['formGet'])) {
		// Récupération de tous les genres
		$pattern_genre = '/^valueGenre(.*)$/';
		foreach ($_GET as $idGenre => $couleurGenre) {
			if (preg_match($pattern_genre, $idGenre, $tabIdGenre)) {
				$entity_genre = $this->em->getRepository('IpcProgBundle:Genre')->find($tabIdGenre[1]);
				$entity_genre->setCouleur($couleurGenre);
			}
		}	
		$this->em->flush();
		$this->get('session')->getFlashBag()->add('info', 'Nouvelles couleurs enregistrées');
		$response = new Response($this->renderView('IpcConfigurationBundle:Configuration:configuration.html.twig', array(
			'sessionCourante' => $this->session->getSessionName(),
        	'tabSessions' => $this->session->getTabSessions()
		)));
	}else{
		$entities_genre = $this->em->getRepository('IpcProgBundle:Genre')->findAll();
		$response = new Response($this->renderView('IpcConfigurationBundle:Configuration:gestionCouleurGenres.html.twig', array(
			'entities_genre' => $entities_genre,
			'sessionCourante' => $this->session->getSessionName(),
        	'tabSessions' => $this->session->getTabSessions()
		)));
	}
	$response->setPublic();
	$response->setETag(md5($response->getContent()));
	return $response;
}

private function getIdSiteCourant($dbh) {
    $site = new Site();
    $idSiteCourant = $site->SqlGetIdCourant($dbh);
    return ($idSiteCourant);
}


// Fonction qui supprime une requête personnelle
// On vérifie que l'utilisateur peut supprimer la requete : Il en est le createur ou il est administrateur
public function deleteRequestPersoAction() {
    $this->constructeur();
    $this->initialisation();
    // Récupération de la page d'origine de la demande
    $page = $_GET['page'];
    // récupération de la requête à supprimer
    $id_requete = $_GET['id_requete'];
    	if ($id_requete != 0) {
       		$requete = $this->em->getRepository('IpcConfigurationBundle:Requete')->find($id_requete);
           	if ( ($requete->getCreateur() == $this->userLabel) || ($this->get('security.context')->isGranted('ROLE_ADMIN')) ) {
               	// Suppression de la requête personnelle
               	$this->em->remove($requete);
               	$this->em->flush();
               	// Récupération de la page d'origine  pour
               	// Supprimer la variable de session indiquant la requête selectionnée
               	// ET
               	// Retourner sur la bonne page d'accueil (le bon index.html)
               	if ($page == 'listing') {
                   	$this->removeListeReq('listing');
                   	$this->session->set('listing_requete_selected', null);
               	} else if ($page =='graphique') {
                   	$this->removeListeReq('graphique');
                   	$this->session->set('graphique_requete_selected', null);
               	}
           } else {
               $this->getRequest()->getSession()->getFlashBag()->add('info', "Vous n'avez pas les autorisations pour supprimer cette requête");
           }
       }
       if ($page == 'listing') {
               return $this->redirect($this->generateUrl('ipc_accueilListing'));
       } else if ($page =='graphique') {
               return $this->redirect($this->generateUrl('ipc_accueilGraphique'));
       }
}

public function removeListeReq($page) {
    if ($page == 'listing') {
        $this->session->remove('liste_req');
        $this->session->remove('liste_req_pour_listing');
    } else {
        $this->session->remove('liste_req_pour_graphique');
    }
}




}
