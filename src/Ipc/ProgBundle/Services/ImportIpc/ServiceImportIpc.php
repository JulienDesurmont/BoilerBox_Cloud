<?php
//src/Ipc/ProgBundle/Services/ImportIpc/ServiceImportIpc.php

namespace Ipc\ProgBundle\Services\ImportIpc;

use Ipc\ProgBundle\Entity\FichierIpc;
use Ipc\ProgBundle\Entity\Fichier;
use Ipc\ProgBundle\Entity\Site;
use Ipc\ProgBundle\Entity\Genre;
use Ipc\ProgBundle\Entity\Module;
use Ipc\ProgBundle\Entity\Mode;
use Ipc\ProgBundle\Entity\InfosLocalisation;
use Ipc\ProgBundle\Entity\Configuration;
use Ipc\ProgBundle\Entity\Localisation;

use Symfony\Component\HttpFoundation\Response;

class ServiceImportIpc {
protected $dbh;
protected $em;
protected $multi_sites;
protected $srv_log;
protected $srv_email;
protected $srv_fillNumbers;
protected $srv_security_context;

public function __construct($srv_connexion, $srv_log, $srv_email, $srv_fillNumbers, $srv_security_context) {
	$this->srv_log = $srv_log;
	$this->fichierLog = 'importIpc.log';
	$this->dbh = $srv_connexion->getDbh();
    $this->em = $srv_connexion->getManager();
	$this->srv_email = $srv_email;
	$this->srv_fillNumbers = $srv_fillNumbers;
	$this->srv_security_context = $srv_security_context;
	$configuration = new Configuration();		
	$this->multi_sites = $configuration->SqlGetParam($this->dbh, 'multi_sites');
}


// Remplacement des \ par \\ pour prise en compte lors des insertions et modifications
protected function modifySlash($texte) {
	return(str_replace('\\', '\\\\', $texte));
}

// Importe le fichier Table_echange_IPC
// Le fichier doit être nommé de la sorte : tei_site_numeroLocalisation_#mode#_date ( exemple tei_C721_01_#ee_fr_00#_20180726153220 )
// Le fichier doit être encodé en utf-8
// -- obsolete - si le site n'est pas multi-site : Indication de "noprog" en lieu et place du mode
public function importation(FichierIpc $fichieripc, $dateDebutMode) {
	// Suppression de la limite de temps d'execution du script car il fait plus de 300 secondes d'execution
    set_time_limit(0);
    ignore_user_abort(1);

	$dbh = $this->dbh;
	$liste_insertion = '';
	$liste_genres = array();
	$liste_modules = array();
	$nomfichier = $fichieripc->getNomdOrigine();
	$pattern = '/^tei_(.+?)_(.+?)_#(.+?)#_.+?$/';
	// Si le nom est correct : déplacement dans le dossier des fichiers CSV
	// Vérification des informations écrites dans le nom du fichier
	if(preg_match($pattern, $nomfichier, $tabNomFichier)) {
		// Récupération des informations du site / de la localisation et du code programme
		$affaire_site = $tabNomFichier[1];
		$numLocalisation_ipc = $tabNomFichier[2];
		$designationMode_ipc = $tabNomFichier[3]; 
		// Vérification que le site indiqué dans le nom du fichier existe
		$site_ipc = $this->em->getRepository('IpcProgBundle:Site')->findOneByAffaire($affaire_site);
		$affaire_site = ucfirst($affaire_site);	
		if (empty($site_ipc)) {
			return("Erreur : Site $affaire_site non trouvé");
		}
		// Vérification que la localisation existe pour le site et le numero de localisation indiqués dans le nom du fichier
		$localisation_ipc 	= $this->em->getRepository('IpcProgBundle:Localisation')->findOneBy(array('site' => $site_ipc, 'numeroLocalisation' => $numLocalisation_ipc));

		if (empty($localisation_ipc)) {
			return("Erreur : Localisation $numLocalisation_ipc non trouvée pour le site $affaire_site."); 
		}
		// Si la gestion du multi-site est prise en compte et si le mode indiqué dans le nom du fichier n'existe pas en base, création de celui-ci
		if ($this->multi_sites == true) {
			// Création du nouveau mode en base si il n'existe pas
			$mode_ipc = $this->em->getRepository('IpcProgBundle:Mode')->findOneByDesignation($designationMode_ipc);
			if (empty($mode_ipc)) {
				$mode_ipc = new Mode();
				$mode_ipc->setDesignation($designationMode_ipc);
				$mode_ipc->setDescription('Non renseignée');
			}
		} else {
			// En mode non multi-sites le nom du programme doit être noProg
			if ($designationMode_ipc != 'noprog') {
				return("Erreur de nomenclature du fichier : Version non multi-sites en cours. Veuillez modifier le nom du programme ( $designationMode_ipc )  en 'noProg' svp.");
			}
			$mode_ipc = $this->em->getRepository('IpcProgBundle:Mode')->findOneByDesignation("noprog");
			if ($mode_ipc == null) {
				$mode_ipc = new Mode();
				$mode_ipc->setDesignation('noprog');
				$mode_ipc->setDescription('No programme');
			}
		}
		// Affectation du mode à la localisation
		$localisation_ipc->setMode($mode_ipc);
	} else {
		return("Erreur : Nomenclature du fichier attendue  : TEI_CodeAffaire_NumLocalisation_#CodeProgramme#_Horodatage. ( Fichier reçu ".$nomfichier." )");
	}


	$fichieripc->setNom($nomfichier);
	$fichieripc->deplacement();
	// On vérifie si le fichier a déjà été importé
	$lastFichierIpc = $this->em->getRepository('IpcProgBundle:FichierIpc')->findOneByNom($nomfichier);
	if ($lastFichierIpc != null) {
		// Si le nom du fichier est trouvé en base, modification de l'horodatage de traitement
		$fichieripc = $lastFichierIpc;
		$fichieripc->updateDateTraitement();
	} else {
		// Sinon : instanciation des paramètres du nouveau fichier
		$this->em->persist($fichieripc);
	}
	// Ajout du fichier ipc au mode si la gestion multi-sites est prise en compte
	if ($this->multi_sites == true) {
		$mode_ipc->setFichierIpc($fichieripc);
	}
	$this->em->flush();
	// Par cette fonction Déplacement du fichier téléchargé dans le répertoire csv
	// Retourne le contenu du fichier dans un tableau
	//  Si le tableau est vide : Soit le fichier est vide soit il n'est pas formaté en UTF-8
	$liste_messages = $fichieripc->getContenu();
	if (! isset($liste_messages[0])) {
		return("Erreur : Le fichier est vide ou n'est pas encodé en UTF-8");
	}
	$this->srv_log->setLog("Fichier [$nomfichier] téléchargé", $this->fichierLog);


	//	Récupération des informations concernant l'ancien programme de la localisation indiquée dans le nom du fichier 
	//  Si il n'y a pas d'information, la variable oldMode est mise à null SINON elle vaut le dernier mode (programme) de la localisation
	$oldInfosLocalisation = $this->em->getRepository('IpcProgBundle:infosLocalisation')->findBy(array('localisation' => $localisation_ipc), array('horodatageDeb' => 'DESC'), 1, 0);
	if ($oldInfosLocalisation != null) {
		$oldMode = $oldInfosLocalisation[0]->getMode();
	} else {
		$oldMode = null;
	}
	if ($dateDebutMode != 'maj') {
		// Création des informations concernant le nouveau mode de fonctionnement pour la localisation désignée
		// Recherche des infos concernant la localisation
		$infosLocalisation = new InfosLocalisation();
		$infosLocalisation->setLocalisation($localisation_ipc);
		$infosLocalisation->setMode($mode_ipc);
		$infosLocalisation->setHorodatageDeb(new \Datetime($dateDebutMode));


		if ($oldInfosLocalisation != null) {
			// Si une ancienne ligne concernant la localisation est trouvée en base
			// et si la date de début est < à la date donnée retour d'un message d'erreur
			// sinon mise à jour des informations de fin de programme précédent et de début de programme courant
			// Si l'ancien programme est identique au nouveau programme : Une mise à jour est requise.
			//  Si une mise à jour est demandée : Vérification que le mode est différent de l'ancien mode
			if ($mode_ipc->getDesignation() == $oldMode->getDesignation()) {
				return("Erreur : Ancien programme identique détecté : Veuillez selectionner l'option 'mise à jour de table d'échange' svp.");
			}
			$oldDateProgramme = $oldInfosLocalisation[0]->getHorodatageDeb();
			if (strtotime($infosLocalisation->getHorodatageDeb()->format('Y-m-d H:i:s')) <= strtotime($oldDateProgramme->format('Y-m-d H:i:s'))) {
				return("Erreur : La date donnée (".$infosLocalisation->getHorodatageDeb()->format('Y-m-d H:i:s').") est inférieure à la date du précédent programme (".$oldDateProgramme->format('Y-m-d H:i:s').")");
			}
			$oldInfosLocalisation[0]->setHorodatageFin(new \Datetime($dateDebutMode));
			// Définition de la localisation comme localisation courante
			$listeInfosDeLaLoc = $this->em->getRepository('IpcProgBundle:infosLocalisation')->findBy(array('localisation' => $localisation_ipc));
			// Réinitialisation des champs période d'analyse pour la localisation courante
			foreach ($listeInfosDeLaLoc as $infosDeLoc) {
				$infosDeLoc->setPeriodeCourante(false);
			}
		}
		// Définition de la nouvelle ligne d'information comme période d'analyse
		$infosLocalisation->setPeriodeCourante(true);
		$this->em->persist($infosLocalisation);
	} else {
		// Si une mise à jour du programme est demandée :
		// On vérifie que le programme (mode) existe bien en base et que sa déignation correspond à la désignation du programme à mettre à jour.
		 if ($oldMode == null) {
    	    return('Aucun ancien programme déctecté pour la localisation. Veuillez sélectionner l\'option "Nouveau programme" svp');
   	 	}
		if ($mode_ipc->getDesignation() != $oldMode->getDesignation()) {
			return("Erreur : Nouveau programme détecté : Impossible de mettre à jour (Ancien programme : ".$oldMode->getDesignation());
		}
		$listeInfosDeLaLoc = $this->em->getRepository('IpcProgBundle:infosLocalisation')->findBy(array('localisation' => $localisation_ipc));

		// Réinitialisation des champs période d'analyse pour la localisation courante
		foreach ($listeInfosDeLaLoc as $infosDeLoc) {
			$infosDeLoc->setPeriodeCourante(false);
		}
		// Définition de la période courante sur la dernière log trouvée pour le mode courant
		$oldInfosLocalisation[0]->setPeriodeCourante(true);
	}

	// Suppression des précédents modules liés à la localisation
	$localisation_ipc = $localisation_ipc->resetModules();
	$this->em->flush();


	$nbNewModules = 0;
	$nbUpdateModules = 0;
	// Pour chaque ligne du fichier IPC : Vérification que le genre, le module et le message existent en base
	foreach ($liste_messages as $tableau_message) {
		$numero_genre = $tableau_message['numero_genre'];
		$intitule_genre	= $tableau_message['intitule_genre'];
		// La recherche du genre se fait dans un tableau pour éviter les requêtes inutiles vers la bdd
		// Création d'un tableau (liste_genres) des genres trouvés dans le fichier IPC
		//	Lors de la création du tableau on vérifie si le genre existe en BD. Si il n'existe pas création de ce nouveau genre.
		if (! array_key_exists($tableau_message['numero_genre'], $liste_genres)) {
			$genre = $this->em->getRepository('IpcProgBundle:Genre')->findOneByNumeroGenre($numero_genre);
			if (empty($genre)) {
				$genre = new Genre();
				$genre->setNumeroGenre($numero_genre);
				$genre->setIntituleGenre($intitule_genre);
				$this->em->persist($genre);
				$this->em->flush();
				$genre = $this->em->getRepository('IpcProgBundle:Genre')->findOneByNumeroGenre($numero_genre);
				$this->srv_log->setLog('Nouveau Genre : '.$genre->getNumeroGenre()." - ".$genre->getIntituleGenre(), $this->fichierLog);
			}
			$liste_genres[$numero_genre] = $genre->getId();
		}
		$genre_id = $liste_genres[$numero_genre];
		// Remplacement des \ par \\ pour prise en compte lors des insertions et modifications
		$tableau_message['intitule_module']  = $this->modifySlash($tableau_message['intitule_module']);
		$tableau_message['intitule_message'] = $this->modifySlash($tableau_message['intitule_message']);
		// Cas d'erreur rencontrée lors de la mise en prod du 10 07 2014 : En cas de présence de double quote dans le message : Retour d'un message d'erreur
		$pattern = '/"/';
		if(preg_match($pattern, $tableau_message['intitule_message'])) {
			return("Erreur : Veuillez supprimer les doubles quotes dans les messages des modules svp\Erreur à la ligne ".$tableau_message['intitule_message']);
		}
		// Si un nouveau triplet est découvert on recherche si un module correspondant existe : Si oui on le modifie / Si non on le crée
		$triplet_module = $tableau_message['type_categorie'].$this->srv_fillNumbers->fillNumber($tableau_message['numero_module'],2).$this->srv_fillNumbers->fillNumber($tableau_message['numero_message'],2).$this->srv_fillNumbers->fillNumber($mode_ipc->getId(),2);
		if (! array_key_exists($triplet_module, $liste_modules)) {
			// Recherche du Module et Création de celui-ci si il n'existe pas en base de donnée
			// Récupération de l'entité du genre par son id
			$newGenre = $this->em->getRepository('IpcProgBundle:Genre')->find($genre_id);
			// Récupération de l'entité du module par son triplet + son id de mode 
			$module = $this->em->getRepository('IpcProgBundle:Module')->myFindModuleByUnicite($tableau_message['type_categorie'], $tableau_message['numero_message'], $tableau_message['numero_module'], $mode_ipc->getId());
			if ($module == null) {
				// Si le module n'existe pas : Création
				$newModule = new Module();
				$newModule->setCategorie($tableau_message['type_categorie']);
				$newModule->setNumeroMessage($tableau_message['numero_message']);
				$newModule->setNumeroModule($tableau_message['numero_module']);
				$newModule->setIntituleModule($tableau_message['intitule_module']);
				$newModule->setMessage($tableau_message['intitule_message']);
				$newModule->setUnite($tableau_message['type_unite']);
				$newModule->setMode($mode_ipc);
				$newModule->setGenre($newGenre);
				$newModule->setFichieripc($fichieripc);
				// Prise en charge du nouveau module par Doctrine
				$this->em->persist($newModule);
				// OLD OLD Ajout du module à la localisation
				// Pour chaque localisation, on ajoute le lien avec le module
				$localisation_ipc->addModule($newModule);
				$nbNewModules ++;
			} else {
				// Si le module existe : Modification
				$module->setCategorie($tableau_message['type_categorie']);
				$module->setNumeroMessage($tableau_message['numero_message']);
				$module->setNumeroModule($tableau_message['numero_module']);
				$module->setIntituleModule($tableau_message['intitule_module']);
				$module->setMessage($tableau_message['intitule_message']);
				$module->setUnite($tableau_message['type_unite']);
				$module->setMode($mode_ipc);
				$module->setGenre($newGenre);
				$module->setFichieripc($fichieripc);
				// OLD OLD Ajout au lien entre la localisation et le module si il ne sont pas déjà associés
				$localisation_ipc->addModule($module);
				$nbUpdateModules ++;
			}
			// Ajout du nouveau triplet à la liste des modules de la base
			$liste_modules[$triplet_module] = 1;
		}
	}
	// Insertion en Base
	$this->em->flush();
	$this->srv_log->setLog("Fin d'importation : $nbNewModules nouveaux modules, $nbUpdateModules modules mis à jour", $this->fichierLog);
	$this->srv_log->setLog("Nouvelle Table d'Echange [$nomfichier] importée", $this->fichierLog);

	// Envoi d'un mail avec la nouvelle table importée
    // Récupération de l'intitulé du site pour l'envoi du mail
    $intitule_site = $site_ipc->getIntitule();
	$configuration = new Configuration();
	$email_admin = $configuration->SqlGetParam($dbh, 'admin_email');
	$sujet = $affaire_site." : Importation d'une Table d'échange";
	$fichier = new Fichier();
	$chemin_du_fichier = $fichier->getTableIpcDir().$nomfichier;
	$this->sendEmailTableEchange($affaire_site, $intitule_site, $email_admin, $sujet, $chemin_du_fichier, false);
	return($fichieripc);
}

// Fonction qui exporte les données de la table des modules, crée le fichier csv et propose son téléchargement
//	En argument l'id de la localisation dont la table d'échange est demandée ou All si toute la table est demandée
public function exportationTableEchange($idLocalisation, $numeroLocalisation) {
	$dbh = $this->dbh;
	$objModule = new Module();
	$genre = new Genre();
	$fichier = new Fichier();
	$configuration = new Configuration();
	$site = new Site();
	$affaire_site = $site->SqlGetCourant($this->dbh, 'affaire');
	$intitule_site = $site->SqlGetCourant($this->dbh, 'intitule');	
	$email_admin = $configuration->SqlGetParam($dbh, 'admin_email');
	$sujet = $affaire_site." : Exportation d'une Table d'échange";
	$tabDesGenre = array();
	//Récupération de la liste des genres pour effectuer la conversion modules.idGenre en numeroGenre + intituleGenre
	$liste_genres_en_base = $genre->SqlGetAllGenre($dbh);
	//Récupération de la liste des modules de la base
	$tmp_liste_modules = $objModule->SqlGetModulesGenreAndUnit($dbh);
	//Récupération de la liste des liens modules / localisations si une localisation est demandée
	$tab_objLiens = array();
	$tab_liens = array();
	// Si une localisation est demandée : 	On recherche les liens modules/localisation en base
	// Le nom de la table d'échange indique la localisation
	// Recherche du programme associé à la localisation
	$mode = array();
	$mode_designation = null;
	if ($idLocalisation != 'allLoc') {
		$entity_localisation = $this->em->getRepository('IpcProgBundle:localisation')->find($idLocalisation);
        $affaire_site = $entity_localisation->getSite()->getAffaire();
        $intitule_site = $entity_localisation->getSite()->getIntitule();
		// Exportation de la dernière table d'échange importée
		// Exportation de la table d'échange en cours d'analyse.
		$infoLocalisation = $this->em->getRepository('IpcProgBundle:infosLocalisation')->findBy(array('localisation' => $idLocalisation, 'periodeCourante' => 1), array('horodatageDeb' => 'DESC'), 1, 0);
		if ($infoLocalisation != null) {
			$mode = $infoLocalisation[0]->getMode();
			if (! empty($mode)) {
				$mode_designation = $mode->getDesignation();
			}
		}
	}
	if ($idLocalisation != 'allLoc') {
		$tab_objLiens = $objModule->sqlGetLiens($dbh, $idLocalisation);
		if ($mode_designation != null) {
			$tableEchangeFic = $fichier->getTableIpcDir().'tei_'.$affaire_site.'_'.$this->srv_fillNumbers->fillNumber($numeroLocalisation,2).'_#'.$mode_designation.'#_'.date('YmdHis').'.csv';
		} else {
			$tableEchangeFic = $fichier->getTableIpcDir().'tei_'.$affaire_site.'_'.$this->srv_fillNumbers->fillNumber($numeroLocalisation,2).'_'.date('YmdHis').'.csv';
		}
	} else {
		$tableEchangeFic = $fichier->getTableIpcDir().'tei_'.$affaire_site.'_'.date('YmdHis').'.csv';
	}
	foreach ($tab_objLiens as $keyLien => $lien) {
		$tab_liens[] = $lien['module_id'];
	}
	// Création et Ouverture du nouveau fichier de la table d'échange
	$fp = fopen($tableEchangeFic, "w");
	foreach ($liste_genres_en_base as $key => $genre) {
		$tabDesGenre[$genre['id']]['numero'] = $genre['numero_genre'];
		$tabDesGenre[$genre['id']]['intitule'] = $genre['intitule_genre'];
	}
	// Ecriture dans un fichier de la liste des modules de la localisation demandée (passée en paramètre)
	foreach ($tmp_liste_modules as $module) {
		if (($idLocalisation != 'allLoc') && (in_array($module['id'], $tab_liens))) {
			$message = null;
			$message = $tabDesGenre[$module['idGenre']]['numero'].';';
			$message .= $tabDesGenre[$module['idGenre']]['intitule'].';';
			$message .= $module['categorie'].';';
			$message .= $module['numeroModule'].';';
			$message .= $module['intituleModule'].';';
			$message .= $module['numeroMessage'].';';
			$message .= $module['message'].';';
			$message .= $module['unite']."\n";
			$message = utf8_decode($message);
			fwrite($fp, $message);
		} elseif ($idLocalisation  == 'allLoc') {
			$message = null;
			$message = $tabDesGenre[$module['idGenre']]['numero'].';';
			$message .= $tabDesGenre[$module['idGenre']]['intitule'].';';
			$message .= $module['categorie'].';';
			$message .= $module['numeroModule'].';';
			$message .= $module['intituleModule'].';';
			$message .= $module['numeroMessage'].';';
			$message .= $module['message'].';';
			$message .= $module['unite']."\n";
			$message = utf8_decode($message);
			fwrite($fp, $message);
		}
	}
	fclose($fp);
	// Envoye du fichier par mail aux admins
	/*$liste_messages[] = "Table d'échange du site : ".$affaire_site.' - '.$intitule_site;
	$this->email->sendTableEchange($email_admin, $sujet, $tableEchangeFic, $liste_messages);
	//	Téléchargement du fichier
	$fichierName = basename($tableEchangeFic);
	$response = new Response();
	$response->headers->set('Content-Type', 'application/force-download');
	$response->headers->set('Content-Disposition', 'attachment;filename="'.$fichierName.'"');
	$response->headers->set('Content-Length', filesize($tableEchangeFic));
	$response->setContent(file_get_contents($tableEchangeFic));
	$response->setCharset('UTF-8');
	return $response;
	*/
	return $this->sendEmailTableEchange($affaire_site, $intitule_site, $email_admin, $sujet, $tableEchangeFic, true);
}

public function changeProgramme(Localisation $localisation, Mode $mode = null) {
	$localisation->resetModules();
	// Récupération des modules du programme
	$liste_module = $this->em->getRepository('IpcProgBundle:Module')->findBy(array('mode' => $mode));
	foreach ($liste_module as $module) {
		$localisation->addModule($module);
	}
	$localisation->setMode($mode);
	$this->em->flush();
}


// Fonction qui envoi le mail avec la table d'échange en pièce jointe
public function sendEmailTableEchange($affaire_site, $intitule_site, $adresse_mail, $sujet, $chemin_du_fichier, $bool_export) {
	$user = $this->srv_security_context->getToken()->getUser();
	if ($bool_export == true) {
		// Demande d'exportation 
		$tab_contenus[] = "Exportation d'une table d'échange";
		$tab_contenus[] = $user." a demandé l'exportation d'une table d'échange du site ".$affaire_site.' ('.$intitule_site.')';
		$tab_contenus[] = "Cf la pièce jointe pour obtenir la table exportée.";
	} else {
		// Importation d'une nouvelle table
		$tab_contenus[] = "Importation d'une nouvelle table d'échange";
		$tab_contenus[] = $user." a effectué l'importation d'une nouvelle table d'échange sur le site ".$affaire_site.' ('.$intitule_site.')';
		$tab_contenus[] = "Cf la pièce jointe pour obtenir la nouvelle table importée.";
	}
	$this->srv_email->sendTableEchange($adresse_mail, $sujet, $chemin_du_fichier, $tab_contenus);
	$nom_du_fichier = basename($chemin_du_fichier);	
	$response = new Response();
	// Si une demande d'exportation est faite, la réponse retourne le fichier en pièce jointe
	if ($bool_export == true) {
		$response->headers->set('Content-Type', 'application/force-download');
		$response->headers->set('Content-Disposition', 'attachment;filename="'.$nom_du_fichier.'"');
		$response->headers->set('Content-Length', filesize($chemin_du_fichier));
		$response->setContent(file_get_contents($chemin_du_fichier));
    	$response->setCharset('UTF-8');
	}
    return $response;
}

}
