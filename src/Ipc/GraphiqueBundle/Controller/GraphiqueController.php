<?php
//src/Ipc/GraphiqueBundle/Controller/GraphiqueController

namespace Ipc\GraphiqueBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
//	Pour l'utilisation de createQueryBuilder
use Doctrine\ORM\EntityRepository;
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




class GraphiqueController extends Controller {

private $tabModulesG;	 		//	Tableau dans lequel on place les valeurs de la variable de session  $session->set('tabModules', $this->tabModulesG);
private $fichierLog;
private $limit;
private $limit_export_sql;			//	Limitation par requete d'exportation (si nb points > a cette limite les variables php ne peuvent plus traiter les données
private $limit_excel;			//	Limitation par fichier excel
private $session;
private $pageTitle;
private $liste_localisations;
private $tab_conversion_loc_id;
private $tab_last_horodatage_loc_id;
private $tab_conversion_genre_id;
//private $tab_conversion_idmodule_numgenre;
private $tab_conversion_message_id;
private $liste_genres;
private $liste_modules;
private $liste_messages_modules;
private $liste_noms_modules;
private $dbh;
private $em;
private $connexion;
private $messagePeriode;
private $last_loc_graph_id;
private $limite_multi_requetes;
private $activation_modbus;
// Indique le compte des requêtes affichées. Permet de cocher ou décocher la checkbox 'Requêtes cliente'
private $compteRequetePerso;
private $translator;
private $s_transforme_texte;
private $s_compression_graphique;
private $entities_requetes_perso;



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
	$this->s_transforme_texte = $this->get('ipc_prog.transformeTexte');

	$this->dbh = $this->connexion->getDbh();
	$this->pageTitle = $this->session->get('pageTitle');
	$this->fichierLog = 'importBin.log';
	$this->tabModulesG = array();
	// Définie la limite après laquelle la recherche est divisée par tranche de 3 jours
	$this->limite_multi_requetes = 8000;
	// Définie la limite après laquelle une compression des données est demandée.
	$this->limit = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('graphique_max_points');
	if ($this->limit) {
		$this->limit = $this->limit->getValeur();
	}
	$this->limit_export_sql = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('limitation_export_sql_graphique')->getValeur();
	$this->limit_excel = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('limitation_excel_graphique')->getValeur();

    $this->translator = $this->get('translator');
    $this->messagePeriode = $this->translator->trans('periode.info.none');
	$this->activation_modbus = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('activation_modbus')->getValeur();
	// Récupération de l'indication de recherche de tous les points ou de compression
	$this->s_compression_graphique = $this->session->get('compression_graphique', array());

}

private function getRequetesPerso() {
	$this->constructeur();
	// Variable qui indique si les recherches concernent le compte client ou le compte utilisateur courant
	$this->compteRequetePerso = $this->session->get('compte_requete_perso','');
    if ($this->compteRequetePerso) {
      	if ($this->compteRequetePerso != 'Personnel') {
            $entities_requetes_perso = $this->em->getRepository('IpcConfigurationBundle:Requete')->myFindByCompte($this->compteRequetePerso, 'graphique');
        } else {
            $this->compteRequetePerso = 'Personnel';
            // Recherche de l'appelation des requêtes de l'utilisateur
         	$entities_requetes_perso = $this->em->getRepository('IpcConfigurationBundle:Requete')->myFindByCreateur($this->session->get('label'), 'graphique');
		}
    } else {
		if ($this->get('security.context')->isGranted('ROLE_TECHNICIEN')) {
			$this->compteRequetePerso = 'Personnel';
			// Recherche de l'appelation des requêtes de l'utilisateur
			$entities_requetes_perso = $this->em->getRepository('IpcConfigurationBundle:Requete')->myFindByCreateur($this->session->get('label'), 'graphique');
		} else if ($this->get('security.context')->isGranted('ROLE_CLIENT')) {
			$this->compteRequetePerso = 'Client';
			// Recherche de l'appelation des requêtes de l'utilisateur
			$entities_requetes_perso = $this->em->getRepository('IpcConfigurationBundle:Requete')->myFindByCompte($this->compteRequetePerso, 'graphique');
		}
    }
    return ($entities_requetes_perso);
}


// Retourne le répertoire des logs
protected function getLogDir() {
	return __DIR__.'/../../../../web/logs/'; 
}

// Met un message en log
public function setLog($message) {
	$this->initialisation();
	$ficlog = $this->getLogDir().$this->fichierLog;
	$message = date("d/m/Y;H:i:s;").$message."\n";
	$fp	= fopen($ficlog, "a");
	fwrite($fp, $message);
	fclose($fp);
}

// Fonction qui recoit une date en entrée et inverse l'annee et le jour pour recherche Sql
public function reverseDate($horodatage) {
	$this->constructeur();
	$pattern = '/^(\d{2})([-\/]\d{2}[-\/])(\d{4})(.+?)$/';
	if (preg_match($pattern, $horodatage, $tabdate)) {
		$retour_heure = $tabdate[3].$tabdate[2].$tabdate[1].$tabdate[4];
		return ($retour_heure);
	}
	return($horodatage);
}


