<?php
//src/Ipc/RapportsBundle/Controller/RapportsController.php
namespace Ipc\RapportsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerAware;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\FOSUserEvents;

use Ipc\ProgBundle\Entity\Site;
use Ipc\ProgBundle\Entity\Donnee;
use Ipc\ProgBundle\Entity\Rapport;
use Ipc\ProgBundle\Entity\Localisation;
use Ipc\ProgBundle\Entity\FichierRapport;

use Ipc\ConfigurationBundle\Form\Type\RapportType;
use Ipc\ConfigurationBundle\Form\Type\ModifyRapportType;
use Ipc\ConfigurationBundle\Form\Type\FichierRapport2Type;

use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormError;



class RapportsController extends Controller {

private $service_configuration;
private $session;
private $userLabel;
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
private $translator;

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
    $this->userLabel = $this->session->get('label');
	// On récupère le service translator
	$this->translator = $this->get('translator');
    $this->fillnumbers = $this->get('ipc_prog.fillnumbers');
    $this->tab_modules = array();
}


public function indexAction(Request $request) {
    $this->initialisation();
    return  $this->render('IpcRapportsBundle:Rapports:index.html.twig');
}


public function interventionsAction($idIntervention) {
    $this->initialisation();
    if ($idIntervention != 0) {
        // Si un identifiant est fournit recherche du rapport pour modification
        $rapport = $this->em->getRepository('IpcProgBundle:Rapport')->find($idIntervention);
        // ... et suppression des informations concernant les indications du rapports
        $indicationsRapport = $rapport->getRapport();
        $rapport->setRapport('');
    } else {
        $rapport = new Rapport();
    }
    $categorieIntervention = null;
    $numModuleIntervention = null;
    $numMessageIntervention = null;
	$maxUploadedFileSize = ini_get('upload_max_filesize');
    // Définition du login du rapport en fonction du login d'accès au site boilerBox
    if ($this->userLabel != null) {
        $rapport->setLogin($this->userLabel);
        $rapport->setNomTechnicien($this->userLabel);
    } else {
        if ($this->get('security.context')->isGranted('ROLE_ADMIN_LTS')) {
            $rapport->setLogin('Administrateur Lci');
        } else if ($$this->get('security.context')->isGranted('ROLE_ADMIN_LTS')) {
            $rapport->setLogin('Administrateur local');
        } else if ($this->get('security.context')->isGranted('ROLE_SUPERVISEUR')) {
            $rapport->setLogin('Superviseur local');
        } else if ($this->get('security.context')->isGranted('ROLE_TECHNICIEN_LTS')) {
            $rapport->setLogin('Technicien local');
        } else if ($this->get('security.context')->isGranted('ROLE_TECHNICIEN')) {
            $rapport->setLogin('Technicien Lci');
        } else if ($this->get('security.context')->isGranted('ROLE_USER')) {
            $rapport->setLogin('Utilisateur local');
        } else {
            $rapport->setLogin('Utilisateur indéfini');
        }
    }
    $connexion = $this->get('ipc_prog.connectbd');
    $dbh = $connexion->getDbh();
    // Récupération de la liste des localisations sous la forme d'un tableau : ID - numéro - adresseIp
    $liste_localisations = null;
    $entitySite = null;
    $entitiesLocalisation = null;
    $tmp_site = new Site();
    $site_id = $tmp_site->SqlGetIdCourant($dbh);
    $entitySite = $this->em->getRepository('IpcProgBundle:Site')->find($site_id);
    $entitiesLocalisation = $entitySite->getLocalisations();
    if (count($this->session->get('tablocalisations')) == 0) {
        $tmp_localisation = new Localisation();
        $liste_localisations = $this->em->getRepository('IpcProgBundle:Localisation')->SqlGetLocalisation($dbh, $site_id);
        $this->session->set('tablocalisations', $liste_localisations);
    } else {
        $liste_localisations = $this->session->get('tablocalisations');
    }
    if ($idIntervention == 0) {
        $form = $this->createForm(new RapportType(), $rapport);
    } else {
        $form = $this->createForm(new ModifyRapportType(), $rapport);
    }
    $requete = $this->get('request');
    //  Vérification que la taille des variables du paramètre ne dépasse pas la valeur maximum autorisée
    if (isset($_SERVER['CONTENT_LENGTH'])) {
        $varGetSize = $_SERVER['CONTENT_LENGTH'] / 1024 / 1024;
        if ( $varGetSize > $maxUploadedFileSize ) {
			$tmpTextInfo = $this->translator->trans('info.rapport.tailleDocuments', array('%maxUploadedFileSize%' => $maxUploadedFileSize));
            $requete->getSession()->getFlashBag()->add('info', $tmpTextInfo);
			//"Les documents passés en paramètres sont trop volumineux. Taille maximum autorisé : $maxUploadedFileSize");
            if ($idIntervention == 0) {
                return $this->redirect($this->generateUrl('ipc_modify_intervention'));
            } else {
                return $this->redirect($this->generateUrl('ipc_view_interventions'));
            }
        }
    }

	// Création d'un nouveau rapport
    if ($form->handleRequest($requete)->isValid()) {
        if ($idIntervention == 0) {
            // Récupération dans les configurations de la catégorie, du numéro de module et du numéro de message du trigramme du module d'intervention
            $trigrammeIntervention = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('trigramme_intervention');
            if ($trigrammeIntervention == null) {
			 	$tmpTextInfo = $this->translator->trans('info.rapport.trigrammeIntervention.absent_configuration');
                $requete->getSession()->getFlashBag()->add('info', $tmpTextInfo);
                return $this->redirect($this->generateUrl('ipc_prog_homepage'));
            }
            $pattern = '/^(..)(..)(..)$/';
            if (preg_match($pattern, $trigrammeIntervention->getValeur(), $tab_trigramme)) {
                $categorieIntervention = $tab_trigramme[1];
                $numModuleIntervention = $tab_trigramme[2];
                $numMessageIntervention= $tab_trigramme[3];
            } else {
				$tmpTextInfo = $this->translator->trans('info.rapport.trigrammeIntervention.erreur_configuration', array('%trigrammeIntervention%' => $trigrammeIntervention->getValeur()));
				$requete->getSession()->getFlashBag()->add('info', $tmpTextInfo);
                return $this->redirect($this->generateUrl('ipc_prog_homepage'));
            }
            // Inscription d'une donnée indiquant la création d'un nouveau rapport d'intervention en base de donnée
            // Si la Radiobox est cochée l'intervention concerne une localisation spécifique : Enregistrement de la localisation dans les paramètres du rapport.
            if ($_POST['Rapport']['site'] == 1) {
                // Récupération du trigramme du module dans les configurations
                $module = $this->em->getRepository('IpcProgBundle:Module')->findOneBy(array('categorie' => $categorieIntervention, 'numeroModule' => $numModuleIntervention, 'numeroMessage' => $numMessageIntervention, 'mode' => $rapport->getLocalisation()->getMode()));
                if ($module == null) {
					$tmpTextInfo = $this->translator->trans('info.rapport.trigrammeIntervention.absent_base', array('%trigrammeIntervention%' => $trigrammeIntervention->getValeur()));
					$requete->getSession()->getFlashBag()->add('info', $tmpTextInfo);
                    return $this->redirect($this->generateUrl('ipc_prog_homepage'));
                }
                // Inscription d'une nouvelle donnée de type Rapport d'intervention
                $donnee = new Donnee();
                $donnee->setHorodatage($rapport->getDateRapport());
                $donnee->setCycle('00');
                $donnee->setValeur1('0');
                $donnee->setValeur2('0');
                // L'identifiant du fichier est déclaré à 1
                $donnee->setFichierId('1');
                $donnee->setModuleId($module->getId());
                $donnee->setLocalisationId($rapport->getLocalisation()->getId());
                $this->em->persist($donnee);
            } else {
                // On retire du rapport la localisation associée
                $rapport->removeLocalisation();
                // Si l'intervention concerne le site: On entre les informations de module pour chaque localisation du site courant
                foreach ($entitiesLocalisation as $localisation) {
                    // Récupération du trigramme du module dans les configurations
                    $module = $this->em->getRepository('IpcProgBundle:Module')->findOneBy(array('categorie' => $categorieIntervention, 'numeroModule' => $numModuleIntervention, 'numeroMessage' => $numMessageIntervention, 'mode' => $localisation->getMode()));
                    // Gestion des l'absence de mode pour la localisation courante
                    if ($module == null) {
						$tmpTextInfo = $this->translator->trans('info.rapport.trigrammeIntervention.absent_base', array('%trigrammeIntervention%' => $trigrammeIntervention->getValeur()));
						$requete->getSession()->getFlashBag()->add('info', $tmpTextInfo);
                        return $this->redirect($this->generateUrl('ipc_prog_homepage'));
                    }
                    // Inscription d'une nouvelle donnée de type Rapport d'intervention
                    $donnee = new Donnee();
                    $donnee->setHorodatage($rapport->getDateRapport());
                    $donnee->setCycle('00');
                    $donnee->setValeur1('0');
                    $donnee->setValeur2('0');
                    // L'identifiant du fichier est déclaré à 1
                    $donnee->setFichierId('1');
                    $donnee->setModuleId($module->getId());
                    $donnee->setLocalisationId($localisation->getId());
                    $this->em->persist($donnee);
                }
            }
            $rapport->setSite($entitySite);
        } else {
            // Par défaut le rapport n'est composé que du caractère '-' (Car un rapport vide n'est pas accepté par le formulaire)
            // Ce caractère correspond donc à un rapport vide
            $newIndicationRapport = $indicationsRapport."\n\nAjout du ".date('d-m-Y H:i')."\n".$rapport->getRapport();
            $rapport->setRapport($newIndicationRapport);
        }
        // Ajout de fichiers au rapport
        foreach ($rapport->getFichiersrapport() as $fichierDuRapport) {
			if ($fichierDuRapport->getFile() != null) {
            	$fichierDuRapport->setNom($fichierDuRapport->getFile()->getClientOriginalName());
            	$rapport->addReverseRapport($fichierDuRapport);
			} else {
				$rapport->removeFichiersrapport($fichierDuRapport);
			}
        }
        $this->em->persist($rapport);
        $this->em->flush();
        //  Gestion des fichiers liés au rapport
        // Déplacement des rapports dans le dossier des rapports
        foreach ($rapport->getFichiersrapport() as $FichierDuRapport) {
			if ($FichierDuRapport->getFile() != null) {
            	$retourDeplacement = $FichierDuRapport->deplacement();
            	if ($retourDeplacement == 1) {
					$tmpTextInfo = $this->translator->trans('info.erreur.import_fichier');
            	    $requete->getSession()->getFlashBag()->add('info', $tmpTextInfo);
            	    if ($idIntervention == 0) {
            	        return $this->redirect($this->generateUrl('ipc_prog_homepage'));
            	    } else {
            	        return $this->redirect($this->generateUrl('ipc_view_interventions'));
            	    }
            	}
			}
        }
        if ($idIntervention == 0) {
			$tmpTextInfo = $this->translator->trans('info.rapport.enregistrer');
			$requete->getSession()->getFlashBag()->add('info', $tmpTextInfo);	
            return $this->redirect($this->generateUrl('ipc_view_interventions'));
        } else {
			$tmpTextInfo = $this->translator->trans('info.rapport.modifier');
			$requete->getSession()->getFlashBag()->add('info', $tmpTextInfo);
            return $this->redirect($this->generateUrl('ipc_view_interventions'));
        }
    } else {
        // A l'apparition du formulaire il n'y a pas d'erreur. Par la suite si une erreur est detectée on affiche les erreurs
        // Pour afficher les erreurs il ne faut pas recréer le formulaire
        $pattern_error = '/ERROR/';
        if (preg_match($pattern_error, $form->getErrorsAsString())) {
            // Suppression des fichiers précédemment désignés pour l'importation
            $rapport->removeAllFichiersrapport();
            $form = $this->createForm(new RapportType(), $rapport);
            // Réinstanciation des erreurs détectées
            $errors = $this->get('validator')->validate($rapport);
            $result = '';
            foreach ($errors as $error) {
                $form->get($error->getPropertyPath())->addError(new FormError($error->getMessage()));
            }
        }
    }
	$maxUploadedFileSize = ini_get('upload_max_filesize');
    if ($idIntervention == 0) {
        $response = new Response($this->renderView('IpcRapportsBundle:Rapports:addRapport.html.twig', array(
            'form' => $form->createView(),
            'liste_localisations' => $liste_localisations,
			'maxUploadedFileSize' => $maxUploadedFileSize
        )));
    } else {
        $response = new Response($this->renderView('IpcRapportsBundle:Rapports:modifyRapport.html.twig', array(
            'form' => $form->createView(),
            'entityRapport' => $rapport,
            'indicationRapport' => $indicationsRapport,
			'maxUploadedFileSize' => $maxUploadedFileSize
        )));
    }
    $response->setPublic();
    $response->setETag(md5($response->getContent()));
    return $response;
}



