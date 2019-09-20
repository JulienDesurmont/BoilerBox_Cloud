<?php
//src/Ipc/RapportsBundle/Controller/RapportsController.php
namespace Ipc\OutilsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerAware;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\FOSUserEvents;

use Ipc\ProgBundle\Entity\Site;
use Ipc\ProgBundle\Entity\Genre;
use Ipc\ProgBundle\Entity\Donnee;
use Ipc\ProgBundle\Entity\DonneeDoublon;
use Ipc\ProgBundle\Entity\Module;
use Ipc\ProgBundle\Entity\Rapport;
use Ipc\ProgBundle\Entity\Fichier;
use Ipc\ProgBundle\Entity\Donneetmp;
use Ipc\ProgBundle\Entity\Localisation;
use Ipc\ProgBundle\Entity\Configuration;
use Ipc\ProgBundle\Entity\FichierRapport;

use Ipc\ConfigurationBundle\Form\Type\RapportType;
use Ipc\ConfigurationBundle\Form\Type\ModifyRapportType;
use Ipc\ConfigurationBundle\Form\Type\FichierRapport2Type;

use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormError;



class OutilsController extends Controller {

private $service_configuration;
private $session;
private $em;
private $fillnumber;
private $tab_modules;
private $liste_localisations;
private $datedebut;
private $datefin;
private $liste_heures;
private $liste_minutes;
private $adresseMot;
private $highPercentLimit = 80;
private $last_loc_graph_id;

// Fonction qui permet de récupérer le service de session pour les sessions multiples
public function constructeur(){
	$this->em = $this->get('ipc_prog.connectbd')->getManager();
    if (empty($this->session)) {
        $service_session = $this->container->get('ipc_prog.session');
        $this->session = $service_session;
    }
}

private function initialisation() {
	$this->constructeur();
    $this->service_configuration = $this->get('ipc_prog.configuration');
    $this->fillnumbers = $this->get('ipc_prog.fillnumbers');
    $this->tab_modules = array();
}


public function indexAction(Request $request) {
    $this->initialisation();
    return  $this->render('IpcOutilsBundle:Outils:index.html.twig', array(
		'sessionCourante' => $this->session->getSessionName(),
        'tabSessions' => $this->session->getTabSessions()
    ));
}


// Fonction qui retourne vers la page de gestion des scripts.
//  Cette page indique la liste des scripts. Ceux qui sont actif et ceux qui ne le sont pas
public function gestionScriptsAction() {
    $this->initialisation();
    // Recherche de l'état des scripts
    $tabRetour = array();
    $tabDesScripts = array();
    $tabDesScripts['swiftmailer'] = array();
    $tabDesScripts['swiftmailer']['description'] = "Envoi des mails";
    $tabDesScripts['swiftmailer']['value'] = false;
    $tabDesScripts['swiftmailer']['scriptAdmin'] = true;
    $tabDesScripts['importBin'] = array();
    $tabDesScripts['importBin']['description'] = "Importation des fichiers en base";
    $tabDesScripts['importBin']['value'] = false;
	$tabDesScripts['importBin']['scriptAdmin'] = true;
    $tabDesScripts['transfertFtp'] = array();
    $tabDesScripts['transfertFtp']['description'] = "Transfert Ftp des fichiers des automates";
    $tabDesScripts['transfertFtp']['value'] = false;
	$tabDesScripts['transfertFtp']['scriptAdmin'] = true;	
    $tabDesScripts['rapportEtat'] = array();
    $tabDesScripts['rapportEtat']['description'] = "Génération des rapports d'état";
    $tabDesScripts['rapportEtat']['value'] = false;
    $tabDesScripts['rapportEtat']['scriptAdmin'] = false;
    $tabDesScripts['rapportSystem'] = array();
    $tabDesScripts['rapportSystem']['description'] = "Génération des rapports système";
    $tabDesScripts['rapportSystem']['value'] = false;
    $tabDesScripts['rapportSystem']['scriptAdmin'] = true;
    $tabDesScripts['rapportSecurite'] = array();
    $tabDesScripts['rapportSecurite']['description'] = "Génération du rapport de sécurité";
    $tabDesScripts['rapportSecurite']['value'] = false;
	$tabDesScripts['rapportSecurite']['scriptAdmin'] = true;
    $tabDesScripts['rapportJournalier'] = array();
    $tabDesScripts['rapportJournalier']['description'] = "Génération du rapport journalier";
    $tabDesScripts['rapportJournalier']['value'] = false;
    $tabDesScripts['rapportJournalier']['scriptAdmin'] = false;
    $tabDesScripts['modbusGet'] = array();
    $tabDesScripts['modbusGet']['description'] = "Récupération des informations modbus";
    $tabDesScripts['modbusGet']['value'] = false;
	$tabDesScripts['modbusGet']['scriptAdmin'] = true;
    // Récupération des informations sur l'execution des scripts de la crontab root
    foreach ($tabDesScripts as $script => $execution) {
        $execCmd = exec("sudo crontab -u root -l | grep -i 'boilerbox\|script' | grep '$script'");
		if ($execCmd === "") {
			echo "<br />Droits d'execution de la commande [sudo crontab -u root -l | grep -i 'boilerbox\|script'  | grep '$script']  insuffisant ou ligne non déclarée";
			return new Response();
		}
        if (substr(trim($execCmd), 0, 1) == '#') {
            $tabDesScripts[$script]['value'] = false;
        } else {
            $tabDesScripts[$script]['value'] = true;
        }
    }
    $response = new Response($this->renderView('IpcOutilsBundle:Outils:gestionScripts.html.twig', array(
        'tabDesScripts' => $tabDesScripts,
		'sessionCourante' => $this->session->getSessionName(),
        'tabSessions' => $this->session->getTabSessions()
    )));
    $response->setPublic();
    $response->setETag(md5($response->getContent()));
    return $response;
}


//  Envoi vers la page permettant d'envoyer un rapport ou d'effectuer le transfert ftp des fichiers
public function gestionRapportAction() {
	$this->constructeur();
    $this->initialisation();
    $service_mail = $this->get('ipc_prog.mailing');
    $requete = $this->get('request');
    $tab_logs = array();
    $texte_logs = '';
    // Récupération du numéro de ligne du fichier de log avant l'action :
    $fichier = new Fichier();
    $dossier_log = $fichier->getLogsDir();
    if ($requete->getMethod() == 'POST') {
        // Récupération du choix de l'action a effectuer
        $choixAction = $_POST['typeAction'];
        $post_date_rapport = $_POST['dateRapport'];
        switch ($choixAction) {
        case 'rapportJournalier':
            $service_creationRapport = $this->get('ipc_prog.rapports');
			// Ajout du 20/04/2017 : On force l'envoi du rapport (même si aucune erreur n'est détectée)
			// Entraine la purge des doublons
			$service_creationRapport->setSendMail(true);
            // Création du rapport
            $texte_logs .= $service_creationRapport->rapportJournalier($post_date_rapport);
            // Envoi du rapport
            //$service_mail->sendAllMails();
            break;
        case 'rapportAnalyse':
            $service_creationRapport = $this->get('ipc_prog.rapports');
            $texte_logs .= $service_creationRapport->rapportSyntheseModule(true);
            $service_mail->sendAllMails();
            break;
        case 'rapportSecurite':
            $service_creationRapport = $this->get('ipc_prog.rapports');
            $texte_logs .= $service_creationRapport->rapportSecurite($post_date_rapport);
            $service_mail->sendAllMails();
            break;
        case 'testMail':
            $service_creationRapport = $this->get('ipc_prog.rapports');
            $texte_logs .= $service_creationRapport->rapportTestMail($post_date_rapport);
            $service_mail->sendAllMails();
            break;
        case 'rapportSystem':
            $service_rapports = $this->get('ipc_prog.rapports');
            $tabParametres = $service_rapports->lectureSystemParametre();
            $texte_logs .= $service_rapports->rapportSystem($tabParametres);
            $service_mail->sendAllMails();
            break;
        case 'rapportSystemPageAffiche':
            $service_rapports = $this->get('ipc_prog.rapports');
            $tabParametres = $service_rapports->lectureSystemParametre();
            $texte_logs .= $service_rapports->rapportSystem($tabParametres);
            $service_mail->sendAllMails();
            $response = new Response($this->renderView('IpcOutilsBundle:Outils:parametreSystem.html.twig', array(
                'tabParametres' => $tabParametres,
                'texte_logs' => $texte_logs,
                'highPercentLimit' => $this->highPercentLimit,
				'sessionCourante' => $this->session->getSessionName(),
        		'tabSessions' => $this->session->getTabSessions()
            )));
            $response->setPublic();
            $response->setETag(md5($response->getContent()));
            return $response;
            break;
        }
    }
    $response = new Response($this->renderView('IpcOutilsBundle:Outils:gestionRapport.html.twig', array(
        'texte_logs' => $texte_logs,
		'sessionCourante' => $this->session->getSessionName(),
        'tabSessions' => $this->session->getTabSessions()
    )));
    $response->setPublic();
    $response->setETag(md5($response->getContent()));
    return $response;
}

// Force la suppression des fichiers ftp vides
public function suppressionFtpVidesAction(){
    $this->constructeur();
    $this->initialisation();
    $requete = $this->get('request');
    $service_transfertFtp = $this->get('ipc_prog.transfertFtp');
    $nb_supp = $service_transfertFtp->forcageSuppressionFtpVides();
    $this->get('session')->getFlashBag()->add('info', "Forcage de suppression FTP effectué: $nb_supp fichier(s) supprimé(s)");
    return $this->redirect($this->generateUrl('ipc_outils_conf'));
}


//  Envoi vers la page permettant d'envoyer un rapport ou d'effectuer le transfert ftp des fichiers
public function transfertFtpAction() {
	$this->constructeur();
    $this->initialisation();
    $service_mail = $this->get('ipc_prog.mailing');
    $requete = $this->get('request');
    $tab_logs = array();
    $texte_logs = '';
    // Récupération du numéro de ligne du fichier de log avant l'action :
    $fichier = new Fichier();
    $dossier_log = $fichier->getLogsDir();
    if ($requete->getMethod() == 'POST') {
        // Récupération du choix de l'action a effectuer
        $fichier_log = $dossier_log.'transfertFtp.log';
        $nblig1 = intval(exec("wc -l $fichier_log"));
        $service_transfertFtp = $this->get('ipc_prog.transfertFtp');
        // Lancement de l'importation Ftp
        $service_transfertFtp->importation();
        $nblig2 = intval(exec("wc -l $fichier_log"));
        $nbligEcrites = $nblig2 - $nblig1;
        $commande = "tail -".$nbligEcrites." $fichier_log";
        $logs = exec("$commande", $tab_logs, $retour);
        foreach ($tab_logs as $log) {
            $texte_logs .= $log."\n";
        }
    }
    $response = new Response($this->renderView('IpcOutilsBundle:Outils:transfertFtp.html.twig', array(
        'texte_logs' => $texte_logs,
		'sessionCourante' => $this->session->getSessionName(),
        'tabSessions' => $this->session->getTabSessions()
    )));
    $response->setPublic();
    $response->setETag(md5($response->getContent()));
    return $response;
}




public function getParametresSystemAction() {
	$this->constructeur();
    $this->initialisation();
    $service_rapports = $this->container->get('ipc_prog.rapports');
    $tabParametres = $service_rapports->lectureSystemParametre();
    $response = new Response($this->renderView('IpcOutilsBundle:Outils:parametreSystem.html.twig', array(
        'tabParametres' => $tabParametres,
        'highPercentLimit' => $this->highPercentLimit,
		'sessionCourante' => $this->session->getSessionName(),
        'tabSessions' => $this->session->getTabSessions()
    )));
    $response->setPublic();
    $response->setETag(md5($response->getContent()));
    return $response;
}

// Fonction qui retourne les informations sur la derniére valeur d'un module avant une date donnée
// Appelée par la fonction 'Recherche de valeurs' de la page des configurations
public function rechercheValeursAction() {
	$this->constructeur();
    $this->initialisation();
    $connexion = $this->get('ipc_prog.connectbd');
    $dbh = $connexion->getDbh();
    $requete = $this->get('request');
    $fillnumbers = $this->get('ipc_prog.fillnumbers');
    // Recherche de la date minimum autorisée pour les recherches
    $limitFirstDate = null;
    $service_security = $this->get('security.context');
    $configuration = new Configuration();
    if ($service_security->isGranted('ROLE_SUPERVISEUR')) {
        $limitFirstDate = $configuration->SqlGetParam($dbh, 'date_de_mise_en_service');
    } else if ($service_security->isGranted('ROLE_TECHNICIEN')) {
        $limitFirstDate = $configuration->SqlGetParam($dbh, 'date_dmes');
    } else if ($service_security->isGranted('ROLE_USER')) {
        $limitFirstDate = $configuration->SqlGetParam($dbh, 'autorisation_dmes');
    }
    // Récupération de la liste des localisations sous la forme d'un tableau : ID - numéro - adresseIp
    $liste_localisations = null;
    if (count($this->session->get('tablocalisations')) == 0) {
        $tmp_site = new Site();
        $tmp_localisation = new Localisation();
        $site_id = $tmp_site->SqlGetIdCourant($dbh);
        $liste_localisations = $this->em->getRepository('IpcProgBundle:Localisation')->SqlGetLocalisation($dbh, $site_id);
        $this->session->set('tablocalisations', $liste_localisations);
    } else {
        $liste_localisations = $this->session->get('tablocalisations');
    }
    // Si aucune localisation déclarée pour le site courant : Retour d'un message
    if ($liste_localisations == null) {
        $this->get('session')->getFlashBag()->add('info', "Aucune Localisation définie pour le site courant");
        $response = new Response($this->renderView('IpcOutilsBundle:Outils:index.html.twig', array(
			'sessionCourante' => $this->session->getSessionName(),
        	'tabSessions' => $this->session->getTabSessions()
        )));
        $response->setPubic();
        $response->setETag(md5($response->getContent()));
        return $response;
    }
    // Récupération de la liste des genres présents en base de données
    $liste_genres = null;
    if (count($this->session->get('tabgenres')) == 0) {
        $tmp_genre = new Genre();
        $liste_genres = $tmp_genre->SqlGetAllGenre($dbh);
        $this->session->set('tabgenres', $liste_genres);
    } else {
        $liste_genres = $this->session->get('tabgenres');
    }
    if (( count($this->session->get('tabModules')) == 0 ) || (count($this->session->get('correspondance_Message_Code')) == 0)) {
        $tmp_module = new Module();
        $tmp_liste_modules = $tmp_module->SqlGetModulesGenreAndUnit($dbh);
        $correspondance_message_code = array();
        foreach ($tmp_liste_modules as $module) {
            foreach ($liste_localisations as $localisation) {
                $tmpNbLien = $tmp_module->sqlGetNbLien($dbh, $module['id'], $localisation['id']);
                if ($tmpNbLien != 0) {
                    if (! array_key_exists($module['id'], $this->tab_modules)) {
                        $correspondance_message_code[$module['id']] = $module['categorie'].$fillnumbers->fillNumber($module['numeroModule'], 2).$fillnumbers->fillNumber($module['numeroMessage'], 2);
                        $this->tab_modules[$module['id']]['intitule'] = $module['intituleModule'];
                        $this->tab_modules[$module['id']]['message'] = $module['message'];
                        $this->tab_modules[$module['id']]['genre'] = $module['idGenre'];
                        $this->tab_modules[$module['id']]['unite'] = $module['unite'];
                        $this->tab_modules[$module['id']]['localisation'][] = $localisation['id'];
                    } else {
                        $this->tab_modules[$module['id']]['localisation'][] = $localisation['id'];
                    }
                }
            }
        }
		//      Définit et récupère des attributs de session
        $this->session->set('tabModules', $this->tab_modules);
        if ($correspondance_message_code == null) {
            $this->get('session')->getFlashBag()->add('info', "Outil : Aucun module associé aux localisations du site courant");
            $response = new Response($this->renderView('IpcOutilsBundle:Outils:index.html.twig', array(
				'sessionCourante' => $this->session->getSessionName(),
        		'tabSessions' => $this->session->getTabSessions()
            )));
            $response->setPublic();
            $response->setETag(md5($response->getContent()));
            return $response;
        }
        $this->session->set('correspondance_Message_Code', $correspondance_message_code);
    } else {
        $this->tab_modules = $this->session->get('tabModules');
        $correspondance_message_code = $this->session->get('correspondance_Message_Code');                          //      Tableau de clé IDModule et de valeurs : CATEGORIE.NumMODULE.NumMESSAGE
    }
    $date_debut = date('d/m/Y', strtotime(date('Y/m/d').' + 1 DAY'));   //date('d/m/Y');
    $tabDonnee = array();
    $tabDonnee['0'] = array();
    $tabDonnee['0']['horodatage'] = null;
    $tabDonnee['0']['cycle'] = null;
    $tabDonnee['0']['codeModule'] = null;
    $tabDonnee['0']['numero_localisation'] = null;
    $tabDonnee['0']['valeur1'] = null;
    $tabDonnee['0']['valeur2'] = null;
    $tabDonnee['0']['intitule_genre'] = null;
    $tabDonnee['0']['message'] = null;
    $valeur_code = null;
    $codeModule = current($correspondance_message_code);
    if ($requete->getMethod() == 'POST') {
        // Recherche de l'identifiant du module correspondant au code entré
        $codeModule = $_POST['codeModule'];
        $idModule = array_search($codeModule, $correspondance_message_code);
        // Si un identifiant est trouvé : Recherche de la dernière valeur depuis la date indiquée
        if ($idModule != false) {
            // Récupération de l'id de la localisation
            $idLocalisation = $_POST['listeLocalisations'];
            $date_debut = $_POST['date_recherche'];
            $serviceConfiguration = $this->get('ipc_prog.configuration');
            $tmp_datedebut = '';
            $tmp_datedebut = $serviceConfiguration->rechercheLastValue($idModule, $idLocalisation, $fillnumbers->reverseDate($date_debut), $limitFirstDate);
            $firstValue = array();
            $tmp_donnee = new Donnee();
            $first_datetime = new \Datetime($limitFirstDate);
            $dateTmp = new \Datetime($tmp_datedebut);
            $dateTmp->add(new \DateInterval('P1D'));
            if ($dateTmp < $first_datetime) {
                $dateTmp = $first_datetime;
            }
            $dateAfterDebut = $dateTmp->format('Y-m-d H:i:s');
            $firstValue = $tmp_donnee->sqlGetLast($dbh, $tmp_datedebut, $dateAfterDebut, $idModule, $idLocalisation);
            // Si une précédente valeur est trouvée : Retour des informations sur la donnée : Récupération du nom de la localisation et de l'intitulé du genre à partir des ids
            if ($firstValue != null) {
                $tabDonnee = $firstValue;
                foreach ($liste_localisations as $key => $localisation) {
                    if ($localisation['id'] == $tabDonnee[0]['localisation_id']) {
                        $tabDonnee[0]['numero_localisation'] = $localisation['numero_localisation'];
                    }
                }
                foreach ($liste_genres as $key => $genre) {
                    if ($genre['id'] == $this->tab_modules[$tabDonnee[0]['module_id']]['genre']) {
                        $tabDonnee[0]['intitule_genre'] = $genre['intitule_genre'];
                    }
                }
                $tabDonnee[0]['message'] = $this->tab_modules[$tabDonnee[0]['module_id']]['message'];
                $tabDonnee[0]['codeModule'] = $correspondance_message_code[$tabDonnee[0]['module_id']];
                //  Récupération du paramètre indiquant le nombre de chiffres après la virgule à afficher
                $nbDecimal = $configuration->SqlGetParam($dbh, 'arrondi');
                $tabDonnee[0]['valeur1'] = round($tabDonnee[0]['valeur1'], $nbDecimal);
				$tabDonnee[0]['valeur2'] = round($tabDonnee[0]['valeur2'], $nbDecimal);
            } else {
                $tabDonnee['0']['horodatage'] = null;
                $tabDonnee['0']['cycle'] = null;
                $tabDonnee['0']['codeModule'] = null;
                $tabDonnee['0']['numero_localisation'] = null;
                $tabDonnee['0']['valeur1'] = null;
                $tabDonnee['0']['valeur2'] = null;
                $tabDonnee['0']['intitule_genre'] = 'Undefined';
                $tabDonnee['0']['message'] = null;
            }
        }
    }
    $dbh = $connexion->disconnect();
    $response = new Response($this->renderView('IpcOutilsBundle:Outils:rechercheValeur.html.twig', array(
        'liste_localisations' => $liste_localisations,
        'datedebut' => $date_debut,
        'tabDonnee' => $tabDonnee[0],
        'code_module' => $codeModule,
		'sessionCourante' => $this->session->getSessionName(),
        'tabSessions' => $this->session->getTabSessions()
    )));
    $response->setPublic();
    $response->setETag(md5($response->getContent()));
    return $response;
}

// Recherche des 10000 premières lignes de la table donnee tmp dont le champs erreur = true
public function importErrorsDonneesAction() {
    $this->initialisation();
    $fillnumbers = $this->fillnumbers;
    $session_page = $this->session->get('session_page');
    $page = 1;
    $limit = 10000;
    $offset = 0;
    $nb_fichiers_max = 100;
    $texte_log = "";
    $connexion = $this->get('ipc_prog.connectbd');
    $dbh = $connexion->getDbh();
    // Temps maximum d'execution des requêtes avant kill
    $configuration  = new Configuration();
    $maximum_execution_time = $configuration->SqlGetParam($dbh, 'maximum_execution_time');
    // Récupération du service de gestion des logs
    $service_log = $this->get('ipc_prog.log');
    // Si la page provient de 'importErrorDonnees.html.twig' : Récupération de la page à afficher
    $donneesError = new Donneetmp();
    $requete = $this->get('request');
    $nb_error = 0;
    if ($requete->getMethod() == 'POST') {
        $nb_error = $_POST['nb_error'];
        // Récupération du choix de l'utilisateur (Détail, Réimportation, Suppression, Page suivante, Page précédente)
        $choix_submit = $_POST['choixSubmit'];
        // Si la demande concerne l'affichage d'une autre page
        if (($choix_submit == "NextPage") || ($choix_submit == "PrevPage")) {
            // Si demande de la page suivante : offset = offsetn (l'offset de départ vaut l'ancien offset de fin)
            if ($choix_submit == "NextPage") {
                $page = $_POST['page'] + 1;
            }
            if ($choix_submit == "PrevPage") {
                $page = $_POST['page'] - 1;
                if ($page < 0) {
                    $page = 0;
                }
            }
            $offset = $session_page["$page"];
        } else {
            // Récupération de la liste des fichiers selectionnés dans la page 'Gestion des données erronées'
            $entity_module = new Module();
            $tab_modules_sql = $entity_module->SqlGetModules($dbh);
            foreach ($tab_modules_sql as $module) {
                $keyTab = $module['categorie'].$fillnumbers->fillNumber($module['numero_module'], 2).$fillnumbers->fillNumber($module['numero_message'], 2).$fillnumbers->fillNumber($module['genre_id'], 2).$fillnumbers->fillNumber($module['mode_id'], 2);
                $this->tab_modules[$keyTab] = $module['id'];
            }
            $liste_fichiers_a_rejouer = $_POST['FileList'];
            foreach ($liste_fichiers_a_rejouer as $fichier) {
                // Récupération de l'erreur et du fichier à rejouer
                $pattern = '/^(.+?);(.+?)$/';
                if (preg_match($pattern, $fichier, $tabFichier)) {
                    $nom_fichier = $tabFichier[1];
                    $erreur_fichier = $tabFichier[2];
                }
                // Si le bouton submit cliqué sur le formulaire importErrorDonnee.html.twig est Details de l'erreur

                // Recherche des détails de l'erreur dans le fichier de log
                if ($choix_submit == 'DetailsE') {
                    // Recherche de la date d'importation de la donnée erronée
                    $entityFichierRapport = $this->em->getRepository('IpcProgBundle:Fichier')->findOneByNom($nom_fichier);
                    $dateTraitement = substr($entityFichierRapport->getDateTraitementStr(), 0, 8);
                    // Si la date de traitement de la donnée erronée est différente de la date actuelle :
                    //  Le fichier de log correspond au fichier présent dans le dossier à la date du traitement
                    $fichierLog = 'importBin.log';
                    if ($dateTraitement != date('Ymd')) {
                        $fichierLog = 'backup/'.$dateTraitement.'/'.'*_importBin.log.bz2';
                    }
                    // Recherche en log du nom du fichier et de l'erreur indiquée
                    $texte_log .= $service_log->rechercheTexte($fichierLog, $fichier, 'all');
                } elseif ($choix_submit == 'DetailsI') {
                    $entityFichierRapport = $this->em->getRepository('IpcProgBundle:Fichier')->findOneByNom($nom_fichier);
                    $dateTraitement = substr($entityFichierRapport->getDateTraitementStr(), 0, 8);
                    // Si la date de traitement de la donnée erronée est différente de la date actuelle :
                    //  Le fichier de log correspond au fichier présent dans le dossier à la date du traitement
                    $fichierLog = 'importBin.log';
                    if ($dateTraitement != date('Ymd')) {
                        $fichierLog = 'backup/'.$dateTraitement.'/'.'*_importBin.log.bz2';
                    }
                    // Si le bouton submit cliqué sur le formulaire importErrorDonnee.html.twig est Details de l'importation du fichier
                    // Recherche des détails sur le fichier
                    $texte_log .= $service_log->rechercheTexte($fichierLog, $nom_fichier, 'all');
                } elseif ($choix_submit == 'PurgeDD') {
					// Purge des doublons - Déplacement dans la table des doublons	
					$this->moveDoublons();
					$nb_error = $donneesError->SqlGetNb($dbh);
                } else {
                    // Si le bouton submit cliqué sur le formulaire importErrorDonnee.html.twig est 'Réimportation' ou 'Suppression' des données
                    // Appel de la fonction de réimportation des données
                    // Appel de la fonction pour rejouer les données du fichier dont l'erreur est celle indiquée
                    $texte_log .= $this->reimport($dbh, $nom_fichier, $erreur_fichier, $choix_submit);
                    // Le nombre de données en erreur peut diffèrer du précédents après réimportation
                    $pattern = '/REIMPORT \[INFO\];;Fin d\'importation du fichier \(.+?avec (.+?) erreur\(s\), (.+?) ligne\(s\) insérée\(s\) et (.+?) doublon\(s\)\n$/';
                    if (preg_match($pattern, $texte_log, $tabtmp)) {
                        $nb_error -= $tabtmp[2];
                    } else {
                        $nb_error = $donneesError->SqlGetNb($dbh);
                    }
                }
            }
        }
    } else {
        // Recherche du nombre de données en erreur
        $nb_error = $donneesError->SqlGetNb($dbh);
    }
    $session_page[$page]=$offset;
    // Selection de la liste des fichiers erronés
    // La listes des noms de fichiers est placé dans le tableau '$tab_liste_fichiers'
    // On effectue les recherches jusqu'à ce que 100 noms de fichiers soient récupérés
    $tab_tmp_liste_fichiers = array();
    $tab_liste_fichiers = array();
    $nberrorfiles = 0;
    do {
        $liste_fichiers = $donneesError->SqlGetFiles($dbh, $limit, $offset);
        // Si la requête ne retourne rien, toutes les données en erreur ont étées analysées
        // Sinon on calcul combien de fichiers ont déjà étés récupérés
        if (!$liste_fichiers) {
            $nberrorfiles = $nb_fichiers_max;
        } else {
            // Concatenation des nouveaux noms de fichiers récupérés avec les anciens
            $tab_tmp_liste_fichiers = array_merge($tab_tmp_liste_fichiers, $liste_fichiers);
            // Suppression des doublons
            foreach($tab_tmp_liste_fichiers as $fichier) {
                // Tableau ayant pour clé le nom du fichier pour valeur les différentes erreurs
                // Si le nom du fichier existe déjà
                if (isset($tab_liste_fichiers[$fichier['nom_fichier']])) {
                    // Si l'erreur n'est pas déjà parmis la liste des erreurs, création d'une nouvelle valeur au tableau ( Nouveau fichier - Nouvelle ligne pour la liste déroulante )
                    if (! in_array($fichier['erreur'], $tab_liste_fichiers[$fichier['nom_fichier']])) {
                        // Récupération du nombre d'erreurs
                        $countErrors = count($tab_liste_fichiers[$fichier['nom_fichier']]) + 1;
                        $param = 'error_'.$countErrors;
                        $tab_liste_fichiers[$fichier['nom_fichier']][$param] = $fichier['erreur'];
                        $nberrorfiles ++;
                    }
                } else {
                    // Si le nom du fichier n'existe pas encore : Déclaration d'une nouvelle valeur au tableau
                    $tab_liste_fichiers[$fichier['nom_fichier']]['error_1'] = $fichier['erreur'];
                    $nberrorfiles ++;
                }
            }
        }
        $offset += $limit;
    } while ($nberrorfiles < $nb_fichiers_max);
    $session_page[$page + 1] =  $offset;
    // Enregistrement de la variable de session
    $this->session->set('session_page', $session_page);
    $dbh = $connexion->disconnect();
    if ($texte_log == '') {
        $texte_log = 'Aucune information trouvée dans le fichier de log';
    }
    //  Si des erreurs sont à signaler : Retour de la page d'affichage des erreurs
    $response = new Response($this->renderView('IpcOutilsBundle:Outils:importErrorDonnees.html.twig', array(
        'liste_fichiers' => $tab_liste_fichiers,
        'nb_error' => $nb_error,
        'page' => $page,
        'offset' => $offset,
        'maximum_execution_time' => $maximum_execution_time,
        'texte_log' => $texte_log,
		'sessionCourante' => $this->session->getSessionName(),
        'tabSessions' => $this->session->getTabSessions()
    )));
    $response->setPublic();
    $response->setETag(md5($response->getContent()));
    return $response;
}



public function reimport($dbh, $nomFile, $errorFile, $choixSubmit) {
	$this->constructeur();
    // Appel du service ServiceRattrapImportBin.php
    $service_rattrapImportBin = $this->container->get('ipc_prog.rattrapimportbin');
    if ($choixSubmit == 'Valider') {
        // Execution de la fonction de réimportation des données : Retourne le dernier message de logs concernant le fichier
        $log_reimport = $service_rattrapImportBin->importation("$nomFile", "$errorFile", $this->tab_modules);
    } else if ($choixSubmit == 'Supprimer') {
        $log_reimport = $service_rattrapImportBin->suppression("$nomFile", "$errorFile");
    }
    return($log_reimport);
}


public function moveDoublons(){
	$service_rapport = $this->get('ipc_prog.rapports');
	$service_rapport->moveDoublons();
	return new Response();
}

// Calcul le nombre de données sur les X dernier jours (dans la table t_donnee)
public function calculNbDBDonneesAction(Request $request) {
       $srv_getNbDBDonnees = $this->container->get('ipc_outil.getNbDBDonnees');

       // Récupération du nombre de données
       $nb_donnees = $srv_getNbDBDonnees->getNbDBDonnees();
       $s = '';
       if ($nb_donnees > 1) {
               $s = 's';
       }

       // Récupération de la valeur X (nombre de jours de recherche)
       $nb_jours = $srv_getNbDBDonnees->getEntityParamNbJours()->getValeur();
       $s2 = '';
       if ($nb_jours > 1) {
               $s2 = 's';
       }

       $ret_message = "$nb_donnees donnée$s trouvée$s dans la table [ t_donnee ] sur les ";
       $ret_message .= "$nb_jours dernier$s2 jour$s2";
       $this->get('session')->getFlashBag()->add('info', $ret_message);
       return($this->indexAction($request));
}


}