public function initialisationListes() {
	$dbh = $this->dbh;
	$liste_genres_en_base = null;
	$liste_genres = null;
	$liste_modules = null;
	$liste_messages_modules = array();
	$liste_noms_modules = array();
	$correspondance_message_code = array();
	$tab_conversion_loc_id = array();
	$tab_conversion_genre_id = array();
	//$tab_conversion_idmodule_numgenre = array();
	$tab_conversion_message_id = array();


	// Initialisation des listes de localisation
	$this->container->get('ipc_prog.session.boilerbox')->definirListeLocalisationsCourantes();

    $this->liste_localisations = $this->session->get('tablocalisations');
	if ($this->liste_localisations == null) {
		$this->get('session')->getFlashBag()->add('info', "Aucune Localisation définie pour le site courant");
		return false;
	}
	// Initialisation d'un tableau de converion des localisations permettant d'afficher la désignation d'une localisation selon son id
	foreach ($this->liste_localisations as $key => $localisation) {
		$this->tab_last_horodatage_loc_id[$localisation['id']] = $localisation['last_horodatage'];
		$this->tab_conversion_loc_id[$localisation['id']] = $localisation['designation'];
	}
	// Récupération de la dernière localisation entrée pour la réafficher par défaut dans la popup
	$this->last_loc_graph_id = $this->session->get('last_loc_graph_id');
	// Si il n'y a pas eu de requête enregistrée, la localisation par défaut est la première de la liste
	if (empty($this->last_loc_graph_id)) {
		$this->last_loc_graph_id = $this->liste_localisations[0]['id'];
	}



    // Initialisation de la liste des genres autorisés
	$this->container->get('ipc_prog.session.boilerbox')->definirListeDesGenres();

    $this->liste_genres = $this->session->get('session_genreg_autorise');
    //   Tableau qui indique l'intitulé du genre selon son id
    foreach ($this->liste_genres as $key => $genre) {
        $this->tab_conversion_genre_id[$genre['id']] = $genre['intitule_genre'];
        $this->tab_conversion_genre_num[$genre['id']] = $genre['numero_genre'];
    }


    // Initialisation de la liste des modules
	$this->container->get('ipc_prog.session.boilerbox')->definirTabModuleL();

    $this->tabModulesG = $this->session->get('tabModules');
    if ($this->tabModulesG == null) {
        $this->get('session')->getFlashBag()->add('info', "Graphique : Aucun module n'est associé aux localisations du site courant : Veuillez importer la/les table(s) d'échanges ou modifier le paramètre popup_simplifiee");
        return false;
    }
    $correspondance_message_code = $this->session->get('correspondance_Message_Code');

	$liste_messages_modules = $this->session->get('liste_messages_modules_graphique');
	$liste_noms_modules	= $this->session->get('liste_nom_modules_graphique');
	$tab_conversion_message_id = $this->session->get('tab_conversion_message_id_graphique');
	if ((empty($liste_messages_modules)) || empty($liste_noms_modules) || empty($tab_conversion_message_id)) {
		if (count($this->liste_localisations) > 1) {
			$localisation_id = $this->last_loc_graph_id;
			foreach ($this->tabModulesG as $key => $module) {
				if (in_array($localisation_id, $module['localisation'])) {
					// Création d'un tableau pour éviter de présenter des doublons dans les intitulés des modules
					if (! in_array($module['intitule'], $liste_noms_modules)) {
						array_push($liste_noms_modules, $module['intitule']);
					} 
					$liste_messages_modules[$key] = $correspondance_message_code[$key]." - ".$this->suppressionDesCaracteres($module['message']);
					$this->tab_conversion_message_id[$key] = $module['message'];
				}
			}
		} else {
			// Création des tableaux des intitulés de module
			//                       et des messages de modules
			foreach ($this->tabModulesG as $key => $module) {
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
		$this->liste_messages_modules = $liste_messages_modules;
		// Ajout d'une variable de session afin de permettre une recherche des messages par recherche direct
		$this->session->set('liste_messages_modules_graphique', $liste_messages_modules);
		// Ajout d'une variable de session qui stock la liste des modules afin d'éviter des faire la boucle de parcours de modules une fois la variable crée
		$this->session->set('liste_noms_modules_graphique', $liste_noms_modules);
		// Ajout de la variable de session qui stock les correspondances idModule => message
		$this->session->set('tab_conversion_message_id_graphique', $this->tab_conversion_message_id);
		return(true);
	} else {  
		$this->liste_noms_modules = $liste_noms_modules;
		$this->liste_messages_modules = $liste_messages_modules;
		$this->tab_conversion_message_id = $tab_conversion_message_id;
	}
	return(true);
}



public function indexAction() {
	$this->initialisation();
	$autorisation_acces	= $this->initialisationListes();
	if ($autorisation_acces == false) {
		return $this->redirect($this->generateUrl('ipc_prog_homepage'));
	}
	if (! $this->limit) {
		$this->get('session')->getFlashBag()->set('info', "Variable de configuration [graphique_max_points] non définie. Accés au module graphique non possible");
		$this->get('session')->getFlashBag()->set('precision', "Veuillez contacter votre administrateur");
		return $this->redirect($this->generateUrl('ipc_prog_homepage'));
	}
	set_time_limit(0);
	// Réinitialisation des valeurs des requêtes graphiques
	// Sinon la recherche Count n'est pas réeffectuée : Erreur lorsque le nombre de données correspond au nombre de données après un Zoom et non pas au nombre de données de la période
	$liste_req = $this->session->get('liste_req_pour_graphique');
	foreach ($liste_req as $key => $requete) {
		$liste_req[$key]['NbDonnees'] =  null;
		$liste_req[$key]['MaxDonnees'] =  null;
		$liste_req[$key]['TexteRecherche'] = null;
	}
	$this->session->set('liste_req_pour_graphique', $liste_req);
	$correspondance_message_code = $this->session->get('correspondance_Message_Code');
	// Dates de début et de fin 
	$session_date = $this->session->get('session_date');
	$messagePeriode	= $this->messagePeriode;
	if (! empty($session_date)) {
		$messagePeriode = $session_date['messagePeriode'];
	}
	// Service de mise en norme du format des chiffres, des dates
	$fillnumbers = $this->get('ipc_prog.fillnumbers');
	// Etablissement de la connexion et récupération du pointeur permettant de manipuler les requêtes Sql
	$dbh = $this->dbh;
	// Temps maximum d'execution des requêtes avant kill
	$configuration = new Configuration();
	$maximum_execution_time = $configuration->SqlGetParam($dbh, 'maximum_execution_time');
	$heure_debut = strtotime(date('Y-m-d H:i:s'));
	// Service des requêtes entrantes
	$request = $this->get('request');
	// Récupération des variables de session	
	// Liste des requêtes demandées par l'utilisateur
	$liste_req = $this->session->get('liste_req_pour_graphique');
	// Distinction entre 'Lancer la recherche' OU 'Ajouter une requête de recherche'
	$submit = isset($_GET["choixSubmit"])?$_GET["choixSubmit"]:null;
	// 	La requête d'appelle de la fonction provient du formulaire de la page (index.html) OU de la page (graphique.html)
	if ($submit != null) {
		// Nombre de données à afficher par page
		$limit = $this->limit;
		if ($submit == 'suppressionRequete') {
			// Récupération du numero de requete à supprimer
			$tabModifRequete = explode('_', $_GET['suppression_requete']);
			$idRequeteASup = $tabModifRequete[1];
			// Si il y a plus d'une requête : Suppression de la requête Sinon réinitialisation de la variable
			if (count($liste_req) == 1) {
				$this->session->remove('liste_req_pour_graphique');
				$liste_req = $this->session->get('liste_req_pour_graphique');
			} else {
				unset($liste_req[$idRequeteASup]);
			}
			// Réorganisation du tableau
			$liste_req = array_filter($liste_req);
			$liste_req = array_values($liste_req);
			$this->session->set('liste_req_pour_graphique', $liste_req);
		} elseif (($submit != 'RAZ') && ($submit != null)) {
			// Si une modification de requête est demandée : Suppression de l'ancienne requête et création d'une nouvelle requête
			if ($_GET['modificationRequete'] != null) {
				$idRequeteASup = $_GET['modificationRequete'];
				if (count($liste_req) == 1) {
					$this->session->remove('liste_req_pour_graphique');
					$liste_req = $this->session->get('liste_req_pour_graphique');
				} else {
					unset($liste_req[$idRequeteASup]);
					$liste_req = array_filter($liste_req);
					$liste_req = array_values($liste_req);
				}
				$this->session->set('liste_req_pour_graphique', $liste_req);
			}
			// Si la requête est sans distinction de site ou de localisation : Mise à every du paramètre localisations
			$localisations = $_GET['listeLocalisations'];
			// Si la localisation différe de la précédente localisation : Réinitialisation des variables de session utilisées par la popup
			if ($localisations != $this->last_loc_graph_id) {
				$this->session->set('liste_messages_modules_graphique', array());
				$this->session->set('liste_nom_modules_graphique', array());
				$this->session->set('tab_conversion_message_id_graphique', array());
				// On enregistre la dernière localisation entrée pour faciliter l'utilisation de la popup
				$this->session->set('last_loc_graph_id', $localisations);
			}
			$modules = $_GET['listeModules'];
			$idGenre = $_GET['listeGenres'];
			$idModules = $_GET['listeIdModules'];
			$val1min = null;
			$val1max = null;
			// Indique quel type de recherche sur la valeur 1 est demandé (Supérieur/ Inférieur/ Interval)
			$codeVal1 = $_GET['codeVal1'];
			if($codeVal1 != 'None') {
				$val1min = (int)$_GET['codeVal1Min'];
				if ($codeVal1 == 'Int') {
					$val1max = (int)$_GET['codeVal1Max'];
				}
			}
			$val2min = null;
			$val2max = null;
			// Indique quel type de recherche sur la valeur 2 est demandé (Supérieur/ Inférieur/ Interval)
			$codeVal2 = $_GET['codeVal2'];
			if ($codeVal2 != 'None') {
				$val2min = (int)$_GET['codeVal2Min'];
				if ($codeVal2  == 'Int') {
					$val2max 	= (int)$_GET['codeVal2Max'];
				}
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
			// Recupération de la liste des modules : Tableau : 	IdModule => 'intitule'	-> intitulé de la famille de module
			// => 'message' 	-> message du module
			// => 'genre' 	-> id du genre du module
			$this->liste_message = $this->session->get('tabModules');
			// info : Lors de la sélection d'un message, c'est l'id du module qui est retourné par la page index.html
			// Recherche du message et du numéro de genre associé à l'identifiant de module
			$message_idModule = null;
			$numGenre_idModule = null;
			// Un identifiant de module est obligatoirement passé en paramètre
			foreach ($this->liste_message as $key => $message) {
				if ($key == $idModules) {
					$message_idModule = $correspondance_message_code[$idModules].'_'.$message['message'];
					//$numGenre_idModule = $this->tab_conversion_idmodule_numgenre[$this->tabModulesG[$idModules]['genre']];
					$numGenre_idModule = $this->tab_conversion_genre_num[$this->tabModulesG[$idModules]['genre']];
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
			//	____________________________________________________________________________________________________

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
				// Vérification que le nombre de requêtes ne dépasse pas la limite définie par le parametre 'graphique_nbmax_requetes' pour les droits TECHNICIEN
				// et par le parametre 'autorisation_graphique_nbmax_requetes' pour les droits CLIENT
				$message_error = null;
				$message_error_precision = null;
				// Pour les utilisateurs n'ayant pas les droits d'administrateurrs : Vérification du nombre de requêtes maximum autorisé
				if (! $this->get('security.context')->isGranted('ROLE_SUPERVISEUR')) {
					if ($this->get('security.context')->isGranted('ROLE_TECHNICIEN')) {
						$param_de_conf = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('graphique_nbmax_requetes');
						if (! $param_de_conf) {
							$message_error = "Nombre de requêtes autorisé non défini.";
							$message_error_precision = "Veuillez renseigner le paramètre 'graphique_nbmax_requetes' svp.";
						}
					} elseif ($this->get('security.context')->isGranted('ROLE_USER')) {
						$param_de_conf = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('autorisation_graphique_nbmax_requetes');
						if (! $param_de_conf) {
							$message_error = "Nombre de requêtes autorisé non défini.";
							$message_error_precision = "Veuillez renseigner le paramètre 'autorisation_graphique_nbmax_requetes' svp.";
						}
					}
				}
				if ($message_error != null) {
					$this->get('session')->getFlashBag()->add('info', "$message_error");
					$this->get('session')->getFlashBag()->add('precision', "$message_error_precision");
				} else {
					$message_error = null;
					// Récupération du nombre de requêtes demandées = Nombre de requêtes déjà enregistré + Nouvelle requête demandée
					// Modification du 23/04/2015 : Plus de restriction limite du nombre de requête
					$tmpNumeroDeRequete = count($liste_req);
					if ($message_error != null) {
						$this->get('session')->getFlashBag()->add('info', "$message_error");
					} else {
						// Sauvegarde des paramètres de la demande
						$liste_req[$tmpNumeroDeRequete]['numeroGenre'] = $numGenre_idModule;
						$liste_req[$tmpNumeroDeRequete]['id_localisations']	= $localisations;
						$liste_req[$tmpNumeroDeRequete]['idModule']	= $idModules;
						$liste_req[$tmpNumeroDeRequete]['codeVal1']	= $codeVal1;
						$liste_req[$tmpNumeroDeRequete]['val1min'] = $val1min;
						$liste_req[$tmpNumeroDeRequete]['val1max'] = $val1max;
						$liste_req[$tmpNumeroDeRequete]['codeVal2']	= $codeVal2;
						$liste_req[$tmpNumeroDeRequete]['val2min'] = $val2min;
						$liste_req[$tmpNumeroDeRequete]['val2max'] = $val2max;
						// Message de la requête
						$liste_req[$tmpNumeroDeRequete]['Texte'] = $messageTemporaire;
						$liste_req[$tmpNumeroDeRequete]['Localisation']	= $tmp_numero_localisation;
						// Nombre Maximum de pages que la requête retourne
						$liste_req[$tmpNumeroDeRequete]['MaxDonnees'] = null;
						// Indique si tous les points sont récupérés
						$liste_req[$tmpNumeroDeRequete]['AllPoints'] = false;
						// Données remontée par la requête Sql
						$liste_req[$tmpNumeroDeRequete]['Donnees'] = array();
						$liste_req[$tmpNumeroDeRequete]['NbDonnees'] = 0;
						// Texte indiquant la recherche effectuée / All /Moyenne à l'heure/ A la minute etc.
						$liste_req[$tmpNumeroDeRequete]['TexteRecherche'] = null;
						$liste_req[$tmpNumeroDeRequete]['precisionInit'] = null;
						$liste_req[$tmpNumeroDeRequete]['choixRechercheInit'] = null;
						$liste_req[$tmpNumeroDeRequete]['precision'] = null;
						$liste_req[$tmpNumeroDeRequete]['choixRecherche'] = null;
						$liste_req[$tmpNumeroDeRequete]['validation'] = false;
						// Redéfinition de la variable de session avec la nouvelle demande
						$this->session->set('liste_req_pour_graphique', $liste_req);
					}
				}
			}
		} elseif ($submit == 'RAZ') {
			// Lors du clic sur RAZ : Suppression des variables de session
			$this->session->remove('liste_req_pour_graphique');
			$liste_req = $this->session->get('liste_req_pour_graphique');
		}
	} else {
		$liste_req = $this->session->get('liste_req_pour_graphique');
		foreach ($liste_req as $key => $requete) {
			$liste_req[$key]['MaxDonnees'] = null;
			$liste_req[$key]['AllPoints'] = null;
			$liste_req[$key]['NbDonnees'] = null;
			$liste_req[$key]['Donnees'] = array();
			$liste_req[$key]['TexteRecherche'] = null;
			$liste_req[$key]['precisionInit'] = 'none';
			$liste_req[$key]['choixRechercheInit'] = 'all';
			$liste_req[$key]['precision'] = 'none';
			$liste_req[$key]['choixRecherche'] = 'all';
			$liste_req[$key]['validation'] = false;
		}
		$this->session->set('liste_req_pour_graphique', $liste_req);
	}
	$dbh = $this->connexion->disconnect();
	$heure_fin = strtotime(date('Y-m-d H:i:s'));
	$tmp_de_traitement = $heure_fin - $heure_debut;
	// Si le temps de traitement est >= au temps maximum d'execution des requêtes : retour vers la page d'erreur
	$tempMax = null;
	if ($tmp_de_traitement > $maximum_execution_time) {
		$tempMax = 1;
	}
	//  - - - -- - - - - - - - - - -- - - - - --    PARTIE REMPLACEMENT du numéro de localisation par l'intitulé de la localisation pour l'affichage
	// Pour chaque courbes, si une Localisation est définie, remplacement du numéro par l'intitulé
	// Création du tableau de conversion : N°localisation => Intitulé
	foreach ($this->liste_localisations as $key => $localisation) {
		$tabConversionLoc[$localisation['numero_localisation']] = $localisation['designation'];
	}
	foreach ($liste_req as $key => $requete) {
		$pattern='/Localisation : \[(.+?)\]/';
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
		$this->get('session')->getFlashBag()->add('info', "Graphique : Aucun module défini pour les localisations du site courant : Veuillez importer la/les table(s) d'échanges");
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
		$num_requete ++;
	}
	if (isset($_GET['AJAX'])) {
		echo json_encode($tab_requetes);
		return new Response();
	} else {
		$ent_requete = new Requete();
		$ent_requete->setCreateur($this->session->get('label'));
		$ent_requete->setType('graphique');
		$form_requete = $this->createForm(new RequeteType(), $ent_requete, [
			'action' => $this->generateUrl('ipc_accueilGraphique'),
			'method' => 'POST'
			]
		);
		// Récupération de la requête
		$request = $this->get('request');
		// Récupération du handler de formulaire
  		$form_handler = new RequeteHandler($form_requete, $request);
       	// Execution de la méthode d'execution du handler : Retourne True si les données du formulaire sont validées
        $process = $form_handler->process($this->em, 'graphique', $this->container->get('security.context')->getToken()->getUser(), $this->session);
		// Récupération de l'id de la requête personnelle
		$id_requete_perso = $this->session->get('graphique_requete_selected', null);
		$this->entities_requetes_perso = $this->getRequetesPerso();


		return $this->render('IpcGraphiqueBundle:Graphique:index.html.twig', array(
			'messagePeriode' 		=> $messagePeriode,
			'liste_req'				=> $liste_req,
			'tab_requetes' 			=> $tab_requetes,
			'strTab_requetes' 		=> json_encode($tab_requetes),
			'liste_localisations' 	=> $this->liste_localisations,
			'liste_genres' 			=> $this->liste_genres,
			'liste_nomsModules' 	=> $this->liste_noms_modules,
			'liste_messagesModules' => $this->liste_messages_modules,
			'maximum_execution_time'=> $maximum_execution_time,
			'tempMax' 				=> $tempMax,
            //'tab_requetes_perso' => $this->tabRequetesPerso,
			'entities_requetes_perso' => $this->entities_requetes_perso,
			'id_requete_perso'      => $id_requete_perso,
			'compte_requete_perso' => $this->compteRequetePerso,
		    'sessionCourante' => $this->session->getSessionName(),
            'tabSessions' => $this->session->getTabSessions(),
			'form_requete'          => $form_requete->createView()
		));
	}
}

//	Fonction qui peut être appelée en AJAX
public function afficheGraphiqueAction($page) {
	$this->initialisation();
	$autorisation_acces = $this->initialisationListes();
	set_time_limit(0);
	// Remise à 0 des valeurs de NbDonnees des requêtes graphiques
	// Sinon la recherche Count n'est pas réeffectuée : Erreur lorsque le nombre de données correspond au nombre de données après un Zoom et non pas au nombre de données de la période
	$liste_req = $this->session->get('liste_req_pour_graphique');
	foreach ($liste_req as $key => $requete) {
		$liste_req[$key]['NbDonnees'] =  null;
		$liste_req[$key]['MaxDonnees'] =  null;
		$liste_req[$key]['TexteRecherche'] = null;
	}
	$this->session->set('liste_req_pour_graphique', $liste_req);
	$correspondance_message_code = $this->session->get('correspondance_Message_Code');
	$session_date = $this->session->get('session_date');
	$messagePeriode = $this->messagePeriode;
	if (! empty($session_date)) {
		$messagePeriode = $session_date['messagePeriode'];
	}
	// Service de mise en norme du format des chiffres, des dates
	$fillnumbers = $this->get('ipc_prog.fillnumbers');
	// Etablissement de la connexion et récupération du pointeur permettant de manipuler les requêtes Sql
	$dbh = $this->dbh;
	// Temps maximum d'execution des requêtes avant kill
	$configuration = new Configuration();
	$maximum_execution_time	= $configuration->SqlGetParam($dbh, 'maximum_execution_time');
	$nbDecimal = $configuration->SqlGetParam($dbh, 'arrondi');
	$heure_debut = strtotime(date('Y-m-d H:i:s'));
	// Service des requêtes entrantes
	$request = $this->get('request');
	// Récupération des variables de session
	// Liste des requêtes demandées par l'utilisateur
	$liste_req = $this->session->get('liste_req_pour_graphique');
	// Distinction entre 'Lancer la recherche' OU 'Ajouter une requête de recherche'
	$submit = isset($_GET["choixSubmit"]) ? $_GET["choixSubmit"] : null;
	// La requête d'appelle de la fonction provient du formulaire de la page (index.html) OU de la page (graphique.html)
	// Si submit est null c'est que la page a été raffraichie par clic sur F5 -> Pas de traitement necessaire du formulaire
	if ($submit != null) {
		// Nombre de données à afficher par page
		$limit = $this->limit;
		if ($submit == 'suppressionRequete') {
			// Récupération du numero de requête à supprimer
			$tabModifRequete = explode('_', $_GET['suppression_requete']);
			$idRequeteASup = $tabModifRequete[1];
			// Si il y a plus d'une requête : Suppression de la requête Sinon réinitialisation de la variable
			if (count($liste_req) == 1) {
				$this->session->remove('liste_req_pour_graphique');
				$liste_req = $this->session->get('liste_req_pour_graphique');
			} else {
				unset($liste_req[$idRequeteASup]);
			}
			// Réorganisation du tableau
			$liste_req = array_filter($liste_req);
			$liste_req = array_values($liste_req);
			$this->session->set('liste_req_pour_graphique', $liste_req);
		} elseif($submit != 'RAZ') {
			$idLocalisation = $_GET['listeLocalisations'];
			// Si la localisation différe de la précédente localisation : Réinitialisation des variables de session utilisées par la popup
			if ($idLocalisation != $this->last_loc_graph_id) {
				$this->session->set('liste_messages_modules_graphique', array());
				$this->session->set('liste_nom_modules_graphique', array());
				$this->session->set('tab_conversion_message_id_graphique', array());
				// On enregistre la dernière localisation entrée pour faciliter l'utilisation de la popup
				$this->session->set('last_loc_graph_id', $idLocalisation);
			}
			$modules = $_GET['listeModules'];
			$idGenre = $_GET['listeGenres'];
			$idModules = $_GET['listeIdModules'];
			$val1min = null;
			$val1max = null;
			// Indique quel type de recherche sur la valeur 1 est demandé (Supérieur/ Inférieur/ Interval)
			$codeVal1 = $_GET['codeVal1'];
			if ($codeVal1  != 'None') {
				$val1min = (int)$_GET['codeVal1Min'];
				if ($codeVal1  == 'Int') {
					$val1max = (int)$_GET['codeVal1Max'];
				}
			}
			$val2min = null;
			$val2max = null;
			// Indique quel type de recherche sur la valeur 2 est demandé (Supérieur/ Inférieur/ Interval)
			$codeVal2 = $_GET['codeVal2'];
			if ($codeVal2 != 'None') {
				$val2min = (int)$_GET['codeVal2Min'];
				if ($codeVal2  == 'Int') {
					$val2max = (int)$_GET['codeVal2Max'];
				}
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
			// Recupération de la liste des modules : Tableau :        IdModule => 'intitule'  -> intitulé de la famille de module
			//                                                                  => 'message'   -> message du module
			//                                                                  => 'genre'     -> id du genre du module
			$this->liste_message = $this->session->get('tabModules');
			// info : Lors de la selection d'un message, c'est l'id du module qui est retourné par la page index.html
			// Recherche du message associé à l'identifiant de module
			$message_idModule = null;
			$numGenre_idModule = null;
			foreach ($this->liste_message as $key => $message) {
				if ($key == $idModules) {
					$message_idModule = $correspondance_message_code[$idModules].'_'.$message['message'];
					//$numGenre_idModule = $this->tab_conversion_idmodule_numgenre[$this->tabModulesG[$idModules]['genre']];
					$numGenre_idModule = $this->tab_conversion_genre_num[$this->tabModulesG[$idModules]['genre']];
				}
			}
			// Si une localisation est demandée Récupération du numéro de localisation
			$tmp_numero_localisation = null;
			if (($localisations != 'all')&&($localisations != 'every')) {
				foreach ($this->liste_localisations as $key => $localisation) {
					if ($localisation['id'] == $localisations) {
						$tmp_numero_localisation = $localisation['numero_localisation'];
					}
				}
			} else { 
				$tmp_numero_localisation = $localisations;
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
				// Vérification que le nombre de requêtes ne dépasse pas la limite définie par le parametre 'graphique_nbmax_requetes' pour les droits TECHNICIEN
				// et par le parametre 'autorisation_graphique_nbmax_requetes' pour les droits CLIENT
				$message_error = null;
				$message_error_precision = null;
				// Pour les utilisateurs n'ayant pas les droits d'administrateurrs : Vérification du nombre de requêtes maximum autorisé
				if (! $this->get('security.context')->isGranted('ROLE_SUPERVISEUR')) {
					if ($this->get('security.context')->isGranted('ROLE_TECHNICIEN')) {
						$param_de_conf = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('graphique_nbmax_requetes');
						if (! $param_de_conf) {
							$message_error = "Nombre de requêtes autorisé non défini.";
							$message_error_precision = "Veuillez renseigner le paramètre 'graphique_nbmax_requetes' svp.";
						}
					} elseif ($this->get('security.context')->isGranted('ROLE_USER')) {
						$param_de_conf = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('autorisation_graphique_nbmax_requetes');
						if (! $param_de_conf) {
							$message_error = "Nombre de requêtes autorisé non défini.";
							$message_error_precision = "Veuillez renseigner le paramètre 'autorisation_graphique_nbmax_requetes' svp.";
						}
					}
				}
				if ($message_error != null) {
					$this->get('session')->getFlashBag()->add('info', "$message_error");
					$this->get('session')->getFlashBag()->add('precision', "$message_error_precision");
				} else {
					$message_error = null;
					// Récupération du nombre de requêtes demandées = Nombre de requêtes déjà enregistré + Nouvelle requête demandée
					// Modification du 23/04/2015 : Plus de restriction limite du nombre de requête
					$tmpNumeroDeRequete = count($liste_req);
					if ($message_error != null) {
						$this->get('session')->getFlashBag()->add('info', "$message_error");
					} else {
						// Sauvegarde des paramètres de la demande
						$liste_req[$tmpNumeroDeRequete]['numeroGenre'] = $numGenre_idModule;
						$liste_req[$tmpNumeroDeRequete]['id_localisations'] = $localisations;
						$liste_req[$tmpNumeroDeRequete]['idModule'] = $idModules;
						$liste_req[$tmpNumeroDeRequete]['codeVal1'] = $codeVal1;
						$liste_req[$tmpNumeroDeRequete]['val1min'] = $val1min;
						$liste_req[$tmpNumeroDeRequete]['val1max'] = $val1max;
						$liste_req[$tmpNumeroDeRequete]['codeVal2'] = $codeVal2;
						$liste_req[$tmpNumeroDeRequete]['val2min'] = $val2min;
						$liste_req[$tmpNumeroDeRequete]['val2max'] = $val2max;
						// Message de la requête
						$liste_req[$tmpNumeroDeRequete]['Texte'] = $messageTemporaire;
						$liste_req[$tmpNumeroDeRequete]['Localisation'] = null;
						// Nombre Maximum de pages que la requête retourne
						$liste_req[$tmpNumeroDeRequete]['MaxDonnees'] = null;
						// Indique si tous les points sont récupérés
						$liste_req[$tmpNumeroDeRequete]['AllPoints'] = false;
						// Données remontée par la requête Sql
						$liste_req[$tmpNumeroDeRequete]['Donnees'] = array();
						$liste_req[$tmpNumeroDeRequete]['NbDonnees'] = 0;
						// Texte indiquant la recherche effectuée / All /Moyenne à l'heure/ A la minute etc.
						$liste_req[$tmpNumeroDeRequete]['TexteRecherche'] = null; 
						$liste_req[$tmpNumeroDeRequete]['precisionInit'] = null;
						$liste_req[$tmpNumeroDeRequete]['choixRechercheInit'] = null;
						$liste_req[$tmpNumeroDeRequete]['precision'] = null;
						$liste_req[$tmpNumeroDeRequete]['choixRecherche'] = null;
						$liste_req[$tmpNumeroDeRequete]['validation'] = false;
						// Redéfinition de la variable de session avec la nouvelle demande
						$this->session->set('liste_req_pour_graphique', $liste_req);
					}
				}
			}
		} else {
			// Lors du clic sur RAZ : Suppression des variables de session
			$this->session->remove('liste_req_pour_graphique');
			$liste_req = $this->session->get('liste_req_pour_graphique');
		}
	} else {
		$liste_req = $this->session->get('liste_req_pour_graphique');
	}
	$dbh = $this->connexion->disconnect();
	$heure_fin = strtotime(date('Y-m-d H:i:s'));
	$tmp_de_traitement = $heure_fin - $heure_debut;
	// Si le temps de traitement est >= au temps maximum d'execution des requêtes : retour vers la page d'erreur
	$tempMax = null;
	if ($tmp_de_traitement > $maximum_execution_time) {
		$tempMax = 1;
	}
	//  - - - -- - - - - - - - - - -- - - - - --    PARTIE REMPLACEMENT du numéro de localisation par l'intitulé de la localisation pour l'affichage
	// Pour chaque courbes, si une Localisation est définie, remplacement du numéro par l'intitulé
	// Création du tableau de conversion : N°localisation => Intitulé
	foreach ($this->liste_localisations as $key => $localisation) {
		$tabConversionLoc[$localisation['numero_localisation']] = $localisation['designation'];
	}
	foreach($liste_req as $key => $requete) {
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
		$this->get('session')->getFlashBag()->add('info', "Graphique : Aucun module défini pour les localisations du site courant");
		return $this->redirect($this->generateUrl('ipc_prog_homepage'));
	}
    // - - - -- - - - - - - - - - -- - - - - --    FIN DE PARTIE REMPLACEMENT du numéro de localisation par l'intitulé de la localisation pour l'affichage
	$popup_simplifiee = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('popup_simplifiee')->getValeur();
	$response = new Response(
        $this->renderView('IpcProgBundle:Prog:popupMessages.html.twig', array(
		'page_appelante' => 'graphique',
		'messagePeriode' => $messagePeriode,
		'liste_req' => $liste_req,
		'liste_localisations' => $this->liste_localisations,
		'last_loc_graph_id' => $this->last_loc_graph_id,
		'liste_genres' => $this->liste_genres,
		'liste_nomsModules' => $this->liste_noms_modules,
		'liste_messagesModules' => $this->liste_messages_modules,
		'maximum_execution_time' => $maximum_execution_time,
		'tempMax' => $tempMax,
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


// Fonction appelée lors de la validation du formulaire 
public function analyseAction() {
	// Si l'appel est de type GET, c'est qu'un clic sur F5 a été détecté: Redirection sur la page d'accueil
	if ($this->getRequest()->getMethod() == 'GET') {
		return $this->redirect($this->generateUrl('ipc_prog_homepage'));
	}
	// Récupération des graphiques 
	$this->initialisation();
	// Appel du service de traduction
	$autorisation_acces = $this->initialisationListes();
	$liste_req = $this->session->get('liste_req_pour_graphique');
	// Si un refresh est effectué : Réinitialisation des données
	if (isset($_POST['refresh_graphique'])) {
		if ($_POST['refresh_graphique'] == 'refresh') {
			foreach ($liste_req as $key => $requete) {
				$liste_req[$key]['MaxDonnees'] = null;
				$liste_req[$key]['AllPoints'] = null;
				$liste_req[$key]['NbDonnees'] = null;
				$liste_req[$key]['Donnees'] = array();
				$liste_req[$key]['TexteRecherche'] = null;
				$liste_req[$key]['precisionInit'] = 'none';
				$liste_req[$key]['choixRechercheInit'] = 'all';
				$liste_req[$key]['precision'] = 'none';
				$liste_req[$key]['choixRecherche'] = 'all';
				$liste_req[$key]['validation'] = false;
			}
		}
	}
	$fillnumbers = $this->get('ipc_prog.fillnumbers'); 
	$dbh = $this->dbh;
	// L'acces à la page est autorisé si la variable de session session_date est définie
	$session_date = $this->session->get('session_date');
	$messagePeriode = $this->messagePeriode;
	if (! empty($session_date)) {
		$datedebut = $fillnumbers->reverseDate($session_date['datedebut']);
		$datefin = $fillnumbers->reverseDate($session_date['datefin']);
		$messagePeriode = $session_date['messagePeriode'];
	}
	$this->tabModulesG = $this->session->get('tabModules');
	$toMany = false;
	$tempMax = null;
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
	// Temps maximum d'execution des requêtes avant kill
	$maximum_execution_time = $configuration->SqlGetParam($dbh, 'maximum_execution_time');
	$nbDecimal = $configuration->SqlGetParam($dbh, 'arrondi');
	$heure_debut = strtotime(date('Y-m-d H:i:s'));
	// Récupération du type de validation du formulaire de la page graphique.html : entre Analyse et Valider 
	$typeValidation 	= null;
	if (isset($_POST['choixSubmit'])) {
		$typeValidation = $_POST['choixSubmit']; 
	}
	// Lors de la demande d'exportation de la page courante : Récupération des informations du formulaire de la page affichage_graphique.html
	$tab_courbe = array();
	$datemin_courbe	= null;
	$datemax_courbe	= null;

	if ((isset($_POST['impression'])) && ($_POST['impression'] == 'yesPCsv')) {
		$numCourbe = 0;
		foreach ($liste_req as $key => $requete) {
			if (isset($_POST['correspondance_'.$key])) {
				// Récupération du numéro de courbe correspondant à la requête
				$numCourbe = $_POST['correspondance_'.$key];
				// Compression de la courbe
				$tab_courbe[$numCourbe]['compression']	= $_POST[$numCourbe];
				$tab_courbe[$numCourbe]['pas'] = $_POST['pas_'.$numCourbe];
				// Listes des abscisses
				$tab_courbe[$numCourbe]['X'] = json_decode($_POST['tabCourbe_'.$numCourbe.'_X']);
				// Listes des ordonnees
				$tab_courbe[$numCourbe]['Y'] = json_decode($_POST['tabCourbe_'.$numCourbe.'_Y']);
				$tab_courbe[$numCourbe]['Texte'] = $requete['Texte'];
				$tab_courbe[$numCourbe]['initial'] = $_POST['tabCourbe_'.$numCourbe.'_init'];
				// Si les données initiales sont affichées: Récupération des compressions initiales : 
				// Récupération des compressions initiales : Si le choixRecherche = all (Affichage Complet des données)
				// Récupération des compressions indiqués : Si le choixRecherche != all
				if ($tab_courbe[$numCourbe]['initial'] == "true") {
					if ($_POST[$numCourbe] == 'all') {
						$tab_courbe[$numCourbe]['choixRecherche'] = $requete['choixRechercheInit'];
						$tab_courbe[$numCourbe]['precision'] = $requete['precisionInit'];
						$tab_courbe[$numCourbe]['TexteRecherche'] 	= $this->afficheTexteRecherche($tab_courbe[$numCourbe]['precision'], $tab_courbe[$numCourbe]['choixRecherche']);
					} else {
						$tab_courbe[$numCourbe]['choixRecherche'] = $_POST[$numCourbe];
						$tab_courbe[$numCourbe]['precision'] = $_POST['pas_'.$numCourbe];
						$tab_courbe[$numCourbe]['TexteRecherche'] = $this->typeRecherche($tab_courbe[$numCourbe]['choixRecherche'], $tab_courbe[$numCourbe]['precision']);	
					}
				} else {
					// Si des données compressée on été affichées au départ et qu'aucun zoom n'affichant toutes les données n'a été effectué : Affichage de la compression indiqué sur la page
					$tab_courbe[$numCourbe]['choixRecherche'] = $_POST[$numCourbe];
					$tab_courbe[$numCourbe]['precision'] = $_POST['pas_'.$numCourbe];
					$tab_courbe[$numCourbe]['TexteRecherche'] = $this->typeRecherche($tab_courbe[$numCourbe]['choixRecherche'], $tab_courbe[$numCourbe]['precision']);
				}
				$tab_courbe[$numCourbe]['Localisation']	= $_POST['tabCourbe_'.$numCourbe.'_Localisation'];
				$tab_courbe[$numCourbe]['Unite'] = $this->tabModulesG[$requete['idModule']]['unite'];
				$tab_courbe[$numCourbe]['MaxDonnees'] = count($tab_courbe[$numCourbe]['X']);
			}
		}
		// IMPRESSION CSV des données présentes dans liste_req
		// Correction des caractères spéciaux pour affichage du csv dans excel
		// Lecture des données et enregistrement dans le fichier excel
		// Titre
		// Période
		// Date d'impression
		// Saut de ligne
		$chemin = 'uploads/tmp/';
		$fichier = 'Graphique_'.date('YmdHis').'.csv';
		$delimiteur	= ';';
		$fichier_csv = fopen($chemin.$fichier, 'w+');
		fprintf($fichier_csv, chr(0xEF).chr(0xBB).chr(0xBF));
		$tab_tmp = array($this->pageTitle['title']);
		fputcsv($fichier_csv, $tab_tmp, $delimiteur);
		$tab_tmp = array($messagePeriode);
		fputcsv($fichier_csv, $tab_tmp, $delimiteur);
		$tab_tmp = array("Export du ".date('d/m/Y H:i:s'));
		fputcsv($fichier_csv, $tab_tmp, $delimiteur);
		$tab_tmp = array();
		fputcsv($fichier_csv, $tab_tmp, $delimiteur);
		// Requête demandée
		$tab_tmp = array();
		foreach($tab_courbe as $key => $courbe){ if($courbe['MaxDonnees'] != 0) { $tab_tmp[] = 'Requête : '.$courbe['Texte']; $tab_tmp[] = ''; $tab_tmp[] = ''; $tab_tmp[] = ''; $tab_tmp[] = '';} }
		fputcsv($fichier_csv, $tab_tmp, $delimiteur);
		// Type de recherche (complete / Moyenne par seconde etc.)
		$tab_tmp = array();
		foreach($tab_courbe as $key => $courbe){ if($courbe['MaxDonnees'] != 0) { $tab_tmp[]=$courbe['TexteRecherche']; $tab_tmp[]=''; $tab_tmp[]=''; $tab_tmp[]=''; $tab_tmp[]='';} }
		fputcsv($fichier_csv, $tab_tmp, $delimiteur);
		// Localisation de la requete
		$tab_tmp = array();
		foreach($tab_courbe as $key => $courbe){ if($courbe['MaxDonnees'] != 0) { $tab_tmp[]='Localisation : '.$courbe['Localisation']; $tab_tmp[]=''; $tab_tmp[]=''; $tab_tmp[]=''; $tab_tmp[]='';} }
		fputcsv($fichier_csv, $tab_tmp, $delimiteur);
		// Entête des colonnes
		$tab_tmp = array();
		$tab_tmp[] = '';
		foreach($tab_courbe as $key => $courbe){ if($courbe['MaxDonnees'] != 0) { $tab_tmp[]='Horodatage'; $tab_tmp[]='Valeur'; $tab_tmp[]='Unité'; $tab_tmp[]=''; $tab_tmp[]='';} }
		fputcsv($fichier_csv, $tab_tmp, $delimiteur);
		// Données
		// Récupération du plus grand indice
		$max_indice	= 0;
		foreach($tab_courbe as $key => $courbe){ if($courbe['MaxDonnees'] > $max_indice) { $max_indice = $courbe['MaxDonnees'];} }
		// Parcours des tableaux jusqu'à max_indice
		for ($indice = 0 ; $indice < $max_indice ; $indice++) {
			$tab_tmp = array();
			$tab_tmp[] = '';
			// Pour chaque requête, on vérifie que l'indice n'est pas > au max
			// Si non, on récupére les données de l'indice en cours d'analyse
			foreach ($tab_courbe as $key => $courbe) {
				if ($courbe['MaxDonnees'] != 0) {
					if ($indice < $courbe['MaxDonnees']) {
						$timestamp = $courbe['X'][$indice]/1000;
						$millieme = substr($courbe['X'][$indice], -3);
						$horodatage = date('Y-m-d H:i:s', $timestamp);
						$tab_tmp[] = $horodatage.','.$millieme;
						$tab_tmp[] = preg_replace('/\./', ',', round($courbe['Y'][$indice], 2));
						$tab_tmp[] = $courbe['Unite'];
						$tab_tmp[] = '';
						$tab_tmp[] = '';
					} else {
						$tab_tmp[] = '';
						$tab_tmp[] = '';
						$tab_tmp[] = '';
						$tab_tmp[] = '';
						$tab_tmp[] = '';
					}
				}
			}	
			fputcsv($fichier_csv, $tab_tmp, $delimiteur);
		}
		fclose($fichier_csv);
		$response = new Response();
		$response->headers->set('Content-Type', 'application/force-download');
		$response->headers->set('Content-Disposition', 'attachment;filename="'.$fichier.'"');
		$response->headers->set('Content-Length', filesize($chemin.$fichier));
		$response->setContent(file_get_contents($chemin.$fichier));
		$response->setCharset('UTF-8');
		return $response;
	}
	// Lors d'une demande d'exportation de toutes les données : Si une des requêtes comporte un nombre de points > à la limite de l'export l'exportation n'est pas autorisée
	$tmp_nb_points_export = 0;
	if (isset($_POST['impression'])) {
		$tmp_nb_points_export = 0;
		foreach ($liste_req as $key => $requete) {
			if ($requete['MaxDonnees'] > $this->limit_export_sql) {
				if ($toMany == false) {
					$toMany = true;
					$this->get('session')->getFlashBag()->add('info', "La requête comporte trop de points pour l'export (maximu autorisé : $this->limit_export_sql)");
					$this->get('session')->getFlashBag()->add('precision', 'Requête : '.$liste_req[$key]['Texte']." (".$liste_req[$key]['NbDonnees']." points)");
				}
			}
			$tmp_nb_points_export += $requete['MaxDonnees'];
		}	
	}
	// Vérification que le nombre de points total ne dépasse par la limite excel
	if (($toMany == false) && ($tmp_nb_points_export > $this->limit_excel)) {
		$toMany = true;
		$this->get('session')->getFlashBag()->add('info', "Limitation par fichiers csv dépassé : $this->limit_excel");
		$this->get('session')->getFlashBag()->add('precision', "Nombre de points pour l'ensemble des requêtes : $tmp_nb_points_export");
	}
	// Si la demande concerne l'exportation d'une page : pas de re-calcul nécessaire
	if (! (isset($_POST['impression']))) {
		// Pour chaque courbe, analyse des choix de compression demandés
		foreach ($liste_req as $key => $requete) {
			// Si la demande concerne le recalcul des données : On réinitialise la variable à false pour toute les requêtes afin de pouvoir recalculer le nombre de données en fonction de la compression indiquée
			if (($typeValidation == 'AnalyseComplete') || ($typeValidation == 'Analyse') || ($typeValidation == 'Calculer') || ($typeValidation == 'Valider')) {
				$liste_req[$key]['validation'] = false;
				$requete['validation']  = false;
			}
            // Lors de la demande d'un calcul du nombre de point, on ne défini plus de limite à la recherche
            if (($typeValidation == 'Calculer') || ($typeValidation == 'Valider') || ($typeValidation == 'AnalyseComplete')) {
                $limite_requete = -1;
            } else {
                $limite_requete = $this->limit;
            }

			if ($requete['validation'] == false) {
				$nombre_de_donnees = 0;
				// Par défaut (lors de la 1ere recherche ) la recherche porte sur tous les points de la période : $choixRecherche = 'all' && $precision = 'none'
				// Ensuite la recherche est faite en fonction des choix de l'utilisateur
				isset($_POST["recherche_$key"]) ? $choixRecherche = $_POST["recherche_$key"] 	: $choixRecherche = 'all';
				isset($_POST["pas_$key"]) 	? $precision = $_POST["pas_$key"] 		: $precision = 'none';

				// Pour les requêtes provenant de graphique.html  : Si de nouveaux choix de compression ont été fait par l'utilisateur, une recherche des données est effectuée
				if (($liste_req[$key]['TexteRecherche'] != $this->afficheTexteRecherche($precision, $choixRecherche)) || ($typeValidation == 'Calculer') || ($typeValidation == 'Valider') || ($typeValidation == 'AnalyseComplete')) {
					if ($typeValidation == 'Calculer') {
                        $choixCompression = 'all';
                    } else {
                        $choixCompression = $choixRecherche;
                    }

					$tmp_donnee = new Donnee();
					// Mise au format d'une liste de valeurs les valeurs comprises dans le tableau des localisations de la requête
					$id_localisation = "'".$requete['id_localisations']."'";
					// Fonction permettant de récupérer la période minimum pour la localisation désignée
					$tmp_date_deb = $this->getDatePeriode($datedebut, $id_localisation, 'debut');
					$tmp_date_fin = $this->getDatePeriode($datefin, $id_localisation, 'fin');
					// Si la date de fin est < à la date min c'est qu'un des cas suivant c'est produit :
					// cas 1 : datedeb<datemin || datefin<datemin => Valeurs retournées (datemin & datefin) avec datefin<datemin
					// cas 2 : datedeb>datemax || datefin>datemax => Valeurs retournées (datedeb & datemax) avec datedeb>datemax
					$checkDate = $this->checkDeDate($tmp_date_deb, $tmp_date_fin);
					if ($checkDate == true) {
						$nombre_de_donnees = $tmp_donnee->SqlGetCountForGraphiqueWP(
						$dbh,
						$tmp_date_deb,
						$tmp_date_fin,
						$id_localisation,
						$requete['idModule'],
						$requete['codeVal1'],
						$requete['val1min'],
						$requete['val1max'],
						$requete['codeVal2'],
						$requete['val2min'],
						$requete['val2max'],
						$choixCompression,
                        $precision,
                        $limite_requete);
						if ($typeValidation == 'Calculer') {
                            if ($liste_req[$key]['TexteRecherche'] != $this->afficheTexteRecherche($precision, $choixRecherche)) {
                                $liste_req[$key]['NbDonnees'] = $nombre_de_donnees;
                            }
                        } else {
                            if (($limite_requete == -1) || ($nombre_de_donnees < $this->limit)) {
                                $liste_req[$key]['NbDonnees'] = $nombre_de_donnees;
                            } else {
                                $liste_req[$key]['NbDonnees'] = 'NA';
                            }
                        }
					} else {
						$liste_req[$key]['NbDonnees'] = 0;
					}
					// Si la requête ne retourne rien, c'est qu'elle a peut-etre était killée car ayant un temps d'execution trop long
					// Dans ce cas on n'enregistre pas les valeurs 
					// Si le nombre de données est <> null on ajoute 2 aux données retournées : Corresponds a la date de début et la date de fin qui seront calculés pour l'affichage des courbes
					if (($liste_req[$key]['NbDonnees'] != 'NA') && ($liste_req[$key]['NbDonnees'] != 0) && ($liste_req[$key]['NbDonnees'] == $nombre_de_donnees)) {
						if ($liste_req[$key]['NbDonnees'] != 0) {
							$liste_req[$key]['NbDonnees'] += 2;
						}
						$liste_req[$key]['precision'] = $precision;
						$liste_req[$key]['precisionInit'] = $precision;
						$liste_req[$key]['choixRecherche'] = $choixRecherche;
						$liste_req[$key]['choixRechercheInit'] = $choixRecherche;
						$liste_req[$key]['TexteRecherche'] = $this->afficheTexteRecherche($precision, $choixRecherche);

						// Lors de la première recherche la valeur de MaxDonnees est null : Initialisation de la variable avec le nombre de données de la recherche ( portant sur tous les points de la période)
						// La variable est correcte seulement si sa valeur est < à $this->limit ou si Un calcul du nombre de points a été demandé
						if (! $liste_req[$key]['MaxDonnees']) {
							if ((($typeValidation == 'Recherche') && ($liste_req[$key]['NbDonnees'] != 'NA') && ($liste_req[$key]['NbDonnees'] < $this->limit)) || ($typeValidation == 'Valider')) {
                                $liste_req[$key]['MaxDonnees'] = $tmp_donnee->SqlGetCountForGraphiqueWP(
                                $dbh,
                                $tmp_date_deb,
                                $tmp_date_fin,
                                $id_localisation,
                                $requete['idModule'],
                                $requete['codeVal1'],
                                $requete['val1min'],
                                $requete['val1max'],
                                $requete['codeVal2'],
                                $requete['val2min'],
                                $requete['val2max'],
                                'all',
                                null,
                                -1);
                            }
						}
					}
				}	
			}
		}
	}	
	// La mise à jour de la variable n'a pas lieu si la demande concerne l'export d'un fichier csv
	if (! (isset($_POST['impression'])) ) {
		$this->session->set('liste_req_pour_graphique', $liste_req);
	}
	// Si le choix du Submit est Valider => Si tous les points sont < $limit : Affichage des courbes 
	// Si l'export du csv de toutes les données est demandée et si le nombre de points max < $limit_export_sql : Recupération des données
	// || Sinon Message d'avertissement et retour vers la page graphique.html
	if ($toMany == false) {
		// Pour les requêtes qui doivent être affichées sur la page affichage_graphique.html : Vérification que le nombre de points ne dépasse pas la limite
		if ($typeValidation) {
			$error_tmp = false;
			$limit = $this->limit;
			foreach ($liste_req as $key => $requete) {
				// Si une demande d'affichage est demandée malgré le nombre de point trop important, on affiche tout de même les courbes.
				if (($typeValidation != 'Valider') && ($liste_req[$key]['NbDonnees'] > $limit)) {
					$tempMax = 0;
					$error_tmp = true;
				} else {
					$liste_req[$key]['validation'] = true;
				}
			}
			$this->session->set('liste_req_pour_graphique', $liste_req);
			if (($error_tmp == true) || ($typeValidation == 'Analyse') || ($typeValidation == 'AnalyseComplete') || ($typeValidation == 'Calculer')) {
				// - - - -- - - - - - - - - - -- - - - - --    PARTIE REMPLACEMENT du numéro de localisation par l'intitulé de la localisation pour l'affichage
				// Pour chaque courbe, si une Localisation est définie, remplacement du numéro par l'intitulé
				// Récupération de la liste des localisations du site courant
				foreach ($liste_req as $key => $requete) {
					$pattern = '/Localisation : \[(.+?)\]\ : (.+?)_(.+?)$/';
					if (preg_match($pattern, $requete['Texte'], $tab_numeroLoc)) {
						$numero_localisation = $tab_numeroLoc[1];
						$liste_req[$key]['Localisation'] = $numero_localisation;
						$liste_req[$key]['code_message'] = $tab_numeroLoc[2];
						$liste_req[$key]['Texte'] = $tab_numeroLoc[3];
					}
				}
				// - - - -- - - - - - - - - - -- - - - - --    PARTIE REMPLACEMENT du numéro de localisation par l'intitulé de la localisation pour l'affichage
				// Retour vers la page de sélection lorsque le nombre de points est > 4000
				return $this->render('IpcGraphiqueBundle:Graphique:graphique.html.twig', array(
					'messagePeriode' => $messagePeriode,
					'liste_req' => $liste_req,
					'liste_localisations' => $this->liste_localisations,
					'limit'	=> $limit,
					'tempMax' => $tempMax,
					'typeValidation' => $typeValidation,
                	'sessionCourante' => $this->session->getSessionName(),
                	'tabSessions' => $this->session->getTabSessions()
				));
			}
		}
		// Recherche des points pour chaque requête 
		foreach ($liste_req as $key => $requete) {
			// Si la demande concerne l'affichage d'un fichier csv et qu'une requête comporte 0 donnée : Pas de recherche necessaire
			if (isset($_POST['impression']) && ($requete['MaxDonnees'] == 0)) {
				continue;
			}
			if (isset($_POST['impression'])) {
				$requete['choixRecherche']='all';
			}
			$requete['Donnees'] = array();
			$nb_de_donnees = 0;
			// Si le nombre de points max = 0, pas de recherche effectuée pour la requête
			// La recherche porte sur toutes les données
			if ($requete['choixRecherche'] == 'all') {
				$liste_req[$key]['AllPoints'] = true;
			} else {
				// Sinon la recherche ne porte pas sur toutes les données
				$liste_req[$key]['AllPoints'] = false;
			}
			$tmp_donnee = new Donnee();
			// Liste des localisations à rechercher : Si il n'y a qu'une localisation pour le site courant, on définit la variable à 'all'
			// Ceci afin de ne pas faire de recherche sur l'id de la localisation
			$id_localisation = "'".$requete['id_localisations']."'";
			// Recherche de la première valeur de la courbe
			$serviceConfiguration = $this->get('ipc_prog.configuration');
			// Recherche et retourne la derniere date après laquelle une donnée est trouvée dans un intervalle d'une journée
			$tmp_date_deb = $this->getDatePeriode($session_date['datedebut'], $id_localisation, 'debut');
			$tmp_date_fin = $this->getDatePeriode($session_date['datefin'], $id_localisation, 'fin');
			$tmp_donnee = new Donnee();
			$tmp_datedebut = $fillnumbers->reverseDate($tmp_date_deb);
			$dateLimiteDeRecherche = $this->getDatePeriode($limitFirstDate, $id_localisation, 'debut');
			$tmp_datedebut = $serviceConfiguration->rechercheLastValue($requete['idModule'], $id_localisation, $tmp_datedebut, $dateLimiteDeRecherche);
			// On parcours les valeurs en soustrayant 1 jour jusqu'à ce que l'on trouve une donnée
			$firstValue	= array();
			$first_datetime	= new \Datetime($dateLimiteDeRecherche);
			$dateTmp = new \Datetime($tmp_datedebut);
			$dateTmp->add(new \DateInterval('P1D'));
			if ($dateTmp < $first_datetime) {
				$dateTmp = $first_datetime;
			}
			$dateAfterDebut = $dateTmp->format('Y-m-d H:i:s');
			$firstValue	= $tmp_donnee->SqlGetLastPoint(
				$dbh,
				$tmp_datedebut,
				$dateAfterDebut,
				$id_localisation,
				$requete['idModule'],
				$requete['codeVal1'],
				$requete['val1min'],
				$requete['val1max'],
				$requete['codeVal2'],
				$requete['val2min'],
				$requete['val2max']
			);
			// Si au moins un point est trouvé : 
			$OthersValues   = array();
			if ($requete['MaxDonnees'] != 0) {
				// Si il y a plus de X points : Passage à la version multi requête -> Permet de passer la limite des 9000 points.
				if ($requete['MaxDonnees'] > $this->limite_multi_requetes) {
					$timestamp_date_deb  = strtotime($this->reverseDate($tmp_date_deb));
					$timestamp_date_fin  = strtotime($this->reverseDate($tmp_date_fin));
					if (($requete['choixRecherche'] == 'all') || ($requete['precision'] != 'Mois')) {
						$timestamp_pointeur_date = $timestamp_date_deb;
						while ($timestamp_pointeur_date < $timestamp_date_fin) {
							$timestamp_pointeur_date += 86400*3;
							// Si le timestamp dépasse la date de fin on le fixe à la date de fin
							if ($timestamp_pointeur_date > $timestamp_date_fin) {
								$timestamp_pointeur_date = $timestamp_date_fin;
							}
							$pointeur_date = date('d-m-Y H:i:s', $timestamp_pointeur_date);
							$OthersValues = array_merge($OthersValues, $tmp_donnee->SqlGetForGraphique(
								$dbh,
								$fillnumbers->reverseDate($tmp_date_deb),
								$fillnumbers->reverseDate($pointeur_date),
								$id_localisation,
								$requete['idModule'],
								$requete['codeVal1'],
								$requete['val1min'],
								$requete['val1max'],
								$requete['codeVal2'],
								$requete['val2min'],
								$requete['val2max'],
								'nolimit',
								0,
								$requete['choixRecherche'],
								$requete['precision']
							));
							// La date de début de la prochaine requête = date de fin de la première requête + 1 seconde
							$tmp_date_deb = date('d-m-Y H:i:s', $timestamp_pointeur_date + 1);
						}
					} else {
						$OthersValues = array_merge($OthersValues, $tmp_donnee->SqlGetForGraphique(
                        	$dbh,
                            $fillnumbers->reverseDate($tmp_date_deb),
                            $fillnumbers->reverseDate($tmp_date_fin),
                            $id_localisation,
                            $requete['idModule'],
                            $requete['codeVal1'],
                            $requete['val1min'],
                            $requete['val1max'],
                            $requete['codeVal2'],
                            $requete['val2min'],
                            $requete['val2max'],
                            'nolimit',
                            0,
                            $requete['choixRecherche'],
                            $requete['precision']
                    	));
					}
				} else {
					$OthersValues = $tmp_donnee->SqlGetForGraphique(
						$dbh,
						$fillnumbers->reverseDate($tmp_date_deb),
						$fillnumbers->reverseDate($tmp_date_fin),
						$id_localisation,
						$requete['idModule'],
						$requete['codeVal1'],
						$requete['val1min'],
						$requete['val1max'],
						$requete['codeVal2'],
						$requete['val2min'],
						$requete['val2max'],
						'nolimit',
						0,
						$requete['choixRecherche'],
						$requete['precision']
					);
				}
				if (($requete['choixRecherche'] == 'average') && ($requete['precision'] != 'Mois')) {
					$OthersValues = $this->calculMoyennePonderee($OthersValues, $firstValue, $requete['precision']);
				}
				$nb_de_donnees = count($OthersValues);
			}
			// Récupération de la liste des modules graphiques
			$liste_modules = $this->session->get('tabModules');
			// Si le nombre de données est > à la limite, la recherche s'effectue sur la moyenne, le max ou le min
			// Pour chaque donnée récupérée, Enregistrement du Numéro de Module, De l'Intitulé du module, Du message
			// Mise à jour de la variable
			$dateTmp = new \Datetime($datedebut);
			$dateTmp->sub(new \DateInterval('PT1S'));
			$dateUne = $dateTmp->format('Y-m-d H:i:s');
			$dateTmp = new \Datetime($datefin);
			$dateTmp->add(new \DateInterval('PT1S'));
			$dateDeux = $dateTmp->format('Y-m-d H:i:s');
			// Nombre de données composant la requête
			$nbDonnees = 0;
			// Si une donnée précédant la recherche est trouvée, elle est placée en début de liste
			if (isset($firstValue[0])) {
				$requete['Donnees'][0]['horodatage'] = $dateUne;
				if (isset($liste_modules[$requete['idModule']])) {
					$requete['Donnees'][0]['message'] = $liste_modules[$requete['idModule']]['message'];
					$requete['Donnees'][0]['unite'] = $liste_modules[$requete['idModule']]['unite'];
				} else {
					$requete['Donnees'][0]['message'] = 'Non défini';
					$requete['Donnees'][0]['unite']	= 'Non défini';
				}
				$requete['Donnees'][0]['cycle'] = $firstValue[0]['cycle'];
				$requete['Donnees'][0]['valeur1'] = $firstValue[0]['valeur1'];
				$requete['Donnees'][0]['valeur2'] = $firstValue[0]['valeur2'];
				$nbDonnees ++;
			} elseif ($nb_de_donnees != 0) {
				// Si aucune donnée précédent la recherche n'est trouvée et que la requête retourne des données, la première valeur correspond à la première valeur de la requête
				$requete['Donnees'][0]['horodatage']    	= $dateUne;
				if (isset($liste_modules[$requete['idModule']])) {
					$requete['Donnees'][0]['message'] = $liste_modules[$requete['idModule']]['message'];
					$requete['Donnees'][0]['unite'] = $liste_modules[$requete['idModule']]['unite'];
				} else {
					$requete['Donnees'][0]['message'] = 'Non défini';
					$requete['Donnees'][0]['unite'] = 'Non défini';
				}
				if ($requete['choixRecherche'] == 'all') {
					$requete['Donnees'][0]['cycle']	= $OthersValues[0]['cycle'];
				} else {
					$requete['Donnees'][0]['cycle'] = 0;
				}
				$requete['Donnees'][0]['valeur1'] = $OthersValues[0]['valeur1'];
				$requete['Donnees'][0]['valeur2'] = $OthersValues[0]['valeur2'];
				$nbDonnees ++;
			}
			// Si des données sont retournées par la requête de recherche
			if ($nb_de_donnees != 0) {
				//	Si des données ont été trouvées par la requête initiale on place ces données dans le tableau à partir de la colonne 1
				foreach ($OthersValues as $key2 => $recupdonnee) {
					$requete['Donnees'][$nbDonnees]['horodatage'] 	= $OthersValues[$key2]['horodatage'];
					if (isset($liste_modules[$requete['idModule']])) {
						$requete['Donnees'][$nbDonnees]['message'] = $this->translator->trans($this->s_transforme_texte->supprimerEspaces($liste_modules[$requete['idModule']]['message']));
						$requete['Donnees'][$nbDonnees]['unite'] = $liste_modules[$requete['idModule']]['unite'];
					} else {
						$requete['Donnees'][$nbDonnees]['message'] = 'Non défini';
						$requete['Donnees'][$nbDonnees]['unite'] = 'Non défini';
					}
					if ($requete['choixRecherche'] == 'all') {
						$requete['Donnees'][$nbDonnees]['cycle'] = $OthersValues[$key2]['cycle'];
					} else {
						$requete['Donnees'][$nbDonnees]['cycle'] = 0;
					}
					$requete['Donnees'][$nbDonnees]['valeur1'] = $OthersValues[$key2]['valeur1'];
					$requete['Donnees'][$nbDonnees]['valeur2'] = $OthersValues[$key2]['valeur2'];
					$nbDonnees ++;
				}
				// On copie la dernière valeur à la date de fin (Date de fin = date de fin de période + 1 seconde)
				$requete['Donnees'][$nbDonnees]['horodatage'] = $dateDeux;
				if (isset($liste_modules[$requete['idModule']])) {
					$requete['Donnees'][$nbDonnees]['message'] = $this->translator->trans($this->s_transforme_texte->supprimerEspaces($liste_modules[$requete['idModule']]['message']));
					$requete['Donnees'][$nbDonnees]['unite'] = $liste_modules[$requete['idModule']]['unite'];
				} else { 
					$requete['Donnees'][$nbDonnees]['message'] = 'Non défini';
					$requete['Donnees'][$nbDonnees]['unite'] = 'Non défini';
				}
				if ($requete['choixRecherche'] == 'all') {
					// $nbDonnees = Nombre de données retournées par la requête + eventuellement la donnée de début de liste si elle existe
					// La recherche de la dernière valeur a pour clé : $nbDonnees - 1 (car le taleau commence à 0) - 1
					// Si le nombre de données retournées par la requête est > 0, on récupére les valeurs de la dernière donnée
					$requete['Donnees'][$nbDonnees]['cycle'] = $OthersValues[$nb_de_donnees-1]['cycle'];
				} else {
					$requete['Donnees'][$nbDonnees]['cycle'] = 0;
				}
				$requete['Donnees'][$nbDonnees]['valeur1'] = $OthersValues[$nb_de_donnees-1]['valeur1'];
				$requete['Donnees'][$nbDonnees]['valeur2'] = $OthersValues[$nb_de_donnees-1]['valeur2'];
			} elseif (isset($firstValue[0])) {
				// Si aucune donnée n'est retournée par la recherche initiale mais qu'une donnée précédent la période donnée est trouvée
				// Si aucune valeur n'a été trouvée par la requête initiale, on inscrit comme dernière valeur la valeur intiale
				// Modification de la date retournée avec la date de début si la recherche porte sur le dernier point avant la date de début
				$requete['Donnees'][1]['horodatage'] = $dateDeux;
				if (isset($liste_modules[$requete['idModule']])) {
					$requete['Donnees'][1]['message'] = $this->translator->trans($this->s_transforme_texte->supprimerEspaces($liste_modules[$requete['idModule']]['message']));
					$requete['Donnees'][1]['unite'] = $liste_modules[$requete['idModule']]['unite'];
				} else {
					$requete['Donnees'][1]['message'] = 'Non défini';
					$requete['Donnees'][1]['unite']	= 'Non défini';
				}
				$requete['Donnees'][1]['cycle'] = $requete['Donnees'][0]['cycle'];
				$requete['Donnees'][1]['valeur1'] = $requete['Donnees'][0]['valeur1'];
				$requete['Donnees'][1]['valeur2'] = $requete['Donnees'][0]['valeur2'];
			}
			$liste_req[$key]['NbDonnees'] = count($requete['Donnees']);
			// Si le nombre de points max = 0 et si une donnée précédent la date de début de période existe Alors nombre de points max = 2;
			if (($liste_req[$key]['MaxDonnees'] == 0) && (isset($firstValue[0]))) {
				$liste_req[$key]['MaxDonnees'] = 2;
			}
			$liste_req[$key]['Donnees'] = $requete['Donnees'];
		}
		// La mise à jour de la variable n'a pas lieu si la demande concerne l'export d'un fichier csv
		if (! (isset($_POST['impression']))) {
			// Définition en temps que variable de session
			$this->session->set('liste_req_pour_graphique', $liste_req);
		}
	} else {
		// Si le nombre de point est > à la limit lors d'un export on recupère les données de la dernière courbe affichée
		$liste_req = $this->session->get('liste_req_pour_graphique');
	}
	// Préparation du tableau qui sera affiché
	$tabDesRequetes = array();  
	foreach ($liste_req as $key => $requete) {
		if ($requete['NbDonnees'] != 0 ) {
			$tabDesRequetes = array_merge($tabDesRequetes, $requete['Donnees']);
		}
	}
	// Tri du tableau selon la date et le cycle
	$datedonnee = array();
	$cycledonnee = array();
	foreach ($tabDesRequetes as $key => $donnee) {
		$datedonnee[$key] = $donnee['horodatage'];
		$cycledonnee[$key] = $donnee['cycle'];
	}
	array_multisort($datedonnee, SORT_ASC, $cycledonnee, SORT_ASC, $tabDesRequetes);

	// Transmission des entités objets + les listes de valeur pour les recherches suivantes
	$dbh = $this->connexion->disconnect();
	$heure_fin = strtotime(date('Y-m-d H:i:s'));
	$tmp_de_traitement = $heure_fin - $heure_debut;
	// Si le temps de traitement est >= au temps maximum d'execution des requêtes : retour vers la page d'erreur
	$tempMax = null;
	if (($tmp_de_traitement > $maximum_execution_time)&&($requete['NbDonnees'] == 0)) {
		$tempMax = 1;
	}
	// Récupération des droits d'impression si le compte actif est le compte client (Seul le compte client n'hérite pas des droits technicien)
	$impression_graphique = null;
	if (! $this->get('security.context')->isGranted('ROLE_TECHNICIEN')) {
		$impression_graphique = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('autorisation_impression_graphique')->getValeur();
	} else {
		$impression_graphique = 1;
	}
	if ($typeValidation) {
		//  - - - -- - - - - - - - - - -- - - - - --    PARTIE REMPLACEMENT du numéro de localisation par l'intitulé de la localisation pour l'affichage
		//	Pour chaque courbes, si une Localisation est définie, remplacement du numéro par l'intitulé
		//	Création du tableau de conversion : N°localisation => Intitulé
		foreach ($this->liste_localisations as $key => $localisation) {
			$tabConversionLoc[$localisation['numero_localisation']] = $localisation['designation'];
		}
		//  - - - -- - - - - - - - - - -- - - - - --    FIN DE PARTIE REMPLACEMENT du numéro de localisation par l'intitulé de la localisation pour l'affichage
		foreach ($liste_req as $key => $requete) {
			$pattern = '/Localisation : \[(.+?)\]\ : (.+?)_(.+?)$/';
			if (preg_match($pattern, $requete['Texte'], $tab_numeroLoc)) {
				$numero_localisation = $tab_numeroLoc[1];
				$liste_req[$key]['code_message'] = $tab_numeroLoc[2];
				$liste_req[$key]['numero_localisation'] = $numero_localisation;
				$liste_req[$key]['Localisation'] = $tabConversionLoc[$numero_localisation];
				$liste_req[$key]['Texte'] = $this->translator->trans($liste_req[$key]['Localisation']).' : '.$liste_req[$key]['code_message'].'_'.$this->translator->trans(preg_replace('/\s+/',' ',$tab_numeroLoc[3]));
			}
		}

		//	Affichage des courbes si la demande provient de la page graphique.html et concerne l'affichage des courbes(TypeValidation = Valider)
		return $this->render('IpcGraphiqueBundle:Graphique:affichage_graphique.html.twig', array(
			'nbDecimal' => $nbDecimal,
			'activation_modbus'	=> $this->activation_modbus,
			'messagePeriode' => $messagePeriode,
			'liste_req_pour_graphique' => $liste_req,
			'tabDesRequetes' => $tabDesRequetes,	//	Utilisé pour le javascript
			'tabLastHorodatage' => $this->tab_last_horodatage_loc_id, 
			'tempMax' => $tempMax,
			'impression_graphique' => $impression_graphique,
		    'sessionCourante' => $this->session->getSessionName(),
            'tabSessions' => $this->session->getTabSessions()
		));
	}
	// Si le type de recherche est Analyse => Retour vers la page formulaire.html.twig pour affichage des requêtes et des nombres de points récupérés
	$dbh = $this->connexion->disconnect();
	$heure_fin = strtotime(date('Y-m-d H:i:s'));
	$tmp_de_traitement = $heure_fin - $heure_debut;
	// Si le temps de traitement est >= au temps maximum d'execution des requêtes : retour vers la page d'erreur
	$tempMax = null;
	if ($tmp_de_traitement > $maximum_execution_time) {
		$tempMax = 1;
	}
	// Page affichée lors du clic sur 'Lancer la recherche'

	// - - - -- - - - - - - - - - -- - - - - -- 	PARTIE REMPLACEMENT du numéro de localisation par l'intitulé de la localisation pour l'affichage
	// Pour chaque courbes, si une Localisation est définie, remplacement du numéro par l'intitulé
	// Récupération de la liste des localisations du site courant
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
			$liste_req[$key]['Localisation'] = $remplacement;
			$liste_req[$key]['Texte'] = preg_replace($pattern, $remplacement, $requete['Texte']);
		} else {
			$liste_req[$key]['Localisation']='Toutes localisations';
			$liste_req[$key]['Texte'] = 'Toutes localisations : '.$requete['Texte'];
		}
	}
	//  - - - -- - - - - - - - - - -- - - - - --    FIN DE PARTIE REMPLACEMENT du numéro de localisation par l'intitulé de la localisation pour l'affichage
	//  - - - - - - - - - - - - - PARTIE IMPRESSION - - - - - - - - - - - - -
	// Affichage du csv contenant toutes les données si le nombre de données ne dépasse par les limites d'export et d'excel
	if (isset($_POST['impression'])) {
		if ($toMany == false) {
			// IMPRESSION CSV des données présentes dans liste_req
			$chemin = 'uploads/tmp/';
			$fichier = 'Graphique_'.date('YmdHis').'.csv';
			$delimiteur = ';';
			$fichier_csv = fopen($chemin.$fichier,'w+');
			// Correction des caractères spéciaux pour affichage du csv dans excel
			fprintf($fichier_csv, chr(0xEF).chr(0xBB).chr(0xBF));
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
			// Saut de ligne
			$tab_tmp = array();
			fputcsv($fichier_csv, $tab_tmp, $delimiteur);
			// Requête demandée
			$tab_tmp = array();
			foreach ($liste_req as $key => $requete) {
				if ($requete['MaxDonnees'] != 0) {
					$pattern = '/^.+?:(.+?)$/';
					if(preg_match($pattern, $requete['Texte'], $texte_requete)) {
						$tab_tmp[] = 'Requête : '.$texte_requete[1];
						$tab_tmp[] = '';
						$tab_tmp[] = '';
						$tab_tmp[] = '';
						$tab_tmp[] = '';
						$tab_tmp[] = '';
					}
				}
			}
			fputcsv($fichier_csv, $tab_tmp, $delimiteur);
			// Type de la recherche
			$tab_tmp = array();
			foreach($liste_req as $key => $requete) {
				if ($requete['MaxDonnees'] != 0) {
					$tab_tmp[] = 'Recherche sur toutes les données';
					$tab_tmp[] = '';
					$tab_tmp[] = '';
					$tab_tmp[] = '';
					$tab_tmp[] = '';
					$tab_tmp[] = '';
				}
			}
			fputcsv($fichier_csv, $tab_tmp, $delimiteur);
			// Localisation
			$tab_tmp = array();
			foreach ($liste_req as $key => $requete) {
				if ($requete['MaxDonnees'] != 0) {
					$tab_tmp[] = 'Localisation : '.$requete['Localisation'];
					$tab_tmp[] = '';
					$tab_tmp[] = '';
					$tab_tmp[] = '';
					$tab_tmp[] = '';
					$tab_tmp[] = '';
				}		
			}
			fputcsv($fichier_csv, $tab_tmp, $delimiteur);
			// Entête des colonnes
			$tab_tmp = array();
			$tab_tmp[] = '';
			foreach($liste_req as $key => $requete) {
				if ($requete['MaxDonnees'] != 0) {
					$tab_tmp[] = 'Horodatage';
					$tab_tmp[] = 'Valeur1';
					$tab_tmp[] = 'Valeur2';
					$tab_tmp[] = 'Unite';
					$tab_tmp[] = '';
					$tab_tmp[] = '';
				}
			}
			fputcsv($fichier_csv, $tab_tmp, $delimiteur);
			// Récupération du plus grand indice
			$max_indice = 0;
			foreach ($liste_req as $key => $requete) {
				if ($requete['MaxDonnees'] > $max_indice) {
					$max_indice = $requete['MaxDonnees'];
				}
			}
			// Parcours des tableaux jusqu'a max_indice 
			for ($indice = 0 ; $indice < $max_indice ; $indice++) {
				$tab_tmp = array();
				$tab_tmp[] = '';
				// Pour chaque requête, on vérifie que l'indice n'est pas > au max
				// Si non, on récupére les données de l'indice en cours d'analyse
				foreach ($liste_req as $key => $requete) {
					if ($requete['MaxDonnees'] != 0) {
						if ($indice < $requete['MaxDonnees']) {
							$tab_tmp[] = $requete['Donnees'][$indice]['horodatage'].','.$fillnumbers->fillNumber($requete['Donnees'][$indice]['cycle'], 3);
							$tab_tmp[] = preg_replace('/\./', ',', $requete['Donnees'][$indice]['valeur1']);
							$tab_tmp[] = preg_replace('/\./', ',', $requete['Donnees'][$indice]['valeur2']);
							$tab_tmp[] = $this->tabModulesG[$requete['idModule']]['unite'];
							$tab_tmp[] = '';
							$tab_tmp[] = '';
						} else {
							$tab_tmp[] = '';
							$tab_tmp[] = '';
							$tab_tmp[] = '';
							$tab_tmp[] = '';
							$tab_tmp[] = '';
							$tab_tmp[] = '';
						}
					}
				}
				fputcsv($fichier_csv, $tab_tmp, $delimiteur);
			}
			fclose($fichier_csv);
			$response = new Response();
			$response->headers->set('Content-Type', 'application/force-download');
			$response->headers->set('Content-Disposition', 'attachment;filename="'.$fichier.'"');
			$response->headers->set('Content-Length', filesize($chemin.$fichier));
			$response->setContent(file_get_contents($chemin.$fichier));
			$response->setCharset('UTF-8');
			return $response;
		} else {
			$liste_req_init = $this->session->get('liste_req_pour_graphique');
			return $this->render('IpcGraphiqueBundle:Graphique:affichage_graphique.html.twig', array(
				'nbDecimal' => $nbDecimal,
				'activation_modbus'	=> $this->activation_modbus,
				'messagePeriode' => $messagePeriode,
				'liste_req_pour_graphique' => $liste_req,
				'liste_req' => $liste_req_init,
				'tabDesRequetes' => $tabDesRequetes,
				'tabLastHorodatage' => $this->tab_last_horodatage_loc_id,
				'tempMax' => $tempMax,
				'impression_graphique' => $impression_graphique,
			    'sessionCourante' => $this->session->getSessionName(),
                'tabSessions' => $this->session->getTabSessions()
			));
		}
	}
	// - - - - - - - - - - - - - FIN DE LA PARTIE IMPRESSION - - - - - - - - - - - - -
	// - - - -- - - - - - - - - - -- - - - - --    PARTIE REMPLACEMENT du numéro de localisation par l'intitulé de la localisation pour l'affichage
	// Appelée lors du clic sur le raffraichissement de la page graphique.html
	foreach ($liste_req as $key => $requete) {
		$pattern = '/Localisation : \[(.+?)\]\ : (.+?)_(.+?)$/';
		if (preg_match($pattern, $requete['Texte'], $tab_numeroLoc)) {
			$numero_localisation = $tab_numeroLoc[1];
			$liste_req[$key]['Localisation'] = $numero_localisation;
			$liste_req[$key]['code_message'] = $tab_numeroLoc[2];
			$liste_req[$key]['Texte'] = $tab_numeroLoc[3];
		}
	}
	return $this->render('IpcGraphiqueBundle:Graphique:graphique.html.twig', array(
		'messagePeriode' => $messagePeriode,
		'liste_req' => $liste_req,
		'liste_localisations' => $this->liste_localisations,
		'limit' => $this->limit,
		'tempMax' => $tempMax,
		'typeValidation' => $typeValidation,
	    'sessionCourante' => $this->session->getSessionName(),
        'tabSessions' => $this->session->getTabSessions()
	));
}



// Fonction appelée en AJAX par selection d'une portion de courbe
public function getListReqAction() {
	// Récupération des variables communes à toutes les fonctions
	$this->initialisation();
	// Initialisation des listes communes aux divers fonctions du controller
	$autorisation_acces = $this->initialisationListes();
	// Service de mise au norme du format des chiffres
	$fillnumbers = $this->get('ipc_prog.fillnumbers'); 
	$dbh = $this->dbh;
	// Récupération de la requête qui a appelée la fonction getListReqAction
	$request = $this->get('request'); 
	$liste_req = $this->session->get('liste_req_pour_graphique');
	// Tableau de clé IDModule et de valeurs : CATEGORIE.NumMODULE.NumMESSAGE
	$correspondance_message_code = $this->session->get('correspondance_Message_Code');
	$session_date = $this->session->get('session_date');
	if (! empty($session_date)) {
		$messagePeriode = $session_date['messagePeriode'];
	}
	$limit = $this->limit;
	// Récupération des dates de la période sélectionnée sur le graphique
	$datedebut = $_POST['datemin'];
	$datefin = $_POST['datemax'];
	// BUG du programme Highchart : En cas de zoom entre deux dates proches la date min et la date max peuvent etre inversées
	// Vérification de la date min et de la date max
	if ($datedebut > $datefin) {
		$datetempo = $datedebut;
		$datedebut = $datefin;
		$datefin = $datetempo;
	}
	// Pour chaque requête demandée on récupére la liste des modules à afficher : Si le nombre de données Max est != 0 
	foreach ($liste_req as $key => $requete) {
		if ($requete['MaxDonnees'] != 0) {
			$requete['Donnees'] = array();
			// Récupération du choix de recherche initial 	(Indique si la recherche était compléte ou si elle était compressée
			$choixRecherche = $liste_req[$key]['choixRecherche'];	
			// Récupération de la précision 			(Indique en cas de recherche compressée quel est le pas de compression
			$precision = $liste_req[$key]['precision'];
			// Localisation à rechercher 
			$id_localisation = "'".$requete['id_localisations']."'";
			// Récupération des dates de début et de fin de recherche
			// Si la date de fin est < à la date min c'est qu'un des cas suivant c'est produit :
			// cas 1 : datedeb<datemin || datefin<datemin => Valeurs retournées (datemin & datefin) avec datefin<datemin
			// cas 2 : datedeb>datemax || datefin>datemax => Valeurs retournées (datedeb & datemax) avec datedeb>datemax
			$datedebut = $this->getDatePeriode($datedebut, $id_localisation, 'debut');
			$datefin = $this->getDatePeriode($datefin, $id_localisation, 'fin');
			$checkDate = $this->checkDeDate($datedebut, $datefin);
			// Récupération du nombre de données dans la sélection si les dates sont dans l'intervalle des périodes autorisées
			$tmp_donnee = new Donnee(); 
			// Indique le nombre de données récupérer dans la période à analyser
			$nb_de_donnees = 0;
			if ($checkDate == true) {
				$nb_de_donnees = $tmp_donnee->SqlGetCountForGraphique(  
					$dbh,
					$datedebut,
					$datefin,
					$id_localisation,
					$requete['idModule'],
					$requete['codeVal1'],
					$requete['val1min'],
					$requete['val1max'],
					$requete['codeVal2'],
					$requete['val2min'],
					$requete['val2max'],
					$this->limit
				);
			} else {
				$nb_de_donnees = 0;
			}
			// Si le nombre de points récupéré est < à la limit, la recherche retourne tous les points de la requête : $choixRecherche = all
			if ($nb_de_donnees < $this->limit) {
				// Retourne tous les points de la requête
				$choixRecherche = 'all';
				// Texte indiquant que la recherche retourne tous les points
				$liste_req[$key]['TexteRecherche'] = $this->afficheTexteRecherche($precision, $choixRecherche);
				// Indique que tous les points sont retournés
				$liste_req[$key]['AllPoints'] = true;
				$liste_req[$key]['choixRecherche'] = 'all';
				$liste_req[$key]['precision'] = 'none';
			} else {
				// Si le nombre de points récupérés est >  à la limit, la recherche est faite selon la compression définie initialement (Récupération des compression initialement définies)
				$liste_req[$key]['choixRecherche'] = $liste_req[$key]['choixRechercheInit'];
				$liste_req[$key]['precision'] = $liste_req[$key]['precisionInit'];
				// Récupération du pas de recherche initial
				$precision = $liste_req[$key]['precision'];
				// Récupération du choix de compression initial
				$choixRecherche = $liste_req[$key]['choixRecherche'];
				// Texte indiquant que la recherche est faite avec compression
				$liste_req[$key]['TexteRecherche']  = $this->afficheTexteRecherche($precision, $choixRecherche);
				$liste_req[$key]['AllPoints'] = false;
			}
			// Recherche de la date minimum autorisée pour les recherches
			$limitFirstDate = null;
			$service_security = $this->get('security.context');
			$configuration = new Configuration();
			// La date minimum est défini dans les paramètres de configuration
			// Elle diffère selon les droits de l'utilisateur
			if ($service_security->isGranted('ROLE_SUPERVISEUR')) {
				$limitFirstDate = $configuration->SqlGetParam($dbh, 'date_de_mise_en_service');
			} else if ($service_security->isGranted('ROLE_TECHNICIEN')) {
				$limitFirstDate = $configuration->SqlGetParam($dbh, 'date_dmes');
			} else if ($service_security->isGranted('ROLE_USER')) {
				$limitFirstDate = $configuration->SqlGetParam($dbh, 'autorisation_dmes');
			}
			// Création de la variable de type date correspondante à la date minimum autorisée
			$first_datetime = new \Datetime($limitFirstDate);
			$tmp_datedebut = $datedebut;
			// Recherche de la première valeur de la courbe
			$serviceConfiguration = $this->get('ipc_prog.configuration');
			$tmp_donnee = new Donnee();
			// Recherche et retourne la dernière date, avant la date de début de sélection, après laquelle une donnée est trouvée dans un intervalle d'une journée
			$dateLimiteDeRecherche  = $this->getDatePeriode($limitFirstDate, $id_localisation, 'debut');
			$tmp_datedebut = $serviceConfiguration->rechercheLastValue($requete['idModule'], $id_localisation, $tmp_datedebut, $dateLimiteDeRecherche);
			// Intervalle : datedebut-1jour / tmp_datedebut
			$dateTmp = new \Datetime($tmp_datedebut);
			$dateTmp->add(new \DateInterval('P1D'));
			if ($dateTmp < $first_datetime) {
				$dateTmp = $first_datetime;
			}
			$dateAfterDebut = $dateTmp->format('Y-m-d H:i:s');
			$firstValue = array();
			$firstValue	= $tmp_donnee->SqlGetLastPoint(
				$dbh,
				$tmp_datedebut,
				$dateAfterDebut,
				$id_localisation,
				$requete['idModule'],
				$requete['codeVal1'],
				$requete['val1min'],
				$requete['val1max'],
				$requete['codeVal2'],
				$requete['val2min'],
				$requete['val2max']
			);
			$OthersValues = array();
			if ($nb_de_donnees != 0) {
				if ($nb_de_donnees > $this->limite_multi_requetes) {
					// Découpage en plage de 3 jours
					$tmp_date_deb = $fillnumbers->reverDate($datedebut);
					$timestamp_date_deb = strtotime($tmp_date_deb);
					$timestamp_date_fin = strtotime($fillnumbers->reverDate($datefin));
					if (($choixRecherche == 'all') || ($precision != 'Mois')) {
						$timestamp_pointeur_date = $timestamp_date_deb;
						while ($timestamp_pointeur_date < $timestamp_date_fin) {
							$timestamp_pointeur_date += 86400 * 3;
							// Si le timestamp dépasse la date de fin on le fixe à la date de fin
							if ($timestamp_pointeur_date > $timestamp_date_fin) {
								$timestamp_pointeur_date = $timestamp_date_fin;
							}
							$pointeur_date = date('Y-m-d H:i:s', $timestamp_pointeur_date);
							$OthersValues = array_merge($OthersValues, $tmp_donnee->SqlGetForGraphique(
								$dbh,
								$fillnumbers->reverseDate($tmp_date_deb),
								$fillnumbers->reverseDate($pointeur_date),
								$id_localisation,
								$requete['idModule'],
								$requete['codeVal1'],
								$requete['val1min'],
								$requete['val1max'],
								$requete['codeVal2'],
								$requete['val2min'],
								$requete['val2max'],
								'nolimit',
								0,
								$choixRecherche,
								$precision
							));
							// La date de début de la prochaine requête = date de fin de la première requête + 1 seconde
							$tmp_date_deb = date('d-m-Y H:i:s', $timestamp_pointeur_date + 1);
						}
					} else {
                        $OthersValues = array_merge($OthersValues, $tmp_donnee->SqlGetForGraphique(
                            $dbh,
                            $fillnumbers->reverseDate($tmp_date_deb),
                            $fillnumbers->reverseDate($fillnumbers->reverDate($datefin)),
                            $id_localisation,
                            $requete['idModule'],
                            $requete['codeVal1'],
                            $requete['val1min'],
                            $requete['val1max'],
                            $requete['codeVal2'],
                            $requete['val2min'],
                            $requete['val2max'],
                            'nolimit',
                            0,
                            $choixRecherche,
                            $precision
                        ));
					}
				} else {
					// Recherche de toutes les données si < limit
					$OthersValues = $tmp_donnee->SqlGetForGraphique(
						$dbh,
						$datedebut,
						$datefin,
						$id_localisation,
						$requete['idModule'],
						$requete['codeVal1'],
						$requete['val1min'],
						$requete['val1max'],
						$requete['codeVal2'],
						$requete['val2min'],
						$requete['val2max'],
						'nolimit',
						0,
						$choixRecherche,
						$precision
					);
				}
                if (($choixRecherche == 'average') && ($precision != 'Mois')) {
                    $OthersValues = $this->calculMoyennePonderee($OthersValues, $firstValue, $precision);
                }
			}
			// Pour chaque donnée récupérée, Enregistrement du Numéro de Module, De l'Intitulé du module, Du message
			// Définition des dates avant début et aprés fin pour le début et la fin des graphiques
			// Première date du graphique = date de début - 1 seconde
			$dateTmp = new \Datetime($datedebut);
			$dateTmp->sub(new \DateInterval('PT1S'));
			$dateUne = $dateTmp->format('Y-m-d H:i:s');
			// Dernière date du graphique = date de fin + 1 seconde
			$dateTmp = new \Datetime($datefin);
			$dateTmp->add(new \DateInterval('PT1S'));
			$dateDeux = $dateTmp->format('Y-m-d H:i:s');
			$liste_modules = $this->session->get('tabModules');
			$nbDonnees = 0;
			// Définition de la première valeur du tableau des données (Si une valeur précédant la date de début est trouvée) 
			if (isset($firstValue[0])) {
				$requete['Donnees'][0]['horodatage'] = $dateUne;
				if (isset($liste_modules[$requete['idModule']])) {
					$requete['Donnees'][0]['message'] = $liste_modules[$requete['idModule']]['message'];
					$requete['Donnees'][0]['unite'] = $liste_modules[$requete['idModule']]['unite'];
				} else {
					$requete['Donnees'][0]['message'] = 'non défini';
					$requete['Donnees'][0]['unite'] = 'non défini';
				}
				$requete['Donnees'][0]['cycle'] = $firstValue[0]['cycle'];
				$requete['Donnees'][0]['valeur1'] = $firstValue[0]['valeur1'];
				$requete['Donnees'][0]['valeur2'] = $firstValue[0]['valeur2'];
				$nbDonnees ++;
			} elseif ($nb_de_donnees != 0) {
				// Si aucune donnée précédent la recherche n'est trouvée et que la requête retourne des données, la première valeur correspond à la première valeur de la requête
				$requete['Donnees'][0]['horodatage'] = $dateUne;
				if (isset($liste_modules[$requete['idModule']])) {
					$requete['Donnees'][0]['message'] = $liste_modules[$requete['idModule']]['message'];
					$requete['Donnees'][0]['unite'] = $liste_modules[$requete['idModule']]['unite'];
				} else {
					$requete['Donnees'][0]['message'] = 'Non défini';
					$requete['Donnees'][0]['unite'] = 'Non défini';
				}
				if ($choixRecherche == 'all') {
					$requete['Donnees'][0]['cycle'] = $OthersValues[0]['cycle'];
				} else {
					$requete['Donnees'][0]['cycle'] = 0;
				}
				$requete['Donnees'][0]['valeur1'] = $OthersValues[0]['valeur1'];
				$requete['Donnees'][0]['valeur2'] = $OthersValues[0]['valeur2'];
				$nbDonnees ++;
			}
			// Définition des autres valeurs du tableau : Correspondantes aux valeurs retournée par la requête
			if ($nb_de_donnees != 0) {
				foreach ($OthersValues as $key2 => $recupdonnee) {
					$requete['Donnees'][$nbDonnees]['horodatage'] = $OthersValues[$key2]['horodatage'];
					if (isset($liste_modules[$requete['idModule']])) {
						$requete['Donnees'][$nbDonnees]['message'] = $liste_modules[$requete['idModule']]['message'];
						$requete['Donnees'][$nbDonnees]['unite'] = $liste_modules[$requete['idModule']]['unite'];
					} else {
						$requete['Donnees'][$nbDonnees]['message'] = 'non défini';
						$requete['Donnees'][$nbDonnees]['unite'] = 'non défini';
					}
					if ($choixRecherche == 'all') {
						$requete['Donnees'][$nbDonnees]['cycle'] = $OthersValues[$key2]['cycle'];
					} else {
						$requete['Donnees'][$nbDonnees]['cycle'] = 0;
					}
					$requete['Donnees'][$nbDonnees]['valeur1'] = $OthersValues[$key2]['valeur1'];
					$requete['Donnees'][$nbDonnees]['valeur2'] = $OthersValues[$key2]['valeur2'];
					$nbDonnees ++;
				}
				// Définition de la dernière valeur du tableau des données
				$requete['Donnees'][$nbDonnees]['horodatage'] = $dateDeux;
				if (isset($liste_modules[$requete['idModule']])) {
					$requete['Donnees'][$nbDonnees]['message'] = $liste_modules[$requete['idModule']]['message'];
					$requete['Donnees'][$nbDonnees]['unite'] = $liste_modules[$requete['idModule']]['unite'];
				} else {
					$requete['Donnees'][$nbDonnees]['message'] = 'non défini';
					$requete['Donnees'][$nbDonnees]['unite'] = 'non défini';
				}
				// La dernière valeur du tableau = indice-1(car un tableau début à 0)-1(si une valeur de début est défini)
				if ($choixRecherche == 'all') {
					$requete['Donnees'][$nbDonnees]['cycle'] = $OthersValues[$nbDonnees-2]['cycle'];
				} else {
					$requete['Donnees'][$nbDonnees]['cycle'] = 0;
				}
				$requete['Donnees'][$nbDonnees]['valeur1'] = $OthersValues[$nbDonnees-2]['valeur1'];
				$requete['Donnees'][$nbDonnees]['valeur2'] = $OthersValues[$nbDonnees-2]['valeur2'];
			} elseif (isset($firstValue[0])) {
				// Modification de la date retournée avec la date de début si la recherche porte sur le dernier point avant la date de début
				// Si la seule donnée récupérée est la date précédant la date de début de période : Le graphique correspond à un tracé d'une droite
				$requete['Donnees'][1]['horodatage'] = $dateDeux;
				if (isset($liste_modules[$requete['idModule']])) {
					$requete['Donnees'][1]['message'] = $liste_modules[$requete['idModule']]['message'];
					$requete['Donnees'][1]['unite'] = $liste_modules[$requete['idModule']]['unite'];
				} else { 
					$requete['Donnees'][1]['message'] = 'non défini';
					$requete['Donnees'][1]['unite']	= 'non défini';
				}
				$requete['Donnees'][1]['cycle'] = $firstValue[0]['cycle'];
				$requete['Donnees'][1]['valeur1'] = $firstValue[0]['valeur1'];
				$requete['Donnees'][1]['valeur2'] = $firstValue[0]['valeur2'];
			}
			$liste_req[$key]['NbDonnees'] = count($requete['Donnees']);
			// Mise à jour de la variable
			$liste_req[$key]['Donnees'] = $requete['Donnees'];
		}
	} 
	// Redéfinition de la variable de session avec la nouvelle demande
	$this->session->set('liste_req_pour_graphique', $liste_req);
	//  - - - -- - - - - - - - - - -- - - - - --    PARTIE REMPLACEMENT du numéro de localisation par l'intitulé de la localisation pour l'affichage
	// Pour chaque courbes, si une Localisation est définie, remplacement du numéro par l'intitulé
	// Récupération de la liste des localisations du site courant
	// Création du tableau de conversion : N°localisation => Intitulé
	foreach ($this->liste_localisations as $key => $localisation) {
		$tabConversionLoc[$localisation['numero_localisation']] = $localisation['designation'];
	}
	foreach ($liste_req as $key => $requete) {
		$pattern='/Localisation : \[(.+?)\]/';
		if (preg_match($pattern, $requete['Texte'], $tab_numeroLoc)) {
			$numero_localisation = $tab_numeroLoc[1];
			// Si Toutes les localisations sur tous les sites est demandé
			if ($numero_localisation == 'every') {
				$remplacement = 'Tous Sites-Toutes localisations';
			} else {
				$remplacement = $tabConversionLoc[$numero_localisation];
			}
			$liste_req[$key]['Localisation']=$remplacement;
			$liste_req[$key]['Texte'] = preg_replace($pattern, $remplacement, $requete['Texte']);
		} else {
			$liste_req[$key]['Localisation']='Toutes localisations';
			$liste_req[$key]['Texte'] = 'Toutes localisations : '.$requete['Texte'];
		}
	}	
	echo json_encode($liste_req);
	$dbh = $this->connexion->disconnect();
	return new Response();
}


public function getMessageRequete($numLocalisation, $message_idModule, $codeVal1, $val1min, $val1max, $codeVal2, $val2min, $val2max) {
	$this->constructeur();
	$message = '';
	if ($numLocalisation != 'all') {
		$message .= "Localisation : [$numLocalisation] : ";
	}
	$message .= $this->suppressionDesCaracteres($message_idModule);
	switch ($codeVal1) {
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
	switch ($codeVal2) {
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


protected function afficheTexteRecherche($precision, $choixRecherche) {
	$texteHoraire = "";
	switch ($precision) {
	case 'Seconde' :
		$texteHoraire = "par seconde";
		break;
	case 'Minute' :
		$texteHoraire = "par minute";
		break;
	case 'Heure' :
		$texteHoraire = "par heure";
		break;
	case 'Jour' :
		$texteHoraire = "par jour";
		break;
	case 'Mois' :
		$texteHoraire = "par mois";
		break;
	}
	$texteRecherche = "";
	switch ($choixRecherche) {
	case 'average' :
		$texteRecherche = 'Recherche sur la moyenne '.$texteHoraire;
		break;
	case 'high' :
		$texteRecherche = 'Recherche sur le maximum '.$texteHoraire;
		break;
	case 'low' :
		$texteRecherche = 'Recherche sur le minimum '.$texteHoraire;
		break;
	case 'all' :
		$texteRecherche = 'Recherche sur toutes les données';
		break;
	}
	$texte = $texteRecherche;
	return($texte);
}


//	Fonction retournant le type de recherche effectué par la requête
protected function typeRecherche($choixRecherche, $precision) {
	// ex : 10_minutes
	$tabPrecision = split('_', $precision);
	// Nombre de la precision
	$nbPrecision = $tabPrecision[0];
	// Indice de la precision : minute / seconde / hour etc.
	$indicePrecision = $tabPrecision[1];
	$texteRecherche = "";	
	switch ($choixRecherche) {
	case 'average':
		$texteRecherche = 'Recherche sur la moyenne ';
		break;
	case 'high':
		$texteRecherche = 'Recherche sur le maximum ';
		break;
	case 'low':
		$texteRecherche = 'Recherche sur le minimum ';
		break;
	case 'all':
		$texteRecherche = 'Recherche sur toutes les données';
		break;
	}
	if ($choixRecherche != 'all') {
		// ex : 10_minute
		$tabPrecision = split('_', $precision);
		// Nombre de la precision
		$nbPrecision = $tabPrecision[0]; 
		// Indice de la precision : minute / seconde / hour etc.
		$indicePrecision = $tabPrecision[1]; 
		$pluriel = '';
		if ($nbPrecision > 1) {
			$pluriel = 's';
		}
		switch ($indicePrecision) {
		case 'seconde':
			$texteRecherche .= "par $nbPrecision seconde".$pluriel;
			break;
		case 'minute':
			$texteRecherche .= "par $nbPrecision minute".$pluriel;
			break;
		case 'hour':
			$texteRecherche .= "par $nbPrecision heure".$pluriel;
			break;
		case 'day':
			$texteRecherche .= "par $nbPrecision jour".$pluriel;
			break;
		case 'week':
			$texteRecherche .= "par $nbPrecision semaine".$pluriel;
			break;
		case 'month':
			$texteRecherche .= "par $nbPrecision mois";
			break;
		}
	}
	return($texteRecherche);
}

// Fonction retournant la date de début ou de fin de période pour une localisation en comparant une date passée en paramétre
private function getDatePeriode($dateAnalyse, $localisationId, $type) {
	$tabPeriode = $this->session->get('infoLimitePeriode');
	$timestamp_dateAnalyse = strtotime($this->reverseDate($dateAnalyse));
	// En cas de localisation = all : Risque d'erreur dans les données ( cause de module non trouvé car appartenant à un programme différent du programme courant de la localisation
	if ($localisationId == 'all') {
		return($dateAnalyse);
	}
	if ($type == 'debut') {
		$dateDebDeLaLoc = $tabPeriode[intval(preg_replace("/'/","",$localisationId))]['dateDeb'];
		$timestamp_dateDebDeLaLoc = strtotime($this->reverseDate($dateDebDeLaLoc));
		if (($dateDebDeLaLoc != null) && ($timestamp_dateAnalyse < $timestamp_dateDebDeLaLoc)) {
			return($this->reverseDate($dateDebDeLaLoc));
		}
	}
	if ($type == 'fin') {
		$dateFinDeLaLoc = $tabPeriode[intval(preg_replace("/'/","",$localisationId))]['dateFin'];
		$timestamp_dateFinDeLaLoc = strtotime($this->reverseDate($dateFinDeLaLoc));
		if (($dateFinDeLaLoc != null) && ($timestamp_dateAnalyse > $timestamp_dateFinDeLaLoc)) {
			return($this->reverseDate($dateFinDeLaLoc));
		}
	}
	return($dateAnalyse);
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



private function calculMoyennePonderee($OthersValues, $firstValue, $precision) {
	$sommeProd = 0;
	$sommeProd2 = 0;
	$sommeDiv = 0;
	$tabPondere = array();
	$indexTabPondere = 0;
	$cycleDeLaDonneePrecedente = 0;
	switch (strtolower($precision)){
		case('seconde'):
			$formatage = 'Y-m-d H:i:s';
			break;
		case('minute'):
			$formatage = 'Y-m-d H:i';
			break;
		case('heure'):
			$formatage = 'Y-m-d H';
			break;
		case('jour'):
			$formatage = 'Y-m-d';
			break;
		case('mois'):
			$formatage = 'Y-m';
			break;
	}
	$dateDeLaDonneePrecedente = null;
	$sommeDiv = 0;
	$sommeProd = 0;
	$sommeProd2 = 0;

	foreach($OthersValues as $ligneDuTableau) {
		$dateDeLaDonnee = new \DateTime($ligneDuTableau['horodatage']);
		if (isset($dateDeLaDonneePrecedente)) {
			// Si ce n'est pas la première donnée du tableau et si la date analysée est différente de la date précédente : On passe dans une nouvelle tranche - 
			//	On enregistre alors les valeurs du tableau pondérée de la tranche terminée + On débute la nouvelle tranche
			// Le delta d'une nouvelle tranche correponds à la différence entre la nouvelle date et l'heure de début de tranche
			if ($dateDeLaDonnee->format($formatage) != $dateDeLaDonneePrecedente->format($formatage)) {
				$tabPondere[$indexTabPondere] = array();
    			switch (strtolower($precision)){
    			    case('seconde'):
    			        $tabPondere[$indexTabPondere]['horodatage'] = $dateDeLaDonneePrecedente->format($formatage);
						$dateDeFinDePeriode = new \Datetime($tabPondere[$indexTabPondere]['horodatage']);
						$dateDeFinDePeriode->add(new \DateInterval('PT1S'));
    			        break;
    			    case('minute'):
        			    $tabPondere[$indexTabPondere]['horodatage'] = $dateDeLaDonneePrecedente->format($formatage).':00';
						$dateDeFinDePeriode = new \Datetime($tabPondere[$indexTabPondere]['horodatage']);
						$dateDeFinDePeriode->add(new \DateInterval('PT1M'));
        			    break;
       			 	case('heure'):
            			$tabPondere[$indexTabPondere]['horodatage'] = $dateDeLaDonneePrecedente->format($formatage).':00:00';
						$dateDeFinDePeriode = new \Datetime($tabPondere[$indexTabPondere]['horodatage']);
						$dateDeFinDePeriode->add(new \DateInterval('PT1H'));
            			break;
        			case('jour'):
            			$tabPondere[$indexTabPondere]['horodatage'] = $dateDeLaDonneePrecedente->format($formatage).' 00:00:00';
						$dateDeFinDePeriode = new \Datetime($tabPondere[$indexTabPondere]['horodatage']);
						$dateDeFinDePeriode->add(new \DateInterval('P1D'));
            			break;
					case('mois'):
						$tabPondere[$indexTabPondere]['horodatage'] = $dateDeLaDonneePrecedente->format($formatage).'01 00:00:00';
                        $dateDeFinDePeriode = new \Datetime($tabPondere[$indexTabPondere]['horodatage']);
                        $dateDeFinDePeriode->add(new \DateInterval('P1M'));
    			}
            	$deltaT = $dateDeFinDePeriode->getTimestamp() - ($dateDeLaDonneePrecedente->getTimestamp() + ($cycleDeLaDonneePrecedente / 100));
            	$sommeProd += ($valeur1Precedente * $deltaT);
            	$sommeProd2 += ($valeur2Precedente * $deltaT);
            	$sommeDiv += $deltaT;
				$tabPondere[$indexTabPondere]['cycle'] = 0;
				$tabPondere[$indexTabPondere]['valeur1'] = $sommeProd / $sommeDiv; 	// moyenne pondérée 1
				$tabPondere[$indexTabPondere]['valeur2'] = $sommeProd2 / $sommeDiv; // moyenne pondérée 2
				$indexTabPondere ++;

				// Calcul des valeurs de la nouvelle tranche. 
                $sommeProd = 0;
                $sommeProd2 = 0;
                $sommeDiv = 0;
            	switch (strtolower($precision)) {
                	case('seconde'):
                	    $dateDeDebutDePeriode = new \Datetime($dateDeLaDonnee->format($formatage));
                	    $dateDeDebutDePeriode->sub(new \DateInterval('PT1S'));
                	    break;
                	case('minute'):
                	    $dateDeDebutDePeriode = new \Datetime($dateDeLaDonnee->format($formatage).':00');
                	    break;
                	case('heure'):
                	    $dateDeDebutDePeriode = new \Datetime($dateDeLaDonnee->format($formatage).':00:00');
                	    break;
                	case('jour'):
                	    $dateDeDebutDePeriode = new \Datetime($dateDeLaDonnee->format($formatage).' 00:00:00');
                	    break;
					case('mois');
						$dateDeDebutDePeriode = new \Datetime($dateDeLaDonnee->format($formatage).'01 00:00:00');
						break;
            	}	
            	$deltaT = $dateDeLaDonnee->getTimestamp() - $dateDeDebutDePeriode->getTimestamp();
                $sommeProd += ($valeur1Precedente * $deltaT);
                $sommeProd2 += ($valeur2Precedente * $deltaT);
                $sommeDiv += $deltaT;
			} else {
            	$deltaT = ($dateDeLaDonnee->getTimestamp() + ($ligneDuTableau['cycle'] / 100)) - ($dateDeLaDonneePrecedente->getTimestamp() + ($cycleDeLaDonneePrecedente / 100));
            	// Ce cas ne doit pas arriver car on enregistre au minimum une donnée par cycle !
            	if ($deltaT == 0) {
            	    if ($ligneDuTableau['cycle'] == 0) {
            	        $deltaT = '0.01';
            	    } else {
            	        $deltaT = $ligneDuTableau['cycle'] / 100;
            	    }
            	}
            	$sommeProd += ($valeur1Precedente * $deltaT);
            	$sommeProd2 += ($valeur2Precedente * $deltaT);
            	$sommeDiv += $deltaT;
			}
		} else { 
            switch (strtolower($precision)){
                case('seconde'):
                    $dateDeDebutDePeriode = new \Datetime($dateDeLaDonnee->format($formatage));
                    $dateDeDebutDePeriode->sub(new \DateInterval('PT1S'));
                    break;
                case('minute'):
                    $dateDeDebutDePeriode = new \Datetime($dateDeLaDonnee->format($formatage).':00');
                    break;
                case('heure'):
                    $dateDeDebutDePeriode = new \Datetime($dateDeLaDonnee->format($formatage).':00:00');
                    break;
                case('jour'):
                    $dateDeDebutDePeriode = new \Datetime($dateDeLaDonnee->format($formatage).' 00:00:00');
                    break;
				case('mois'):
					$dateDeDebutDePeriode = new \Datetime($dateDeLaDonnee->format($formatage).'01 00:00:00');
					break;
            }
			$deltaT = $dateDeLaDonnee->getTimestamp() - $dateDeDebutDePeriode->getTimestamp();
			if (isset($firstValue) && !empty($firstValue)) {
				$sommeProd = ($firstValue[0]['valeur1'] * $deltaT);
				$sommeProd2 = ($firstValue[0]['valeur2'] * $deltaT);
				$sommeDiv = $deltaT;
			}
		}
		$valeur1Precedente = $ligneDuTableau['valeur1'];
		$valeur2Precedente = $ligneDuTableau['valeur2'];
		$dateDeLaDonneePrecedente = $dateDeLaDonnee;
		$cycleDeLaDonneePrecedente = $ligneDuTableau['cycle'];
	}

	//Si le dernier horodatage est identique à l'avant dernier horodatage, il nous faut enregistrer la derniere valeur du tableau pondéré
	if ($dateDeLaDonnee->format($formatage) == $dateDeLaDonneePrecedente->format($formatage)) {
		// Dernière valeur du tableau pondéré
		$tabPondere[$indexTabPondere] = array();
    	switch (strtolower($precision)) {
    	    case('seconde'):
				$tabPondere[$indexTabPondere]['horodatage'] = $dateDeLaDonneePrecedente->format($formatage);
				$dateDeFinDePeriode = new \DateTime($tabPondere[$indexTabPondere]['horodatage']);
				$dateDeFinDePeriode->add(new \DateInterval('PT1S'));
    	        break;
    	    case('minute'):
				$tabPondere[$indexTabPondere]['horodatage'] = $dateDeLaDonneePrecedente->format($formatage).':00';
				$dateDeFinDePeriode = new \DateTime($tabPondere[$indexTabPondere]['horodatage']);
				$dateDeFinDePeriode->add(new \DateInterval('PT1M'));
    	        break;
    	    case('heure'):
				$tabPondere[$indexTabPondere]['horodatage'] = $dateDeLaDonneePrecedente->format($formatage).':00:00';
				$dateDeFinDePeriode = new \DateTime($tabPondere[$indexTabPondere]['horodatage']);
				$dateDeFinDePeriode->add(new \DateInterval('PT1H'));
    	        break;
    	    case('jour'):
				$tabPondere[$indexTabPondere]['horodatage'] = $dateDeLaDonneePrecedente->format($formatage).' 00:00:00';
				$dateDeFinDePeriode = new \DateTime($tabPondere[$indexTabPondere]['horodatage']);
				$dateDeFinDePeriode->add(new \DateInterval('P1D'));
    	        break;
			case('mois'):
				$tabPondere[$indexTabPondere]['horodatage'] = $dateDeLaDonneePrecedente->format($formatage).'01 00:00:00';
                $dateDeFinDePeriode = new \DateTime($tabPondere[$indexTabPondere]['horodatage']);
                $dateDeFinDePeriode->add(new \DateInterval('P1M'));
                break;
    	}
        $deltaT = $dateDeFinDePeriode->getTimestamp() - ($dateDeLaDonneePrecedente->getTimestamp() + ($cycleDeLaDonneePrecedente / 100));
        $sommeProd += ($valeur1Precedente * $deltaT);
        $sommeProd2 += ($valeur2Precedente * $deltaT);
        $sommeDiv += $deltaT;
    	$tabPondere[$indexTabPondere]['cycle'] = 0;
    	$tabPondere[$indexTabPondere]['valeur1'] = $sommeProd / $sommeDiv;  	// moyenne pondérée 1
    	$tabPondere[$indexTabPondere]['valeur2'] = $sommeProd2 / $sommeDiv;  	// moyenne pondérée 2
	}	
	return $tabPondere;
}

// Fonction qui change la liste des requêtes après selection d'une requête enregistrée
public function changeListeReqGraphiqueAction() {
	$this->constructeur();
    // On recherche la requête perso à afficher
    $id_requete = $this->session->get('graphique_requete_selected');
    $ent_requete = $this->em->getRepository('IpcConfigurationBundle:Requete')->find($id_requete);
    // On modifie la variable de session avec la liste des requêtes de la requête perso
    $this->session->set('liste_req_pour_graphique', json_decode($ent_requete->getRequete(), true));
    // On renvoi la page d'accueil de Listing
    return $this->redirect($this->generateUrl('ipc_accueilGraphique'));
}


}