//  Voir les rapports d'intervention
public function viewInterventionsAction() {
	$this->constructeur();
    $this->initialisation();
    $requete = $this->get('request');
    $entityFichierRapport = new fichierRapport();
    $form = $this->createForm(new FichierRapport2Type(), $entityFichierRapport);
    if ($form->handleRequest($requete)->isValid()) {
        $idRapport = $_POST['ipc_configurationbundle_fichierrapport']['idRapport'];
        $nomFichier = $entityFichierRapport->getNom();
        $chemin_fichier = $entityFichierRapport->getInterventionsDir().$idRapport.'_'.$nomFichier;
        if (is_file($chemin_fichier)) {
            $response = new Response();
            $response->setContent(file_get_contents($chemin_fichier));
            $response->headers->set('Content-Type', 'application/force-download');
            $response->headers->set('Content-disposition', 'attachment; filename='.$nomFichier);
            $response->headers->set('Content-length', filesize($chemin_fichier));
            return $response;
        } else {
            $this->get("session")->getFlashBag()->add('info', "Fichier [$nomFichier] non trouvé sur le serveur");
        }
    }
    // Récupération des rapports d'interventions
    $nombreEntitiesInterventions = $this->em->getRepository('IpcProgBundle:Rapport')->myCount();
    if ($nombreEntitiesInterventions == 0) {
		$tmpTextInfo = $this->translator->trans('info.rapport.none');
		$this->get("session")->getFlashBag()->add('info', $tmpTextInfo);
        $response = new Response($this->renderView('IpcRapportsBundle:Rapports:index.html.twig'));
        $response->setPublic();
        $response->setETag(md5($response->getContent()));
        return $response;
    }
    $entitiesInterventions = $this->em->getRepository('IpcProgBundle:Rapport')->findBy(array(), array('dateRapport'=>'DESC'));
    // Création du tableau associé
    $tabInterventions = array();
    $indiceTableau = 0;
    foreach ($entitiesInterventions as $entityIntervention) {
        $nouvelIdentifiant = $entityIntervention->getId();
        $tabInterventions[$indiceTableau] = array();
        $tabInterventions[$indiceTableau]['dateRapport'] = $entityIntervention->getDateRapport();
        $tabInterventions[$indiceTableau]['site'] = $entityIntervention->getSite()->getIntitule();
        if ($entityIntervention->getLocalisation() != null) {
            $tabInterventions[$indiceTableau]['localisation'] = $entityIntervention->getLocalisation()->getDesignation();
        } else {
            $tabInterventions[$indiceTableau]['localisation'] = null;
        }
        $tabInterventions[$indiceTableau]['nomTechnicien'] = $entityIntervention->getNomTechnicien();
        $tabInterventions[$indiceTableau]['titre'] = $entityIntervention->getTitre();
        $tabInterventions[$indiceTableau]['login'] = $entityIntervention->getLogin();
        $tabInterventions[$indiceTableau]['rapport'] = $entityIntervention->getRapport();
        $tabInterventions[$indiceTableau]['fichiers'] = array();
        foreach ($entityIntervention->getFichiersrapport() as $fichier) {
            $tabInterventions[$indiceTableau]['fichiers'][] = $fichier->getNom();
        }
        $tabInterventions[$indiceTableau]['id'] = $nouvelIdentifiant;
        $indiceTableau ++;
    }
    $response = new Response($this->renderView('IpcRapportsBundle:Rapports:viewRapports.html.twig', array(
        'form' => $form->createView(),
        'tabInterventions' => $tabInterventions
    )));
    $response->setPublic();
    $response->setETag(md5($response->getContent()));
    return $response;
}



