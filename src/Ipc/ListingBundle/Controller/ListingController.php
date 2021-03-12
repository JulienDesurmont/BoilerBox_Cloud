<?php
//src/Ipc/ListingBundle/Controller/ListingController

namespace Ipc\ListingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Ipc\ProgBundle\Entity\Donnee;
use Ipc\ProgBundle\Form\DonneeType;
use Ipc\ProgBundle\Entity\Module;
use Ipc\ProgBundle\Entity\Localisation;
use Ipc\ProgBundle\Entity\Configuration;
use Ipc\ProgBundle\Entity\Genre;
use Ipc\ProgBundle\Entity\Site;
use Symfony\Component\HttpFoundation\Request;
use \PDO;
use \PDOException;
use Ipc\ConfigurationBundle\Entity\Requete;
use Ipc\ConfigurationBundle\Form\Type\RequeteType;
use Ipc\ConfigurationBundle\Form\Handler\RequeteHandler;



class ListingController extends Controller {

private $tabModulesL;
private $fichierLog = 'importBin.log';
private $session;
private $pageTitle;
private $liste_localisations;
private $tab_conversion_loc_id;
private $tab_conversion_loc_num;
private $tab_conversion_loc_getnum;
private $tab_conversion_genre_id;
private $tab_conversion_genre_num;
private $tab_conversion_message_id;
private $liste_genres;
private $liste_genres_en_base;
//private $liste_modules;
private $liste_messages_modules;
private $liste_noms_modules;
private $dbh;
private $em;
private $connexion;
private $messagePeriode;
private $last_loc_id;
private $activation_modbus;
// Indique le compte des requêtes affichées. Permet de cocher ou décocher la checkbox 'Requêtes cliente'
private $compteRequetePerso;
private $limit_excel;
private $limit_export_sql;
private $entities_requetes_perso;



#constructeur()             Initialisation de la variable de session
#initialisation()           Initialisation de certaines variables
#getRequetesPerso()         Récupère la liste des requêtes personnelles pour les afficher dans le bouton SELECT
#initialisationListes()     Initisalisation des Listes des modules, des messages, des genres etc. à afficher dans la POPUP
#getLogDir()                Récupèration du répertoire de log
#setLog()                   Inscription d'une ligne dans le fichier de log
#reverseDate()              Inverse la date passée en paramètre : (entrée) 2014-05-10 12:23:34 -> (sortie) 10-05-2014 12:23:34
#indexAction()              Affiche la page d'accueil LISTING et traite les demandes de SUPPRESSION et d'AJOUT des requêtes
#afficheListingAction()     Lance les requêtes demandées et affiche le résultat
#ajaxTrieDonneesAction      Appelée en AJAX : Retourne les données triées selon la colonne selectionnée par l'utilisateur



public function constructeur(){
    if (empty($this->session)) {
        $service_session = $this->container->get('ipc_prog.session');
        $this->session = $service_session;
    }
	$this->em = $this->get('ipc_prog.connectbd')->getManager();
}

// Initialisation des variables communes à toutes les fonctions
// $page                   indique la page par défaut à afficher
// $session_page['page']   indique la page courante
// $limit                  indique le nombre de données à afficher par page
// $limit_excel          indique le nombre de données  maximum autorisées pour l'exportation sous excel
// $messagePeriode         indique le message représentant la période de recherche
// $request                indique le service gérant la requête appelant la fonction 'indexAction'
// $liste_req              indique la liste des réquêtes demandées par l'utilisateur
// $session_page           indique la page en cours / le nombre de pages max / la dernière page affichée
// $maximum_execution_time indique le temps maximum d'execution des requêtes avant le kill automatique de celles-ci

public function initialisation() {
	$this->constructeur();
	$this->connexion = $this->get('ipc_prog.connectbd');
	$this->dbh = $this->connexion->getDbh();
	$this->pageTitle = $this->session->get('pageTitle');
	$this->tabModulesL = array();
    $translator = $this->get('translator');
    $this->messagePeriode = $translator->trans('periode.info.none');
	$this->activation_modbus = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('activation_modbus')->getValeur();
	$this->limit_excel = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('limitation_excel_listing')->getValeur();
	$this->limit_export_sql = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('limitation_export_sql_listing')->getValeur();
    // Si une demande de nouvelle page est demandée on ne cherche pas à savoir si le nombre de données dépasse la limite
    //      Sinon Si il ya validation de la message box qui demande d'effectuer la recherche, la limite n'est pas restrictive
    //          Sinon la limite est définie par le paramètre [limitation_export_sql_listing]
    if (isset($_GET["listing"])) {
        $this->limit_export_sql = -1;
    } else {
        if ($this->session->get('validation_message_box', array())) {
            $this->limit_export_sql = -1;
            $this->session->remove('validation_message_box');
        } else {
            $this->limit_export_sql = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('limitation_export_sql_listing')->getValeur();
        }
    }
}


// Recherche de la liste des requêtes personnelles à afficher dans le select 
// Si la variable est définie, re-affichage des requêtes désirées
private function getRequetesPerso() {
	// Variable qui indique si les recherches concernent le compte client ou le compte utilisateur courant
	$this->compteRequetePerso = $this->session->get('compte_requete_perso','');
	if ($this->compteRequetePerso) {
		if ($this->compteRequetePerso != 'Personnel') {
			$entities_requetes_perso = $this->em->getRepository('IpcConfigurationBundle:Requete')->myFindByCompte($this->compteRequetePerso, 'listing');
		} else {
			$this->compteRequetePerso = 'Personnel';
			// Recherche de l'appelation des requêtes de l'utilisateur
			$entities_requetes_perso = $this->em->getRepository('IpcConfigurationBundle:Requete')->myFindByCreateur($this->session->get('label'), 'listing');
		}
	} else {
		//       Le compte personnel n'est accessible qu'a partir des ROLES TECHNICIENS (donc pas pour les CLIENTS)
		if ($this->get('security.context')->isGranted('ROLE_TECHNICIEN')) {
			$this->compteRequetePerso = 'Personnel';
			// Recherche de l'appelation des requêtes de l'utilisateur
			$entities_requetes_perso = $this->em->getRepository('IpcConfigurationBundle:Requete')->myFindByCreateur($this->session->get('label'), 'listing');
		} else if ($this->get('security.context')->isGranted('ROLE_CLIENT')) {
			$this->compteRequetePerso = 'Client';
			// Recherche de l'appelation des requêtes de l'utilisateur
			$entities_requetes_perso = $this->em->getRepository('IpcConfigurationBundle:Requete')->myFindByCompte($this->compteRequetePerso, 'listing');
		}
	}
	return ($entities_requetes_perso);
}

public function initialisationListes($localisation_id = null) {
	$dbh = $this->dbh;
	$liste_genres_en_base = null;
	$liste_genres = null;
	//$liste_modules = null;
	$liste_messages_modules = array();
	$liste_noms_modules = array();
	$correspondance_message_code = array();
	$this->tab_conversion_loc_id = array();
	$this->tab_conversion_loc_num = array();
	$this->tab_conversion_loc_getnum = array();
	$this->tab_conversion_genre_id = array();
	$this->tab_conversion_genre_num = array();
	$this->tab_conversion_message_id = array();
	

	// Initialisation des listes de localisation : Récupération des localisations associées au site courant
	$this->container->get('ipc_prog.session.boilerbox')->definirListeLocalisationsCourantes();

	$this->liste_localisations = $this->session->get('tablocalisations');
	if ($this->liste_localisations == null) {
		$this->get('session')->getFlashBag()->add('info',"Aucune Localisation définie pour le site courant (l1)");
		return false;
	}
	// Initialisation d'un tableau de converion des localisations permettant d'afficher la désignation d'une localisation selon son id
	foreach ($this->liste_localisations as $key => $localisation) {
		$this->tab_conversion_loc_id[$localisation['id']] = $localisation['designation'];
		$this->tab_conversion_loc_num[$localisation['numero_localisation']] = $localisation['designation'];
		$this->tab_conversion_loc_getnum[$localisation['id']] = $localisation['numero_localisation'];
	}
	// Récupération de la dernière localisation entrée pour la réafficher par défaut dans la popup
	$this->last_loc_id = $this->session->get('last_loc_id');
	// Si il n'y a pas eu de requête enregistrée, la localisation par défaut est la première de la liste
	if (empty($this->last_loc_id)) {
		$this->last_loc_id = $this->liste_localisations[0]['id'];
	}


	// Initialisation de la liste des genres autorisés
	$this->container->get('ipc_prog.session.boilerbox')->definirListeDesGenres();

	$this->liste_genres = $this->session->get('session_genrel_autorise');
	//   Tableau qui indique l'intitulé du genre selon son id
	foreach ($this->liste_genres as $key => $genre) {
		$this->tab_conversion_genre_id[$genre['id']] = $genre['intitule_genre'];
		$this->tab_conversion_genre_num[$genre['id']] = $genre['numero_genre'];
	}

	// Initialisation de la liste des modules
	$this->container->get('ipc_prog.session.boilerbox')->definirTabModuleL();

	$this->tabModulesL = $this->session->get('tabModules');
    if ($this->tabModulesL == null) {
        $this->get('session')->getFlashBag()->add('info', "Listing : Aucun module n'est associé aux localisations du site courant : Veuillez importer la/les table(s) d'échanges ou modifier le paramètre popup_simplifiee");
        return false;
    }
	$correspondance_message_code = $this->session->get('correspondance_Message_Code');

	$liste_messages_modules = $this->session->get('liste_messages_modules_listing');
	$liste_noms_modules = $this->session->get('liste_noms_modules_listing');
	$tab_conversion_message_id = $this->session->get('tab_conversion_message_id_listing');
	if ((empty($liste_messages_modules)) || empty($liste_noms_modules) || empty($tab_conversion_message_id)) {
		if (count($this->liste_localisations) > 1) {
			// Récupération initiale des informations concernant la localisation 1
			$localisation_id = $this->last_loc_id;
			foreach ($this->tabModulesL as $key=>$module) {
				if (in_array($localisation_id, $module['localisation'])) {
					// Création d'un tableau pour éviter des présenter des doublons dans les intitulé des modules
					if (! in_array($module['intitule'], $liste_noms_modules)) {
						array_push($liste_noms_modules, $module['intitule']);
					}
					$liste_messages_modules[$key] = $correspondance_message_code[$key]." - ".$this->suppressionDesCaracteres($module['message']);
					$this->tab_conversion_message_id[$key] = $module['message'];
				}
			}
		} else {
			// Si il y a plusieurs localisations : Récupération initiale des informations concernant la localisation 1
			// Création des tableaux des intitulés de module
				//et des messages de modules
			foreach ($this->tabModulesL as $key=>$module) {
				// Création d'un tableau pour éviter des présenter des doublons dans les intitulé des modules
				if (! in_array($module['intitule'], $liste_noms_modules)) {
					array_push($liste_noms_modules, $module['intitule']);
				}
				$liste_messages_modules[$key] = $correspondance_message_code[$key]." - ".$this->suppressionDesCaracteres($module['message']);
				$this->tab_conversion_message_id[$key] = $module['message'];
			}
		}
		asort($liste_noms_modules);
		asort($liste_messages_modules);
		$this->liste_noms_modules = $liste_noms_modules;
		$this->liste_messages_modules  = $liste_messages_modules;
		// Ajout d'une variable de session afin de permettre une recherche des messages par recherche direct 
		$this->session->set('liste_messages_modules_listing', $liste_messages_modules);
		// Ajout d'une variable de session qui stock la liste des modules afin d'éviter des faire la boucle de parcours de modules une fois la variable crée
		$this->session->set('liste_noms_modules_listing', $liste_noms_modules);
		// Ajout de la variable de session qui stock les correspondances idModule => message
		$this->session->set('tab_conversion_message_id_listing', $this->tab_conversion_message_id);
	} else {
		$this->liste_noms_modules = $liste_noms_modules;
		$this->liste_messages_modules = $liste_messages_modules;
		$this->tab_conversion_message_id = $tab_conversion_message_id;
	}
	return(true);
}

// Retourne le chemin des répertoires de log
protected function getLogDir() {
	return __DIR__.'/../../../../web/logs/'; 
}

// Permet l'inscription d'un message de log
public function setLog($message) {
	$this->constructeur();
	$this->initialisation();
	$ficlog = $this->getLogDir().$this->fichierLog;
	$message = date("d/m/Y;H:i:s;").$message."\n";
	$fp	= fopen($ficlog, "a");
	fwrite($fp, $message);
	fclose($fp);
}


//	Fonction qui recoit une date en entrée et inverse l'année et le jour : ex -> (entrée) 2014-05-10 12:23:34 <- (sortie) 10-05-2014 12:23:34
public function reverseDate($horodatage) {
	$this->constructeur();
	$pattern = '/^(\d{2})([-\/]\d{2}[-\/])(\d{4})(.+?)$/';
	if (preg_match($pattern, $horodatage, $tabdate)) {
		$retour_heure = $tabdate[3].$tabdate[2].$tabdate[1].$tabdate[4];
		return($retour_heure);
	}
	return($horodatage);
}


public function indexAction() {
	$this->constructeur();
	// Initialisation des variables globales
	// Si l'accès à la page des Listing n'est pas autorisée, retour vers la page d'accueil avec un message flash d'information
	$this->initialisation();
	$autorisation_acces = $this->initialisationListes();
	if ($autorisation_acces == false) { 
		return $this->redirect($this->generateUrl('ipc_prog_homepage'));
	}
	$session_date = $this->session->get('session_date');
	$messagePeriode = $this->messagePeriode;
	if (! empty($session_date)) {
		setlocale (LC_TIME, 'fr_FR.utf8', 'fra');
		$messagePeriode = $session_date['messagePeriode'];
	}
	$fillnumbers = $this->get('ipc_prog.fillnumbers');
	$dbh = $this->dbh;
	$heure_debut = strtotime(date('Y-m-d h:i:s'));
	$page = 1;
	if ($this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('listing_nb_par_page'))
	{
		$limit = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('listing_nb_par_page')->getValeur();
	} else {
		$limit = 1000;
	}
	$limit_initial = $limit;
	$request = $this->get('request');
	$liste_req = $this->session->get('liste_req');
	$configuration = new Configuration();
	$maximum_execution_time = $configuration->SqlGetParam($dbh, 'maximum_execution_time');
	$correspondance_message_code = $this->session->get('correspondance_Message_Code');
	$session_idmodules = $this->session->get('session_idmodules');
	$session_page = $this->session->get('session_page');
	if (! isset($session_page['maxPage'])) {
		$session_page['maxPage'] = 0;
	}
	// Récupération du choix de l'utilisateur : Trois submit sont présents sur la page index.html : 'ajoutRequete (dans la popup)',  'suppressionRequete (pour Supprimer)' et 'RAZ'
	$submit = isset($_GET["choixSubmit"]) ? $_GET["choixSubmit"] : null;

	// Partie traitant les requêtes provenant de la page index.html 
	if ($submit != null) {
		// Analyse des requêtes si la demande concerne la suppression d'une requête
		if ($submit == 'suppressionRequete') {
			// Récupération du numéro de requête à supprimer
			$tabModifRequete = explode('_', $_GET['suppression_requete']);
			$idRequeteASup = $tabModifRequete[1];
			// si il y a plus d'une requete : Suppression de la requête Sinon reinitialisation de la variable
			if (count($liste_req) == 1) {
				$this->session->remove('liste_req');
				$this->session->remove('session_idmodules');
				$this->session->remove('session_fin_de_recherche');
				$liste_req = $this->session->get('liste_req');
			} else {
				// Si la requête est une requête sans condition
				if (($liste_req[$idRequeteASup]['Sql']['codeVal1'] == 'undefined') && ($liste_req[$idRequeteASup]['Sql']['codeVal2'] == 'undefined')) {
					// Récupération des ids de modules qui vont être supprimés pour les retirer de la variable de session session_idModule
					foreach ($liste_req[$idRequeteASup]['Sql']['tab_id_modules'] as $keyIdModule) {
						if (($keyToSup = array_search($keyIdModule, $session_idmodules[$liste_req[$idRequeteASup]['Sql']['id_localisation']])) != false) {
							unset($session_idmodules[$liste_req[$idRequeteASup]['Sql']['id_localisation']][$keyToSup]);
						}
					}
					// Redéfinition de la variable de session
					$this->session->set('session_idmodules', $session_idmodules);
				}
				// Suppression de la requête de la liste des requêtes à rechercher
				unset($liste_req[$idRequeteASup]);
				// Réorganisation du tableau
				$liste_req = array_filter($liste_req);
				$liste_req = array_values($liste_req);
				$this->session->set('liste_req', $liste_req);
			}
		} else if($submit == 'ajoutRequete') {
			// Si une modification de requête est demandée : Suppression de l'ancienne requête et création d'une nouvelle requête
			if ($_GET['modificationRequete'] != null) {
				$idRequeteASup = $_GET['modificationRequete'];
				if (count($liste_req) == 1) {
					$this->session->remove('liste_req');
					$this->session->remove('session_fin_de_recherche');
					$this->session->remove('session_idmodules');
					$session_idmodules = array();
					$liste_req = array();
				} else {
					// Si la requête est une requête sans condition
					if (($liste_req[$idRequeteASup]['Sql']['codeVal1'] == 'undefined') && ($liste_req[$idRequeteASup]['Sql']['codeVal2'] == 'undefined')) {
						// Récupération des ids de modules qui vont être supprimés pour les retirer de la variable de session session_idModule
						foreach ($liste_req[$idRequeteASup]['Sql']['tab_id_modules'] as $keyIdModule) {
							if (($keyToSup = array_search($keyIdModule, $session_idmodules[$liste_req[$idRequeteASup]['Sql']['id_localisation']])) != false) {
								unset($session_idmodules[$liste_req[$idRequeteASup]['Sql']['id_localisation']][$keyToSup]);
							}
						}
						// Redéfinition de la variable de session
						$this->session->set('session_idmodules', $session_idmodules);
					}
					unset($liste_req[$idRequeteASup]);
					// Réorganisation du tableau
					$liste_req = array_filter($liste_req);
					$liste_req = array_values($liste_req);
					$this->session->set('liste_req', $liste_req);
				}
			}
			// Récupération de la localisation, du genre, de l'intitulé du module et du message du module ( message représenté par l'id de son module )
			// Si le site ne comporte qu'une seule localisation la liste déroulante n'est pas affichée, il n'y a donc pas de récupération du paramètre $_GET['listeLocalisations'];
			// Dans ce cas la variable $idLocalisation est initialisé à 'all'
			// Sinon par défaut la localisation ($_GET['listeLocalisations']) vaut 'all'
			// Pour les Administrateurs : Si un clic sur Tous Sites/Toutes localisations : la requête est sans distinction de site ou de localisation : Mise à every du paramètre idLocalisation
			$idLocalisation = $_GET['listeLocalisations'];
			//	Si la localisation différe de la précédente localisation : Réinitialisation des variables de session utilisées par la popup
			if ($idLocalisation != $this->last_loc_id) {
				$this->session->set('liste_messages_modules_listing', array());
				$this->session->set('liste_noms_modules_listing', array());
				$this->session->set('tab_conversion_message_id_listing', array());
				// On enregistre la dernière localisation entrée pour faciliter l'utilisation de la popup
				$this->session->set('last_loc_id', $idLocalisation);
			}
			$idGenre = $_GET['listeGenres'];
			$intituleModule = $_GET['listeModules'];
			$idModules = $_GET['listeIdModules'];
			// Récupération des conditions de recherche et des valeurs 1 et 2 si celles-ci sont demandées.     Par défaut elles ne sont pas requise et les conditions retournées = 'None'
			// Les conditions peuvent être : Supérieur (Sup)/ Inférieur (Inf) / Interval (Int)
			$val1min = null;
			$val1max = null;
			// Indique quel type de recherche sur la valeur 1 est demandé (Supérieur/ Inférieur/ Interval)
			$codeVal1 = $_GET['codeVal1']; 
			if ($codeVal1 != 'undefined') {
				switch ($codeVal1) {
				case 'Inf' : 
					$val1min = (int)$_GET['codeVal1Min'];
					break;
				case 'Sup' :
					$val1min = (int)$_GET['codeVal1Min'];
					break;
				case 'Int' :
					$val1min = (int)$_GET['codeVal1Min'];
					$val1max = (int)$_GET['codeVal1Max'];
					break;
				}
			}
			$val2min = null;
			$val2max = null;
			$codeVal2 = $_GET['codeVal2'];
			if ($codeVal2 != 'undefined') {
				switch ($codeVal2) {
				case 'Inf' :
					$val2min = (int)$_GET['codeVal2Min'];
					break;
				case 'Sup' :
					$val2min = (int)$_GET['codeVal2Min'];
					break;
				case 'Int' :
					$val2min = (int)$_GET['codeVal2Min'];
					$val2max = (int)$_GET['codeVal2Max'];
					break;
				}
			}
			// Recherche de l'intitulé du genre dont l'identifiant est envoyé à la page : Permet d'indiquer l'intitulé du genre dans le message de la page listing.html
			$message_genre = 'all';
			if ($idGenre != 'all') {
				foreach ($this->liste_genres as $tmp_genre) {
					if ($tmp_genre['id'] == $idGenre) {
						$message_genre = $tmp_genre['intitule_genre'];
					}
				}
			}
			// Recherche du message du module dont l'identifiant est envoyé à la page ( Cas où un message est selectionné )
			$message_idModule = 'all';
			if ($idModules != 'all') {
				foreach ($this->tabModulesL as $key=>$message) {
					if ($key == $idModules) {
						$message_idModule = $correspondance_message_code[$idModules].'_'.$message['message'];
					}
				}
			}
			// Si une localisation est demandée Récupération du numéro de localisation
			$numeroLocalisation = null;
			foreach ($this->liste_localisations as $key=>$localisation) {
				if ($localisation['id'] == $idLocalisation) {
					$numeroLocalisation = $localisation['numero_localisation'];
				}
			}
			// Création du message représentant la recherche : Ce message sera affiché dans la page Listing.html.twig
			$messageTemporaire = $this->getMessageRequete($numeroLocalisation, $message_idModule, $intituleModule, $message_genre, $codeVal1, $val1min, $val1max, $codeVal2, $val2min, $val2max);
			// Vérification si une condition est requise : Si oui on accepte plusieurs recherches sur un meme id de module
			$condition = false;
			if (($codeVal1 != 'undefined') || ($codeVal2 != 'undefined')) {
				$condition = true;
			}
			// Initialisation du tableau des id de modules
			$tab_id_modules = array();
			// Vérification d'Enregistrement de la nouvelle requête : Passe à False si une requête avec des paramètres identiques existe déjà
			$reg = true;
			$session_fin_de_recherche = $this->session->get('session_fin_de_recherche');
			// Si la recherche de nouvelles requêtes est permise pour la localisation demandée
			if (isset($session_fin_de_recherche[$idLocalisation])) {
				if ($session_fin_de_recherche[$idLocalisation] == false) {
					// Si une requête ALL est demandée on ne fera plus de recherche pour la localisation demandée et on supprime les anciennes demandes
					if (($idModules == 'all')&&($intituleModule == 'all')&&($idGenre == 'all')&&($codeVal1 == 'undefined')&&($codeVal2 == 'undefined')) {
						// Les prochaines requêtes sur la localisation ne seront pas acceptées
						$session_fin_de_recherche[$idLocalisation] = true;
						$this->session->set('session_fin_de_recherche', $session_fin_de_recherche);
						foreach ($liste_req as $key => $requete) {
							if ($requete['Sql']['id_localisation'] == $idLocalisation) {
								unset($liste_req[$key]);
							}
						}
						// Réinitialisation du tableau des demandes utilisateur portant sur la localisation demandée
						$this->session->set('liste_req', $liste_req);
					}
				} else {
					// Si la recherche sur la localisation n'est pas permise
					$reg = false;
				}
			} else {
				// Si c'est la première requête pour la localisation : la variable n'est pas encore définie; on verifie si la demande All est faite : La requête est acceptée car c'est la première requête
				if (($idModules == 'all')&&($intituleModule == 'all')&&($idGenre == 'all')&&($codeVal1 == 'undefined')&&($codeVal2 == 'undefined')) {
					// Les prochaines requêtes sur la localisation ne seront pas acceptées
					$session_fin_de_recherche[$idLocalisation] = true;
				} else {
					$session_fin_de_recherche[$idLocalisation] = false;
				}
				$this->session->set('session_fin_de_recherche', $session_fin_de_recherche);
			}
			// Lecture des requêtes précédentes : Si une requête identique est déjà demandée on ne prend pas en compte la nouvelle requête demandée
			if ($reg == true) {
				foreach ($liste_req as $key=>$requete) {
					if ($messageTemporaire == $requete['Texte']) {
						$reg = false;
					}
				}
			}
			// Si la recherche est permise 
			if ($reg == true) {
				$message_error = null;
				$message_error_precision = null;
				// Un Genre Ou un Intitulé de Module Correspondent à plusieurs Id de module
				// Recherche en fonction de l'identifiant du genre ou de l'intitulé de la famille de module les identifiants de modules associés
				if (($idModules  == 'all') && ($intituleModule  == 'all') && ($idGenre == 'all')) {
					// Pour les clients 'Tous les genres' correspond à l'ensemble des genres autorisés
					if (! $this->get('security.context')->isGranted('ROLE_TECHNICIEN')) {
						// Si le Client a accès à tous les genres : Prise en compte de tous les modules
						if (count($this->liste_genres_en_base) == count($this->liste_genres)) {
							array_push($tab_id_modules, 'all');
						} else {
							// Si le client a un accés restreint aux genres, $tab_id_modules est composé de l'ensemble des modules ayant les genres autorisés
							foreach ($this->tabModulesL as $key => $lemodule) {
								foreach ($this->liste_genres as $key2 => $genre) {
									if ($lemodule['genre'] == $genre['id']) {
										array_push($tab_id_modules, $key);
									}
								}
							}
						}
						$idGenre = null;
					} else {
						array_push($tab_id_modules, 'all');
						$idGenre = null;
					}
				} elseif ($idModules != 'all') {
					// Si aucune condition sur les Valeurs n'est requise : Vérification des id de modules en doublon
					// Dans la variable de session $session_idmodules, stockage des id des modules dont                la recherche ne comporte pas de condition sur les Valeurs
					// A chaque nouvelle requête, une vérification des doublons est faite avec $session_idmodules si   la recherche ne comporte pas de condition sur les Valeurs
					// Dans la variable $tab_id_modules, stockage des id de modules dont                               la recherche comporte des conditions sur les valeurs
					if ($condition == false) {
						// Si la variable de session est initialisée pour la localisation
						if (isset($session_idmodules[$idLocalisation])) {
							// On indique le nouvel id de module si il n'est pas déjà dans le tableau
							if (! in_array($idModules, $session_idmodules[$idLocalisation])) {
								array_push($tab_id_modules, $idModules);
								array_push($session_idmodules[$idLocalisation], $idModules);
							} else {
								$reg = false;
							}
						} else {
							// Si la variable de session n'est pas encore initialisée pour la localisation : Initialisation + ajout de l'id du module
							$session_idmodules[$idLocalisation] = array();
							array_push($tab_id_modules, $idModules);
							array_push($session_idmodules[$idLocalisation], $idModules);
						}
					} else {
						array_push($tab_id_modules, $idModules);
					}
					$idGenre = null;
				} elseif (($intituleModule != 'all') || ($idGenre != 'all')) {
					// Si tous les messages d'un module ou d'un genre sont selectionnés
					// Pour chaque module contenu dans la variable de session tab_module
					foreach ($this->tabModulesL as $key => $lemodule) {
						// Si un intitulé et un genre sont définis :
						if (($intituleModule != 'all') && ($idGenre != 'all')) {
							// Recherche des modules ayant l'intitulé et le genre demandés
							if (($lemodule['intitule'] == $intituleModule) && ($lemodule['genre'] == $idGenre)) {
								// Vérification que l'identifiant du module n'est pas déjà à rechercher
								if ($condition == false) {
									if (isset($session_idmodules[$idLocalisation])) {
										// Ajout de l'identifiant du module à la liste des ids à rechercher
										array_push($tab_id_modules, $key);
										array_push($session_idmodules[$idLocalisation], $key);
									} else {
										$session_idmodules[$idLocalisation] = array();
										array_push($tab_id_modules, $key);
										array_push($session_idmodules[$idLocalisation], $key);
									}
								} else {
									// Si une condition est demandée on prend en compte tous les modules sans tenir compte des id en doublon
									array_push($tab_id_modules, $key);
								}
							}
						} elseif ($intituleModule != 'all') {
							if ($lemodule['intitule'] == $intituleModule) {
								if ($condition == false) {
									if (isset($session_idmodules[$idLocalisation])) {
										// Vérification que l'identifiant du module n'est pas déjà à rechercher
										// Ajout de l'identifiant du module à la liste des ids à rechercher
										array_push($tab_id_modules, $key);
										array_push($session_idmodules[$idLocalisation], $key);
									} else {
										$session_idmodules[$idLocalisation] = array();
										array_push($tab_id_modules, $key);
										array_push($session_idmodules[$idLocalisation], $key);
									}
								} else {
									array_push($tab_id_modules, $key);
								}
							}
						} elseif ($idGenre != 'all') {
							if ($lemodule['genre'] == $idGenre) {
								if ($condition == false) {
									if (isset($session_idmodules[$idLocalisation])) {
										// Vérification que l'identifiant du module n'est pas déjà à rechercher
										// Ajout de l'identifiant du module à la liste des ids à rechercher
										array_push($tab_id_modules, $key);
										array_push($session_idmodules[$idLocalisation], $key);
									} else {
										$session_idmodules[$idLocalisation] = array();
										array_push($tab_id_modules, $key);
										array_push($session_idmodules[$idLocalisation], $key);
									}
								} else {
									array_push($tab_id_modules, $key);
								}
							}
						}
					}
					if ($intituleModule != 'all') {
						$idGenre = null;
					}
				}	
				if ($reg == true) {
					// Suppression de doublons + Mise à jour de la variable de Session
					$newSession_idmodules = array();
					foreach ($session_idmodules as $keyIdLoc => $infoIdModules) {
						$newSession_idmodules[$keyIdLoc] = array_unique($infoIdModules); 
					}
					$this->session->set('session_idmodules', $newSession_idmodules);
					// Récupération du nombre de requêtes enregistrées par l'ajout d'un nouveau champs
					$tmpNumeroDeRequete	= count($liste_req);
					$liste_req[$tmpNumeroDeRequete]['Sql']['num_localisation'] = $numeroLocalisation;
					$liste_req[$tmpNumeroDeRequete]['Sql']['id_localisation'] = $idLocalisation;
					$liste_req[$tmpNumeroDeRequete]['Sql']['designation_localisation'] = $this->tab_conversion_loc_num[$numeroLocalisation];
					// Utilisé pour les recherches Type 1 : Modules et Intitulé de module
					$liste_req[$tmpNumeroDeRequete]['Sql']['tab_id_modules'] = $tab_id_modules;
					// Utilisé pour les recherches Type 2 : Genre
					$liste_req[$tmpNumeroDeRequete]['Sql']['genre'] = $idGenre;
					$liste_req[$tmpNumeroDeRequete]['Sql']['codeVal1'] = $codeVal1;
					$liste_req[$tmpNumeroDeRequete]['Sql']['val1min'] = $val1min;
					$liste_req[$tmpNumeroDeRequete]['Sql']['val1max'] = $val1max;
					$liste_req[$tmpNumeroDeRequete]['Sql']['codeVal2'] = $codeVal2;
					$liste_req[$tmpNumeroDeRequete]['Sql']['val2min'] = $val2min;
					$liste_req[$tmpNumeroDeRequete]['Sql']['val2max'] = $val2max;
					// Message de la requête
					$liste_req[$tmpNumeroDeRequete]['Texte'] = $messageTemporaire;
					// Redéfinition de la variable de session
					$this->session->set('liste_req', $liste_req);
				} else {
					// L'écriture du mot error entraine le rafraichissement de la page et l'affichage du message d'erreur
					$this->get('session')->getFlashBag()->add('info', "La requête $messageTemporaire est déjà à rechercher");
				}
			}
			// Fin de partie consacrée à l'ajout d'une nouvelle requête
		} else if ($submit == 'RAZ') {
			$this->session->remove('liste_req');
			$this->session->remove('liste_req_pour_listing');
			$this->session->remove('session_page');
			$this->session->remove('session_idmodules');
			$this->session->remove('session_fin_de_recherche');
			$liste_req = array();
			$liste_req_pour_listing = array();
		}
	} else {
		// Si la requête arrive depuis la page accueil, suppression des variables de session
		$this->session->remove('liste_req_pour_listing');
		$this->session->remove('session_page');
		$liste_req = $this->session->get('liste_req');
		$liste_req_pour_listing = array();
	}
	$this->dbh = $this->connexion->disconnect();
	// Définition du tableau formaté pour la page index.html et présentant les requêtes demandées par l'utilisateur
	$tab_requetes = array();
	$num_requete  = 0;
	foreach ($liste_req as $requete) {
		$code = null;
		$message = '';
		if (! isset($tab_requetes[$num_requete])) {
			$tab_requetes[$num_requete] = array();
		}
		// Récupération des éventuelles informations de choix sur les valeurs
		$infos_valeurs = '';
		$pattern_message = '/(\(.+\))$/';
		if (preg_match($pattern_message, $requete['Texte'], $tab_choix_valeur)) {
			$infos_valeurs = $tab_choix_valeur[1];
		}
		$pattern_message = '/Messages : \[(.+?)_(.+?)\]/';
		if (preg_match($pattern_message, $requete['Texte'], $tab_retour)) {
			$message = $tab_retour[2];
			$code = $tab_retour[1];
		} else {
			$pattern_module = '/Module : \[(.+?)\]/';
			$pattern_genre = '/Genre : \[(.+?)\]/';
			if (preg_match($pattern_module, $requete['Texte'], $tab_retour)) {
				$message = "Tous les messages du Module : ".$tab_retour[1];
				if (preg_match($pattern_genre, $requete['Texte'], $tab_retour2)) {
					$message .= " ayant le Genre : ".$tab_retour2[1];
				}
			} elseif (preg_match($pattern_genre, $requete['Texte'], $tab_retour)) {
				$message = "Tous les messages de Genre : ".$tab_retour[1];
			} else {
				$message = 'Tous les messages';
			}
		}
		$tab_requetes[$num_requete]['message'] = $message.$infos_valeurs;
		$tab_requetes[$num_requete]['code'] = $code;
		$tab_requetes[$num_requete]['localisation'] = $requete['Sql']['num_localisation'];
		$tab_requetes[$num_requete]['numrequete'] = $num_requete;
		$num_requete ++;
	}
	
	//	Retour des messages lorsque la fonction est appelée en AJAX 
	if (isset($_GET['AJAX'])) {
		echo json_encode($tab_requetes);
		return new Response();
	} else {
		// Création du formulaire des requêtes personnelles
		$ent_requete = new Requete();
		$ent_requete->setCreateur($this->session->get('label'));
		$ent_requete->setType('listing');
		$form_requete = $this->createForm(new RequeteType(), $ent_requete, [
			'action' => $this->generateUrl('ipc_accueilListing'),
			'method' => 'POST'
			]
		);
		// Récupération de la requête
		$request = $this->get('request');

		// Enregistrement de la nouvelle requête personnelle
		// Récupération du handler de formulaire
		$form_handler = new RequeteHandler($form_requete, $request);
		$entity_user = $this->em->getRepository('IpcUserBundle:User')->find($this->container->get('security.context')->getToken()->getUser()->getId());
		// Execution de la méthode d'execution du handler : Retourne True si les données du formulaire sont validées
		$process = $form_handler->process($this->em, 'listing', $entity_user, $this->session);
		// Récupération de l'id de la requête personnelle
		$id_requete_perso = $this->session->get('listing_requete_selected', null);
		$this->entities_requetes_perso = $this->getRequetesPerso();
		$response = new Response(
			$this->renderView('IpcListingBundle:Listing:index.html.twig', array(
				'messagePeriode' 		=> $messagePeriode,
				'tab_requetes' 			=> $tab_requetes,
				'strTab_requetes' 		=> json_encode($tab_requetes),
				'liste_localisations' 	=> $this->liste_localisations,
				'liste_genres' 			=> $this->liste_genres,
				'liste_nomsModules' 	=> $this->liste_noms_modules,
				'liste_messagesModules' => $this->liste_messages_modules,
				'maximum_execution_time'=> $maximum_execution_time,
				//'tab_requetes_perso' => $this->tabRequetesPerso,
				'entities_requetes_perso' => $this->entities_requetes_perso,
				'id_requete_perso'      => $id_requete_perso,
            	'compte_requete_perso' 	=> $this->compteRequetePerso,
				'sessionCourante' 		=> $this->session->getSessionName(),
				'tabSessions' 			=> $this->session->getTabSessions(),
				'form_requete'          => $form_requete->createView()
			))
		);
		$response->setPrivate();
		$response->setETag(md5($response->getContent()));
		return $response;
	}
}

public function afficheListingAction($page) {
	$this->initialisation();
	$autorisation_acces = $this->initialisationListes();
	$heure_debut = strtotime(date('Y-m-d h:i:s'));
	// Numéro de page par défaut
	$page = 1;
	// Nombre de données par page
	if ($this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('listing_nb_par_page'))
	{
		$limit = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('listing_nb_par_page')->getValeur();
	} else {
		$limit = 1000;
	}
	$limit_initial = $limit;
	$session_date = $this->session->get('session_date');
	$messagePeriode = $this->messagePeriode;
	$tabDesDonnees = array();
	$tabDesRequetes = array();
	$message_erreur = '';
	// Définir 2000000 max
	$service_numbers = $this->get('ipc_prog.fillnumbers');
	if (! empty($session_date)) {
		setlocale (LC_TIME, 'fr_FR.utf8', 'fra');
		$messagePeriode = $session_date['messagePeriode'];
	}
	// Service de modification du format des chiffres pour les afficher sur 2 caractères : ex -> (entrée) 1 <- (sortie) 01
	$fillnumbers = $this->get('ipc_prog.fillnumbers');
	// Récupération du Service de connexion à la base de donnée IPC & de l'objet représentant la connexion
	$dbh = $this->dbh; 
	// Service de récupération de la requête entrante
	$request = $this->get('request');
	// Suppression des variables de pages si un refresh est effectué
	if (isset($_GET['refresh_listing'])) {
		if ($_GET['refresh_listing'] == 'refresh') {
			$this->session->remove('session_page');
			$this->session->remove('session_idmodules');
			$this->session->remove('session_fin_de_recherche');
		}
	}
	// Récupération des variables de session
	// liste des réquêtes demandées par l'utilisateur
	$liste_req = $this->session->get('liste_req');
	$correspondance_message_code = $this->session->get('correspondance_Message_Code');
	// Variable de Session indiquant : La page en cours / Le nombre de pages Max / La denière page affichée
	$session_page = $this->session->get('session_page'); 
	if (! isset($session_page['maxPage'])) {
		$session_page['maxPage'] = 0;
	}
	$session_idmodules = $this->session->get('session_idmodules');
	// Temps maximum d'execution des requêtes avant kill
	$configuration = new Configuration();
	$maximum_execution_time = $configuration->SqlGetParam($dbh, 'maximum_execution_time');
	// Nombre de données maximum autorisées pour l'export excel
	// Partie traitant les requêtes provenant de la page index.html et listing.html
	if (isset($_GET["listing"]) || isset($_GET['refresh_listing'])) {
		// En cas de refresh : Le numero de page à afficher est le même que le précédent numéro de page
		if (isset($_GET['refresh_listing'])) {
			$page = str_replace(' ', '', htmlspecialchars(trim($_GET['pages'])));
			$pattern = '/^\d*$/';
			if (! preg_match($pattern, $page)) {
				$this->get('session')->getFlashBag()->add('info', 'Le numéro de page : '.$page.' est incorrect');
				$session_page['page'] = 1;
				$page = 1;
			}
		} else {
			// Récupération de la page à afficher
			// Si des caractères sont envoyés à la place d'un numéro de page : Affichage de la page 1
			// Sécurisation de la récupération de la page car les données proviennent d'un champs text
			$page = str_replace(' ', '',  htmlspecialchars(trim($_GET['pages'])));
			$pattern = '/^\d*$/';
			if (! preg_match($pattern, $page)) {
				$this->get('session')->getFlashBag()->add('info', 'Le numéro de page : '.$page.' est incorrect');
				$session_page['page'] = 1;
				$page = 1;
			} elseif ($page == '0') {
				// Le cas page==0 se produit lors d'un refresh sur une page ne présentant pas de données
				$session_page['page'] = 1;
				$page = 1;
			} elseif ($page > $session_page['maxPage']) {
				$this->get('session')->getFlashBag()->add('info', 'Le numéro de page : '.$page.' est incorrect');
				$page = $session_page['oldPage'];
			}
		}
	} else {	
		// Partie traitant les requêtes provenant de la page : index.html
		// Récupération du choix de l'utilisateur : Quatre submit sont présents sur la page index.html : 'Recherche',  'Nouvelle Requête', ModifRequete' et 'RAZ'
		$submit = isset($_GET["choixSubmit"])?$_GET["choixSubmit"]:null;
	}
	// Définition de la variable de session [ page ] : Numéro de page courante
	$session_page['page'] = $page;
	// Définition de l'offset à partir duquel rechercher les données
	$offset = ($session_page['page']-1) * $limit;
	// Si le formulaire est validé en cliquant sur le bouton 'Recherche' Ou si c'est une demande d'affichage d'une nouvelle page 
	if ((isset($submit) && ($submit == "Recherche")) || (isset($_GET["listing"]))) {
		// Récupération des droits d'impression pour le compte client (Seul le compte client n'hérite pas des droits technicien)
		// L'impression est toujours autorisée aux techniciens et administrateurs
		$impression_listing = null;
		if (! $this->get('security.context')->isGranted('ROLE_TECHNICIEN'))	{
			$impression_listing = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('autorisation_impression_listing')->getValeur();
		} else {
			$impression_listing = 1;
		}
		// Page affichée précédemment (Si la requête provient de la page listing après un nouveau choix de page)
		if (isset($session_page['oldPage'])) {
			$oldPage = $session_page['oldPage'];
		}
		$liste_req_pour_listing = $this->session->get('liste_req_pour_listing');
		// Regroupement des requêtes ayant des localisations et des valeurs identiques
		if (empty($liste_req_pour_listing)) {
			foreach ($liste_req as $key => $requete) {
				// Regroupement des requêtes selon l'id de la localisation et les conditions de recherche sur les valeurs 1 et 2
				// Création du message représentant la requête
				$message = $liste_req[$key]['Sql']['id_localisation'].$liste_req[$key]['Sql']['codeVal1'].$liste_req[$key]['Sql']['val1min'].$liste_req[$key]['Sql']['val1max'].$liste_req[$key]['Sql']['val2min'].$liste_req[$key]['Sql']['val2max'].$liste_req[$key]['Sql']['codeVal2'];
				// Si une requête a les mêmes paramètres de localisation et de valeur et n'a pas d'indication de genre, regroupement des identifiants des modules de la requête
				if (isset($liste_req_pour_listing[$message])) {
					$liste_req_pour_listing[$message]['tab_id_modules'] = array_merge($liste_req_pour_listing[$message]['tab_id_modules'], $liste_req[$key]['Sql']['tab_id_modules']);
					// Si la requête indique une recherche sur tous les modules : On vide le tableau des identifiants de modules et on indique que la recherche se fait sur tous les modules
					if (in_array('all', $liste_req_pour_listing[$message]['tab_id_modules'])) {
						$liste_req_pour_listing[$message]['tab_id_modules'] = array();
						$liste_req_pour_listing[$message]['tab_id_modules'][0] = 'all';
					}
					asort($liste_req_pour_listing[$message]['tab_id_modules']);
					// Ajout du texte de la requête à la requête regroupée
					$liste_req_pour_listing[$message]['Texte'] .= $liste_req[$key]['Texte'];
				} else {
					// Si la requête n'a pas les mêmes paramètres : Création d'une nouvelle requête
					$liste_req_pour_listing[$message]['tab_id_modules'] = $liste_req[$key]['Sql']['tab_id_modules'];
					$liste_req_pour_listing[$message]['num_localisation'] = $liste_req[$key]['Sql']['num_localisation'];
					$liste_req_pour_listing[$message]['id_localisation'] = $liste_req[$key]['Sql']['id_localisation'];
					$liste_req_pour_listing[$message]['designation_localisation'] = $liste_req[$key]['Sql']['designation_localisation'];
					$liste_req_pour_listing[$message]['codeVal1'] = $liste_req[$key]['Sql']['codeVal1'];
					$liste_req_pour_listing[$message]['codeVal2'] = $liste_req[$key]['Sql']['codeVal2'];
					$liste_req_pour_listing[$message]['val1min'] = $liste_req[$key]['Sql']['val1min'];
					$liste_req_pour_listing[$message]['val1max'] = $liste_req[$key]['Sql']['val1max'];
					$liste_req_pour_listing[$message]['val2min'] = $liste_req[$key]['Sql']['val2min'];
					$liste_req_pour_listing[$message]['val2max'] = $liste_req[$key]['Sql']['val2max'];
					$liste_req_pour_listing[$message]['Texte'] = $liste_req[$key]['Texte'];
				}
			}
			$this->session->set('liste_req_pour_listing', $liste_req_pour_listing);
		}

		// Pour chaque requête demandée on récupére la liste des modules à afficher
		$liste_de_la_page = '';
		$date_min_requete = null;
		if ((! isset($oldPage)) || (isset($session_page['page'])) ||  (isset($_GET['impression']) && ($_GET['impression'] == 'yesCsv'))) {
			// Code du module
			if (! isset($correspondance_message_code)) {
				$correspondance_message_code = $this->session->get('correspondance_Message_Code');
			}
			$tmp_donnee = new Donnee();
			$tabUnionRequete = array();
			foreach ($liste_req_pour_listing as $key => $requete) {
				// Récupération de la liste des identifiants de module à rechercher
				$tabDesRequetes[$key]['liste_id_modules'] = '';
				foreach ($requete['tab_id_modules'] as $tmp_idmodule) {
					if ($tmp_idmodule == 'all') {
						$tabDesRequetes[$key]['liste_id_modules'] = "all";
					} else if (in_array($requete['id_localisation'], $this->tabModulesL[$tmp_idmodule]['localisation'])) {
						$tabDesRequetes[$key]['liste_id_modules'] .= "'".$tmp_idmodule."',";
					}
				}
				// Suppression de la virgule
				if ($tabDesRequetes[$key]['liste_id_modules'] != 'all') {
					$tabDesRequetes[$key]['liste_id_modules'] = substr($tabDesRequetes[$key]['liste_id_modules'], 0, -1);
				}
				// Récupération de l'identifiant de la localisation 
				$tabDesRequetes[$key]['id_localisation'] = "'".$requete['id_localisation']."'";
				// Recherche du nombre de pages max de la requête, si il n'a pas été calculé
				if (! isset($session_page['nbDonneesTotal'])) {
					// Fonction permettant de récupérer la période minimum pour la localisation désignée
					$tmp_date_deb = $this->getDatePeriode($session_date['datedebut'], $tabDesRequetes[$key]['id_localisation'], 'debut');
					$tmp_date_fin = $this->getDatePeriode($session_date['datefin'], $tabDesRequetes[$key]['id_localisation'], 'fin');
					// Si la date de fin est < à la date min c'est qu'un des cas suivant c'est produit : 
					// cas 1 : datedeb<datemin || datefin<datemin => Valeurs retournées (datemin & datefin) avec datefin<datemin
					// cas 2 : datedeb>datemax || datefin>datemax => Valeurs retournées (datedeb & datemax) avec datedeb>datemax
					$checkDate = $this->checkDeDate($tmp_date_deb,$tmp_date_fin);  
					if ($checkDate == true) {
						// Création du texte sql de la requête
						$tabUnionRequete[] = $tmp_donnee->sqlParametresListing(
						$tabDesRequetes[$key]['id_localisation'],
						$tabDesRequetes[$key]['liste_id_modules'],
						$requete['codeVal1'],
						$requete['val1min'],
						$requete['val1max'],
						$requete['codeVal2'],
						$requete['val2min'],
						$requete['val2max']);
					}
				}
			}
			if (! isset($session_page['nbDonneesTotal'])) {
				// Recherche du nombre de données total en faisant une union des requêtes de count
				$requeteTotale = null;
				if (! empty($tabUnionRequete)) {
					$requeteTotale = '('.$tabUnionRequete[0].')';
					for ($numRequete = 1; $numRequete < count($tabUnionRequete); $numRequete++) {
						$requeteTotale .= ' OR ';
						$requeteTotale .= '('.$tabUnionRequete[$numRequete].')';
					}	
				}
				$tmp_date_deb = $this->getDatePeriode($session_date['datedebut'], $tabDesRequetes[$key]['id_localisation'], 'debut');
				$tmp_date_fin = $this->getDatePeriode($session_date['datefin'], $tabDesRequetes[$key]['id_localisation'], 'fin');

				// Requete sql COUNT : Si le COUNT dépasse la limite la valeur retournée = limite + 1
				$nb_de_donnees = $tmp_donnee->sqlCountListing($dbh, $this->reverseDate($tmp_date_deb), $this->reverseDate($tmp_date_fin), 'count', $requeteTotale, $this->limit_export_sql);
				if (($this->limit_export_sql == -1) || ($nb_de_donnees <  $this->limit_export_sql))
				{
					// Définition de la variable de session indiquant le nombre de pages toutes requêtes confondues
					$session_page['nbDonneesTotal'] = $nb_de_donnees;
					$session_page['maxPage'] = ceil($nb_de_donnees/$limit);
				} else {
					$session_page['nbDonneesTotal'] = $nb_de_donnees;
				}
			}
			// Si des données sont trouvées
			if ($session_page['nbDonneesTotal'] != 0) {
				if (($this->limit_export_sql == -1) || ($session_page['nbDonneesTotal'] < $this->limit_export_sql)) {
					// Réinitilisation du tableau contenant les différentes requêtes à mettre en Union
					$tabUnionRequete = array();
					foreach ($liste_req_pour_listing as $key => $requete) {
						// Lors de la demande d'affichage de la dernière page : Recherche des données depuis Offset jusqu'à la fin de la période
						$limit_req = $limit;
						// Si la page demandée est > à la page max, la recherche des données retourne la valeur null
						if ($session_page['page'] > $session_page['maxPage']) {
							$tabDesDonnees = array();
						} else {
							$recherche = true;
							if (isset($_GET['impression']) && ($_GET['impression'] == 'yesCsv')) {
								// Nombre de lignes max d'une feuille excel : 1 048 576 ligne
								// L'export de plus de 10 000 données n'est pas autorisé
								if ($session_page['nbDonneesTotal'] > $this->limit_excel) {
									if (! isset($message_error)) {
										$message_error = "La requête comporte trop de données : ".$session_page['nbDonneesTotal'];
										$message_error_precision = "Nombre maximum de données autorisée pour l'exportation : ".$this->limit_excel;
										$this->get('session')->getFlashBag()->add('info',"$message_error");
										$this->get('session')->getFlashBag()->add('precision',"$message_error_precision");
										$recherche = false;
										$_GET['impression']	= 'no';
									}
								}
								$offset = 0;
								$limit_req = '18446744073709551615';
							}
							if ($recherche == true) {
								// Fonction permettant de récupérer la période minimum pour la localisation désignée
								$tabUnionRequete[] = $tmp_donnee->sqlParametresListing(
									$tabDesRequetes[$key]['id_localisation'],
									$tabDesRequetes[$key]['liste_id_modules'],
									$requete['codeVal1'],
									$requete['val1min'],
									$requete['val1max'],
									$requete['codeVal2'],
									$requete['val2min'],
									$requete['val2max']
								);
							}
						}
					}
					// Création de la requête Union des différentes requêtes
					$requeteTotale = null;
					if (! empty($tabUnionRequete)) {
						$requeteTotale = '('.$tabUnionRequete[0].')';
						for ($numRequete = 1; $numRequete < count($tabUnionRequete); $numRequete++) {
							$requeteTotale .= ' OR ';
							$requeteTotale .= '('.$tabUnionRequete[$numRequete].')';
						}
					}
					$tmp_date_deb = $this->getDatePeriode($session_date['datedebut'], $tabDesRequetes[$key]['id_localisation'], 'debut');
                    $tmp_date_fin = $this->getDatePeriode($session_date['datefin'], $tabDesRequetes[$key]['id_localisation'], 'fin');
					// Requête Sql : Recherche des données 
					$tabDesDonnees = $tmp_donnee->sqlAllLimitedOrdered($dbh, $this->reverseDate($tmp_date_deb), $this->reverseDate($tmp_date_fin), $requeteTotale, $limit_req, $offset);
					// Pour chaque donnée retournée par la requête
					// On indique le numéro de localisation correspondant à l'id de la localisation indiquée dans la donnée ( grâce à la variable $this->liste_localisations)
					// On indique les informations du module correspondant à l'id de module indiqué dans la donnée
					// On indique les informations du genre correspondant à l'id du genre récupéré dans les informations du module
					// Si l'identifiant du module ne fait pas partie de la liste des modules récupérés on inscrit des données par défaut
					// Ce cas peut se rencontrer lors de la recherche sur tous les messages
					// Si une erreur de requête s'est produite 
					if ($tabDesDonnees === null) {
						$this->get('session')->getFlashBag()->set('info',"Temps d'execution sql dépassé : Nombre de données trop élevé (".$session_page['nbDonneesTotal'].") Veuillez spécifier une autre période svp");
						$message_erreur = "Temps d'execution sql dépassé";
					} else {
						// Si aucune erreur de requête ne s'est produite
						foreach ($tabDesDonnees as $key2 => $recupdonnee) {
							$tabDesDonnees[$key2]['numero_localisation'] = $this->tab_conversion_loc_getnum[$recupdonnee['localisation_id']];
							//	Récupération du paramètre indiquant le nombre de chiffres après la virgule à afficher
							$nbDecimal = $configuration->SqlGetParam($dbh, 'arrondi');
							$tabDesDonnees[$key2]['valeur1'] = round($tabDesDonnees[$key2]['valeur1'], $nbDecimal);
							$tabDesDonnees[$key2]['valeur2'] = round($tabDesDonnees[$key2]['valeur2'], $nbDecimal);
							if(isset($correspondance_message_code[$recupdonnee['module_id']])) {
								$tabDesDonnees[$key2]['codeModule'] = $correspondance_message_code[$recupdonnee['module_id']];
							}
							// Module
							if (isset($this->tabModulesL[$recupdonnee['module_id']])) {
								$tabDesDonnees[$key2]['intitule_module'] = $this->remplaceSpecialChars($this->tabModulesL[$recupdonnee['module_id']]['intitule'], $recupdonnee['valeur1'], $recupdonnee['valeur2']);
								$tabDesDonnees[$key2]['message'] = $this->tabModulesL[$recupdonnee['module_id']]['message'];
								$tmp_genre_id = $this->tabModulesL[$recupdonnee['module_id']]['genre'];
								$tabDesDonnees[$key2]['intitule_genre']	= $this->tab_conversion_genre_id[$tmp_genre_id];
								$tabDesDonnees[$key2]['numero_genre'] = $this->tab_conversion_genre_num[$tmp_genre_id];
							} else{ 
								$entity_module_indefini = $this->em->getRepository('IpcProgBundle:Module')->find($recupdonnee['module_id']);
								$tabDesDonnees[$key2]['codeModule'] = $entity_module_indefini->getCategorie().$service_numbers->fillNumber($entity_module_indefini->getNumeroModule(), 2).$service_numbers->fillNumber($entity_module_indefini->getNumeroMessage(), 2);
								$tabDesDonnees[$key2]['intitule_module'] = $entity_module_indefini->getMessage();
								$tabDesDonnees[$key2]['erreur'] = "Aucune correspondance [ module " + $recupdonnee['module_id'] + " / localisation " + $tabDesDonnees[$key2]['numero_localisation'] + "] trouvée dans la table d'échange";
								$tabDesDonnees[$key2]['message'] = 'Id Module : '.$recupdonnee['module_id'];
								$genre_id_module_indefini = $entity_module_indefini->getGenre()->getId();
								$tabDesDonnees[$key2]['intitule_genre']	= $this->tab_conversion_genre_id[$genre_id_module_indefini];
								$tabDesDonnees[$key2]['numero_genre'] = $this->tab_conversion_genre_num[$genre_id_module_indefini];
							}
						}
						// Enregistrement des Données pour gestion du cas ou l'utilisateur raffraichit la page listing.html (par clic sur F5)
						$this->session->set('tabDesDonnees', $tabDesDonnees);
					}
				} else {
					$message_tmp = 'Le nombre de données touvées est trop élevé : > à '.$this->limit_export_sql." [limitation_export_sql_listing]<br /><br /> Veuillez spécifier une autre période svp";
					$message_tmp .= "<br /><br />La recherche peut tout de même être lancée mais le temps d'attente peut être long et la requête ne pas aboutir<br /><br />";
					$this->get('session')->getFlashBag()->set('info_a_valider',$message_tmp);
					$message_erreur = 'Nombre de données trop élevé ('.$session_page['nbDonneesTotal'].')';
					return $this->indexAction();
				}
			}
		} else {
			// Si la page affichée est rafraichie (clic sur F5)
			$tabDesDonnees = $this->session->get('tabDesDonnees');
		}
		if ($session_page['maxPage'] == 0) {
			//  Définition de la variable de session numéro de Page à 0 => Indique aucune donnée trouvée
			$session_page['page'] = 0;
		}
		$session_page['oldPage'] = $session_page['page']; 
		// Mise à jour de la variable de Session [ page / maxPage / oldPage ]
		$this->session->set('session_page', $session_page); 
		if (count($liste_req_pour_listing) == 0) {
			$dbh = $this->connexion->disconnect();
			if (isset($_GET['AJAX'])) {
				echo json_encode($liste_req);
				return new Response();
			}
		} else {
			$heure_fin = strtotime(date('Y-m-d h:i:s'));
			$tmp_de_traitement = $heure_fin - $heure_debut;
			// Si le temps de traitement est >= au temps maximum d'execution des requêtes : retour vers la page d'erreur
			$tempMax = null;
			if ($tmp_de_traitement > $maximum_execution_time) {
				$tempMax = 1;
			}
			// Si la demande concerne l'impression en mode csv: 
			if ((isset($_GET['impression'])) && ( ($_GET['impression'] == 'yesCsv') || ($_GET['impression'] == 'yesPCsv'))) {
				if (($_GET['impression'] == 'yesCsv') || ($_GET['impression'] == 'yesPCsv')) {
					// IMPRESSION CSV des données présentent dans liste_req
					$chemin = 'uploads/tmp/';
					$fichier = 'Listing_'.date('YmdHis').'.csv';
					$delimiteur = ';';
					$fichier_csv = fopen($chemin.$fichier, 'w+');
					// Correction des caractères spéciaux pour affichage du csv dans excel
					fprintf($fichier_csv,  chr(0xEF).chr(0xBB).chr(0xBF));
					// Lecture des données et enregistrement dans le fichier excel
					// Titre
					$tab_tmp = array($this->pageTitle['title']);
					fputcsv($fichier_csv, $tab_tmp, $delimiteur);
					// Période
					$tab_tmp = array($messagePeriode);
					fputcsv($fichier_csv, $tab_tmp, $delimiteur);
					// Date d'impression
					$tab_tmp = array("Export du ".date('d/m/Y H:i:s'));
					fputcsv($fichier_csv, $tab_tmp, $delimiteur);
					foreach ($liste_req_pour_listing as $key => $requete) {
						// Requête demandée
						$tab_tmp = array('Requête : '.$requete['Texte']);
						fputcsv($fichier_csv, $tab_tmp, $delimiteur);
						// Entête des colonnes
						$tab_tmp = array('Horodatage', 'Valeur1', 'Valeur2', 'Unite', 'Localisation', 'Genre', 'Code Module', 'Message Module');
						fputcsv($fichier_csv, $tab_tmp, $delimiteur);
						foreach ($tabDesDonnees as $key => $donnee) {
							$tab_tmp = array($donnee['horodatage'].','.$fillnumbers->fillNumber($donnee['cycle'], 3), $donnee['valeur1'], $donnee['valeur2'], $this->tabModulesL[$donnee['module_id']]['unite'], $this->tab_conversion_loc_num[$donnee['numero_localisation']], $donnee['intitule_genre'], $donnee['codeModule'], $donnee['message']);
							fputcsv($fichier_csv, $tab_tmp, $delimiteur);
						}
						$tab_tmp = array();
						fputcsv($fichier_csv, $tab_tmp, $delimiteur);
					}
					fclose($fichier_csv);
					$response = new Response();
					$response->headers->set('Content-Type', 'application/force-download');
					$response->headers->set('Content-Disposition', 'attachment;filename="'.$fichier.'"');
					$response->headers->set('Content-Length', filesize($chemin.$fichier));
					$response->setContent(file_get_contents($chemin.$fichier)); 
					$response->setCharset('UTF-8');
					$dbh = $this->connexion->disconnect();
					return $response;
				}
			}
			// ------------------- CREATION DU TABLEAU PERMETTANT LA MODIFICATION DES CARACTERES SPECIAUX $ ----------------------------------------------------//
			// Récupération des valeurs à affecter au caractère spécial $ en fonction de la valeur 1
			$tabIndicationValeur = array();
			$tabIndicationValeur[0] = $configuration->SqlGetParam($dbh, 'dollar_0');
			if ($tabIndicationValeur[0] == null) {
				$this->get('session')->getFlashBag()->add('info', 'Aucune valeur pour le parametre \'dollar_0\' n\'est renseignée : Veuillez vérifier la paramètre svp');
				return $this->redirect($this->generateUrl('ipc_prog_homepage'));
			} else {
				$tabIndicationValeur[0] = $tabIndicationValeur[0].' ';
			}
			$tabIndicationValeur[1]	= $configuration->SqlGetParam($dbh, 'dollar_1');
			if ($tabIndicationValeur[1] == null) {
				$this->get('session')->getFlashBag()->add('info', 'Aucune valeur pour le parametre \'dollar_1\' n\'est renseignée : Veuillez vérifier la paramètre svp');
				return $this->redirect($this->generateUrl('ipc_prog_homepage'));
			} else { 
				$tabIndicationValeur[1] = $tabIndicationValeur[1].' ';
			}
			// ------------------- FIN DE CREATION DU TABLEAU PERMETTANT LA MODIFICATION DES CARACTERES SPECIAUX $ ----------------------------------------------------//
			$dbh = $this->connexion->disconnect();
			// Sinon retour vers la page listing pour affichage des données
			// Tri du tableau selon la date et le cycle
			$datedonnee = array();
			$cycledonnee = array();
			foreach ($tabDesDonnees as $key => $donnee) {
				$datedonnee[$key] = $donnee['horodatage'];
				$cycledonnee[$key] = $donnee['cycle'];
			}
			array_multisort($datedonnee, SORT_ASC, $cycledonnee, SORT_ASC, $tabDesDonnees);
			return $this->render('IpcListingBundle:Listing:listing.html.twig', array(
				'activation_modbus' => $this->activation_modbus,
				'messagePeriode' => $messagePeriode,
				'messageErreur' => $message_erreur,
				'page' => $session_page['page'],
				'liste_req_pour_listing' => $liste_req_pour_listing,
				'liste_localisations' => $this->liste_localisations,
				'tabDesRequetes' => $tabDesDonnees,
				'maxpages' => $session_page['maxPage'],
				'nbDonneesTotal' => $session_page['nbDonneesTotal'],
				'tempMax' => $tempMax,
				'liste_genres' => $this->liste_genres,
				'tabIndicationValeur' => $tabIndicationValeur,
				'impression_listing' => $impression_listing,
            	'sessionCourante' => $this->session->getSessionName(),
                'tabSessions' => $this->session->getTabSessions()
			));
		}
	}
	$dbh = $this->connexion->disconnect();
	// - - - -- - - - - - - - - - -- - - - - --    PARTIE REMPLACEMENT du numéro de localisation par l'intitulé de la localisation pour l'affichage
	// - - - -- - - - - - - - - - -- - - - - --    FIN DE PARTIE REMPLACEMENT du numéro de localisation par l'intitulé de la localisation pour l'affichage
	$popup_simplifiee = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('popup_simplifiee')->getValeur();
	$response = new Response(
		$this->renderView('IpcProgBundle:Prog:popupMessages.html.twig', array(
			'page_appelante' => 'listing',
			'messagePeriode' => $messagePeriode,
			'liste_localisations' => $this->liste_localisations,
			'last_loc_id' => $this->last_loc_id,
			'liste_genres' => $this->liste_genres,
			'liste_nomsModules' => $this->liste_noms_modules,
			'liste_messagesModules' => $this->liste_messages_modules,
			'maximum_execution_time' => $maximum_execution_time,
			'popup_simplifiee' => $popup_simplifiee
		))
	);
    $response->setPrivate();
    $response->headers->addCacheControlDirective('no-cache', true);
    $response->headers->addCacheControlDirective('max-age', 0);
    $response->headers->addCacheControlDirective('must-revalidate', true);
    $response->setETag(md5($response->getContent()));
	return $response;
}


//	Fonction AJAX permettant de retourner les données du listing affiché triées
public function ajaxTrieDonneesAction() {
	$this->constructeur();
	$classement = $_POST['classement'];
	$ordre = $_POST['ordre'];
	$tabDesDonnees = $this->session->get('tabDesDonnees');
	// Tri du tableau selon la date et le cycle
	$datedonnee = array(); 
	$cycledonnee = array();
	switch ($classement) {
	case 'genre':
		foreach ($tabDesDonnees as $key=>$donnee) {
			$genredonnee[$key] = $donnee['intitule_genre'];
			$datedonnee[$key] = $donnee['horodatage'];
			$cycledonnee[$key] = $donnee['cycle'];
		}
		if ($ordre == 'ASC') {
			array_multisort($genredonnee, SORT_ASC, $datedonnee, SORT_ASC, $cycledonnee, SORT_ASC, $tabDesDonnees);
		} else {
			array_multisort($genredonnee, SORT_DESC, $datedonnee, SORT_ASC, $cycledonnee, SORT_ASC, $tabDesDonnees);
		}
		break;
	case 'code':
		foreach ($tabDesDonnees as $key=>$donnee) {
			$codedonnee[$key] = $donnee['codeModule'];
			$datedonnee[$key] = $donnee['horodatage'];
			$cycledonnee[$key] = $donnee['cycle'];
		}
		if ($ordre == 'ASC') {
			array_multisort($codedonnee, SORT_ASC, $datedonnee, SORT_ASC, $cycledonnee, SORT_ASC, $tabDesDonnees);
		} else {
			array_multisort($codedonnee, SORT_DESC, $datedonnee, SORT_ASC, $cycledonnee, SORT_ASC, $tabDesDonnees);
		}
		break;
	case 'localisation':
		foreach ($tabDesDonnees as $key=>$donnee) {
			$localisationdonnee[$key] = $donnee['numero_localisation'];
			$datedonnee[$key] = $donnee['horodatage'];
			$cycledonnee[$key] = $donnee['cycle'];
		}
		if ($ordre == 'ASC') {
			array_multisort($localisationdonnee, SORT_ASC, $datedonnee, SORT_ASC, $cycledonnee, SORT_ASC, $tabDesDonnees);
		} else {
			array_multisort($localisationdonnee, SORT_DESC, $datedonnee, SORT_ASC, $cycledonnee, SORT_ASC, $tabDesDonnees);
		}
		break;
	case 'valeur1':
		foreach ($tabDesDonnees as $key=>$donnee) {
			$valeur1donnee[$key] = $donnee['valeur1'];
			$datedonnee[$key] = $donnee['horodatage'];
			$cycledonnee[$key] = $donnee['cycle'];
		}
		if ($ordre == 'ASC') {
			array_multisort($valeur1donnee, SORT_ASC, $datedonnee, SORT_ASC, $cycledonnee, SORT_ASC, $tabDesDonnees);
		} else {
			array_multisort($valeur1donnee, SORT_DESC, $datedonnee, SORT_ASC, $cycledonnee, SORT_ASC, $tabDesDonnees);
		}
		break;
	case 'valeur2':
		foreach ($tabDesDonnees as $key=>$donnee) {
			$valeur2donnee[$key] = $donnee['valeur2'];
			$datedonnee[$key] = $donnee['horodatage'];
			$cycledonnee[$key] = $donnee['cycle'];
		}
		if ($ordre == 'ASC') {
			array_multisort($valeur2donnee, SORT_ASC, $datedonnee, SORT_ASC, $cycledonnee, SORT_ASC, $tabDesDonnees);
		} else {
			array_multisort($valeur2donnee, SORT_DESC, $datedonnee, SORT_ASC, $cycledonnee, SORT_ASC, $tabDesDonnees);
		}
		break;
	case 'horodatage':
		foreach ($tabDesDonnees as $key=>$donnee) {
			$datedonnee[$key] = $donnee['horodatage'];
			$cycledonnee[$key] = $donnee['cycle'];
		}
		if ($ordre == 'ASC') {
			array_multisort($datedonnee, SORT_ASC, $cycledonnee, SORT_ASC, $tabDesDonnees);
		} else {
			array_multisort($datedonnee, SORT_DESC, $cycledonnee, SORT_ASC, $tabDesDonnees);
		}
		break;
	}
	echo json_encode($tabDesDonnees);
	return new Response();
}


public function getMessageRequete($numLocalisation, $message_idModule, $module, $genre, $codeVal1, $val1min, $val1max, $codeVal2, $val2min, $val2max) {
	$this->constructeur();
	$message = '';
	if ($numLocalisation != 'all') {
		$message .= $this->tab_conversion_loc_num[$numLocalisation].' - ';
	}
	if (($message_idModule == 'all')&&($module == 'all')&&($genre == 'all')) {
		$message .= ' Tous les messages';
	}
	if ($message_idModule != 'all') {
		$message .= "Messages : [".$this->suppressionDesCaracteres($message_idModule)."] - ";
	} else {
		if ($module != 'all') { 
			$message .= "Module : [$module] - "; 
		}
		if($genre != 'all') {
			$message .= "Genre : [$genre] - "; 
		}
	}
	switch ($codeVal1) {
	case 'Sup':
		$message .= " (Valeur 1 inférieure à $val1min)";
		break;
	case 'Inf':
		$message .= " (Valeur 1 supérieure à $val1min)";
		break;
	case 'Int':
		$message .= " (Valeur 1 comprise entre $val1min et $val1max)";
		break;
	}
	switch ($codeVal2) {
	case 'Sup':
		$message .= " (Valeur 2 inférieure à $val2min)";
		break;
	case 'Inf':
		$message .= " (Valeur 2 supérieure à $val2min)";
		break;
	case 'Int' :
		$message .= " (Valeur 2 comprise entre $val2min et $val2max)";
		break;
	}
	return($message);
}

private function getDatePeriode($dateAnalyse, $localisationId, $type) {
	$tabPeriode = $this->session->get('infoLimitePeriode', array());
	if ($tabPeriode == null){
		$this->setInfoLimitePeriode();
		$tabPeriode = $this->session->get('infoLimitePeriode', array());
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

//  Fonction qui permet de définir la variable de session infoLimitePeriode
public function setInfoLimitePeriode() {
    // Définition du tableau des périodes d'analyse
    // Récupération du tableau de limitation des requêtes en fonction des périodes d'analyse
    // Récupération des localisations du site courant
    $site = $this->em->getRepository('IpcProgBundle:Site')->myFindCourant();
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
    return(0);
}


private function checkDeDate($date_deb, $date_fin) {
	$timestamp_dateDeb = strtotime($this->reverseDate($date_deb));
	$timestamp_dateFin = strtotime($this->reverseDate($date_fin));
	if ($timestamp_dateDeb > $timestamp_dateFin) {
		return(false);
	}
	return(true);
}

// Fonction qui supprime les caractères spéciaux dans les messages des modules
public function suppressionDesCaracteres($chaine) {
	$this->constructeur();
	//	Suppression des $ et =$ des messages
	$pattern = '/=?\s?\$/';
	$replacement = '';
	$chaine = preg_replace($pattern, $replacement, $chaine);
	return($chaine);
}

// Fonction qui remplace les caractères spéciaux $ et £ par leur valeur respectif
private function remplaceSpecialChars($message, $valeur1, $valeur2) {
	$pattern = '/£/';
	$replacement = $valeur2;
	return (preg_replace($pattern, $replacement, $message));
}

private function getIdSiteCourant($dbh) {
    $site = new Site();
    $idSiteCourant = $site->SqlGetIdCourant($dbh);
    return ($idSiteCourant);
}

// Fonction qui change la liste des requêtes après selection d'une requête enregistrée
public function changeListeReqAction() {
       $this->constructeur();
       // On recherche la requête perso à afficher
       $id_requete = $this->session->get('listing_requete_selected');
       $ent_requete = $this->em->getRepository('IpcConfigurationBundle:Requete')->find($id_requete);
       // On modifie la variable de session avec la liste des requêtes de la requête perso
       $this->session->set('liste_req', json_decode($ent_requete->getRequete(), true));
       // On renvoi la page d'accueil de Listing
       return $this->redirect($this->generateUrl('ipc_accueilListing'));
}
}