public function searchInterventionsAction() {
	$this->constructeur();
    // Lecture des mots clés
    $keyWords = $_GET['texte'];
    // Suppression des espaces multiples
    $keyWords = preg_replace('/\s+/', '|', $keyWords);
    // Création d'un tableau contenant les caractères à rechercher (100 mots max)
    $tabWords = explode('|', $keyWords, 100);
    $tabMotTrouves = array();
    // Recherche des mots clés dans les textes des Rapports
    $entitiesInterventions = $this->em->getRepository('IpcProgBundle:Rapport')->findBy(array(), array('dateRapport' => 'DESC'));
    foreach ($entitiesInterventions as $entityIntervention) {
        $motTrouve = true;
        foreach ($tabWords as $key => $word) {
            // Si le mot n'est trouvé ni dans le rapport, ni dans le titre : recherche dans le rapport suivant
            $word_lower = strtolower($word);
            $pattern_word = "/$word_lower/";
            if ((!preg_match($pattern_word, strtolower($entityIntervention->getRapport()))) && (! preg_match($pattern_word, strtolower($entityIntervention->getTitre())))) {
                $motTrouve = false;
                break;
            }
        }
        // Si à la sortie de la vérification, tous les mots sont trouvés dans le rapports : Enregistrement de l'id du rapport
        if ($motTrouve == true) {
            $tabMotTrouves[] = $entityIntervention->getId();
        }
    }
    // Tri du tableau
    sort($tabMotTrouves);
    echo json_encode($tabMotTrouves);
    return new Response();
}

// Fonction qui exporte les rapports. 
// Si une date de début est donnée : Récupération des rapports depuis cette date.
// Si une date de fin est donnée : Récupération des rapports jusqu'à cette date.
public function exportRapportsAction(){
	$this->constructeur();
	$entity_site_courant = $this->em->getRepository('IpcProgBundle:Site')->myFindCourant();
	$intitule_rapport_export = 'Export des rapports du site ['.$entity_site_courant->getIntitule().']';
	$date_fichier = '';
	if ($_GET['date_debut'] != '') {
		$date_debut = new \Datetime($_GET['date_debut']);
		if ($_GET['date_fin'] != '') {
			// Date de début et date de fin
			$date_fin = new \Datetime($_GET['date_fin']);
			$intitule_rapport_export .= ' entre le '.date('d/m/Y', $date_debut->getTimestamp()).' et le '.date('d/m/Y', $date_fin->getTimestamp());
			$date_fichier = '_du_'.date('dmY', $date_debut->getTimestamp()).'_au_'.date('dmY', $date_fin->getTimestamp());
	    	$tab_des_rapports = $this->em->getRepository('IpcProgBundle:Rapport')->myFindBetween($date_debut, $date_fin);
		} else {
			// Seulement la date de début
			$intitule_rapport_export .= ' depuis le '.date('d/m/Y', $date_debut->getTimestamp());
			$date_fichier = '_depuis_'.date('dmY', $date_debut->getTimestamp());
			$tab_des_rapports = $this->em->getRepository('IpcProgBundle:Rapport')->myFindAfter($date_debut);
		}
	} else {
		// Seulement la date de fin
		if ($_GET['date_fin'] != '') {
			$date_fin = new \Datetime($_GET['date_fin']);
			$intitule_rapport_export .= ' avant le '.date('d/m/Y', $date_fin->getTimestamp());
			$date_fichier = '_avant_'.date('dmY', $date_fin->getTimestamp());
			$tab_des_rapports = $this->em->getRepository('IpcProgBundle:Rapport')->myFindBefore($date_fin);
		} else {
			// Aucune date : Tous les rapports
			$tab_des_rapports = $this->em->getRepository('IpcProgBundle:Rapport')->myFindAll();
		}
	}

	// Création du fichier CSV
	$chemin = 'uploads/tmp/';
	$fichier_csv_name = $entity_site_courant->getAffaire().'_rapports'.$date_fichier;
	$delimiteur = ';';
	$fichier_csv = fopen($chemin.$fichier_csv_name, 'w+');

	// fprintf above writes file header for correct encoding
	fprintf($fichier_csv, chr(0xEF).chr(0xBB).chr(0xBF));

	// En-tête du fichier
	fputcsv($fichier_csv, array($intitule_rapport_export), $delimiteur);

	// En-tête des colonnes
	$tab_ligne_csv = array();
	$tab_ligne_csv[] = '';
	$tab_ligne_csv[] = 'Numéro';
	$tab_ligne_csv[] = 'Site';
    $tab_ligne_csv[] = 'Localisation';
    $tab_ligne_csv[] = 'Date';
    $tab_ligne_csv[] = 'Auteur';
    $tab_ligne_csv[] = 'Titre';
    $tab_ligne_csv[] = 'Contenu';
    $tab_ligne_csv[] = 'Fichiers joints';
	fputcsv($fichier_csv, $tab_ligne_csv, $delimiteur);

	// Colonnes du fichier	
	foreach($tab_des_rapports as $rapport) {
		$tab_ligne_csv = array();
		$tab_ligne_csv[] = '';
		$tab_ligne_csv[] = $rapport['id'];
		$tab_ligne_csv[] = $rapport['site']['intitule'];
		$tab_ligne_csv[] = $rapport['localisation']['designation'];
		$tab_ligne_csv[] = $rapport['dateRapport']->format('d-m-Y H:i:s');
		$tab_ligne_csv[] = $rapport['nomTechnicien'];
		$tab_ligne_csv[] = $rapport['titre'];
		$tab_ligne_csv[] = $rapport['rapport'];
		// Ajout des fichiers joints	
		$liste_des_fichiers = '';
		foreach ($rapport['fichiersrapport'] as $fichier_rapport) {	
			$liste_des_fichiers .= '['.$fichier_rapport['nom'].'] - ';
		}
		if ($liste_des_fichiers != '') {
			$liste_des_fichiers = trim($liste_des_fichiers);
			$liste_des_fichiers = substr($liste_des_fichiers,0, -1);
		}
		$tab_ligne_csv[] = $liste_des_fichiers;
		fputcsv($fichier_csv, $tab_ligne_csv, $delimiteur);
	}
	fclose($fichier_csv);

	// Envoi de la réponse
	$response = new Response();
    $response->headers->set('Content-Type', 'application/force-download');
    $response->headers->set('Content-Disposition', 'attachment;filename="'.$fichier_csv_name.'"');
    $response->headers->set('Content-Length', filesize($chemin.$fichier_csv_name));
    $response->setContent(file_get_contents($chemin.$fichier_csv_name));
    $response->setCharset('UTF-8');
	return $response;
}


}
