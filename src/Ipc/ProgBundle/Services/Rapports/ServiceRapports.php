<?php
//src/Ipc/ProgBundle/Services/Rapports/ServiceRapports

namespace Ipc\ProgBundle\Services\Rapports;

use Symfony\Component\HttpFoundation\Response;
use Ipc\ProgBundle\Entity\Configuration;
use Ipc\ProgBundle\Entity\Localisation;
use Ipc\ProgBundle\Entity\Donnee;
use Ipc\ProgBundle\Entity\Donneetmp;
use Ipc\ProgBundle\Entity\Module;
use Ipc\ProgBundle\Entity\Genre;
use Ipc\ProgBundle\Entity\Etat;
use Ipc\ProgBundle\Entity\Site;
use Ipc\ProgBundle\Entity\Fichier;
use Ipc\ProgBundle\Entity\DonneeDoublon;


class ServiceRapports {
protected $dbh;
protected $log;
protected $email;
protected $date_jour;
protected $date_rapport;
protected $fichier_log_ipc;
protected $fichier_log_transfert;
protected $fichier_log_security;
protected $commande_cat;
protected $configuration;
protected $site;
protected $localisations;
protected $em; 
protected $fileParamSystem;
protected $seuil_fs_alerte;
protected $commande_analyse_fs;
protected $nbErreursAnalyse;
protected $nbErreursFtp;
protected $sendMail;
private $pourcentage_max;
private $nombre_messages_max;
private $nombre_max_messages;
protected $service_fillNumbers;

public function __construct($srv_connexion, $log, $email, $service_fillNumbers) {
	$this->dbh = $srv_connexion->getDbh();
	$this->em = $srv_connexion->getManager();

	$this->log = $log;
	$this->email = $email;
	$this->date_rapport = date('Y/m/d', strtotime("1 day ago" ));
	$this->date_jour = date('Y/m/d');
	$this->service_fillNumbers = $service_fillNumbers;
	$this->dossier_fichiers_err = __DIR__.'/../../../../../web/uploads/fichiers_errors/';
	$this->configuration = new Configuration();
	$site = new Site();
	$site_id = $site->SqlGetIdCourant($this->dbh);
	$this->site = $this->em->getRepository('IpcProgBundle:Site')->find($site_id);
	$this->localisations = $this->site->getLocalisations();
	$this->email_admin = $this->configuration->SqlGetParam($this->dbh, 'admin_email');
	$this->email_adminIpcWeb = $this->configuration->SqlGetParam($this->dbh, 'admin_ipcWeb');
   	$this->pourcentage_max = $this->configuration->SqlGetParam($this->dbh, 'rapport_pourcentage_messages_max');
    $this->nombre_messages_max = $this->configuration->SqlGetParam($this->dbh, 'rapport_nombre_messages_max');
	$this->nombre_max_messages = $this->configuration->SqlGetParam($this->dbh, 'rapport_nombre_max_messages');
	date_default_timezone_set($this->configuration->SqlGetParam($this->dbh, 'timezone'));
	$this->fileParamSystem = __DIR__.'/../../../../../web/logs/parametresSystem.log';
	$this->seuil_fs_alerte = $this->configuration->SqlGetParam($this->dbh, 'seuil_alerte_filesystem');
	$this->commande_analyse_fs = __DIR__.'/../../../../../web/sh/GestionSystem/administation.sh';
	$this->sendMail = false;
}


//	Rapport Journalier indiquant le nombre de fichiers importés, le nombre et le type des erreurs rencontrées, et par module le nombre de fichiers transférés et le nombre de connexions
// Mise à jour du 20/04/2017 : Envoi du rapport ssi le nombre d'erreur est > à 2 ou si la demande provient du module outil. + Analyse des données en doublons
public function rapportJournalier($post_date_rapport = null) {
	$this->setFichiersLog($post_date_rapport);
	// ajout du 20/04/2017 : Variable indiquant qu'une erreur Ftp est rencontrée. 
	//	-> Nombre de connexion FTP != Nombre de tentatives de connexion FTP
	//	-> Nombre de fichiers transférés != Nombre de fichier à transférer
	//	-> Nombre de fichiers vides != 0
	$this->nbErreursFtp = 0; 
	// Permet d'indiquer si les rapports d'erreur ftp sont envoyés ou pas
	$autorisation_envoi_rapport_erreur = $this->configuration->SqlGetParam($this->dbh, 'autorisation_rapports_erreur');
	$flagRapportJournalier = '/tmp/.flagSymfonyRapportJournalier';
	if (file_exists($flagRapportJournalier)) {
		return('Le rapport Journalier est déjà en cours de création');
	} else {
		// Récupération du nombre d'erreurs en base de données
		$donnees_erreur = new Donneetmp();
		$nb_erreurs_en_base = $donnees_erreur->SqlGetNb($this->dbh);
		//	Maj du 20/04/2017 : Déplacement des données en doublons
		$this->moveDoublons();
		$nb_erreurs_en_base_apres_deplacement_DD = $donnees_erreur->SqlGetNb($this->dbh);
		$nb_erreurs_DD = $nb_erreurs_en_base - $nb_erreurs_en_base_apres_deplacement_DD;

		$commande = "touch $flagRapportJournalier";
		exec($commande);
		// Nombre de fichiers vides dans le dossiers des fichiers traités
		$commande = $this->commande_cat.$this->fichier_log_ipc." | grep '".$this->getFrDate($this->date_rapport)."' |".' grep "'."Fin d'importation du fichier ( 0 ligne(s) vide(s) - 0 message(s) ) avec 0 erreur(s), 0 ligne(s) insérée(s) et 0 doublon(s)".'" | grep -v "REIMPORT" | wc -l';
		$nb_fichiers_vides 	= exec($commande);
		// Récupération du nombre de fichiers importés
		$commande = $this->commande_cat.$this->fichier_log_ipc." | grep '".$this->getFrDate($this->date_rapport)."' |".' grep "'."Fin d'importation du fichier".'" | grep -v "REIMPORT" | wc -l';
		$nb_fichiers_importes 	= exec($commande);
		// Récupération du nombre de fichiers réimportés
		$commande = $this->commande_cat.$this->fichier_log_ipc." | grep '".$this->getFrDate($this->date_rapport)."' |".' grep "'."Fin d'importation du fichier".'" | grep "REIMPORT" | wc -l';
		$nb_fichiers_reimportes = exec($commande);
		// Récupération de la liste des fichiers importés
		$commande = $this->commande_cat.$this->fichier_log_ipc." | grep '".$this->getFrDate($this->date_rapport)."' |".' grep "'."Fin d'importation du fichier".'" | grep -v "REIMPORT" ';
		$last_fichiers_importes = exec($commande, $tabtmp_fichiers_importes);
		// Récupération du nombre de fichiers en erreur
		$commande = "find ".$this->dossier_fichiers_err." -maxdepth 1 -type f | wc -l | awk '{print $1}'";
		$nb_fichiers_errones = exec($commande);
		//	Pour chaque ligne indiquant qu'un fichier est importé : Récupération du nom du fichier et mise de celui ci dans le tableau des fichiers importés
		$tabFichiersImportes = array();
		foreach ($tabtmp_fichiers_importes as $fichier_importe) {
			$tabChamps = explode(';', $fichier_importe);	
			// La date du fichier se trouve sur le champ 2
			// Suppression du nom du programme si il existe
			$pattern_date = '/^(.+?)_#(.+?)#_(.+?)$/';
			if (preg_match($pattern_date, $tabChamps[2], $tabPatternDate)) {
				$tabChamps[2] = $tabPatternDate[1].'_'.$tabPatternDate[3];
			}
			$tabChampsDate = explode('_', $tabChamps[2]);
			// Date récupérée au format : yymmdd à transformer en ddmmyy
			$pattern = '/^(..)(..)(..)$/';
			if (preg_match($pattern, $tabChampsDate[2], $tabInfosDate)) {
				$dateDuFichier = $tabInfosDate[3].$tabInfosDate[2].$tabInfosDate[1];
			}
			$tabFichiersImportes[]=$dateDuFichier;
		}
		$tabFichiersImportes = array_unique($tabFichiersImportes);
		// Récupération du nombre d'erreurs d'importation
		$commande = $this->commande_cat.$this->fichier_log_ipc." | grep '".$this->getFrDate($this->date_rapport)."' | grep 'ERROR' | wc -l";
		$nb_erreurs_importation = exec($commande);
		// Nombre total de fichiers à transférer
		$nb_total_fichiers_transferes = 0;
		// Liste des erreurs rencontrées et nombre d'occurences
		$tab_erreurs_importation = array();
		$tab_erreurs = array();
		$commande = $this->commande_cat.$this->fichier_log_ipc." | grep '".$this->getFrDate($this->date_rapport)."' | grep 'ERROR' | awk -F';' '{print $5}' | sort | uniq";
		$last_erreur_import	= exec($commande, $tab_erreurs);
		foreach ($tab_erreurs as $erreur) {
			$tab_car_a_remplacer = array('[',']');
			$tab_car_de_remplacement = array('\[','\]');
			$erreur_replace = str_replace($tab_car_a_remplacer, $tab_car_de_remplacement, $erreur);
			// Nombre d'occurence de l'erreur
			$commande_occurence = $this->commande_cat.$this->fichier_log_ipc." | grep '".$this->getFrDate($this->date_rapport)."' | grep 'ERROR' | awk -F';' '{print $5}' | grep '$erreur_replace' | wc -l";
			$nb_occurences = exec($commande_occurence);
			$tab_erreurs_importation[$erreur] = $nb_occurences;
		}

		// Si une erreur de type DGMNF est rencontrée c'est que des modules ne sont pas présents ou ont un mauvais genre en base 
		// Recherche de la liste des erreurs de type DGMNF
		$nb_modules_absents = 0;
		$tab_modules_absents = array();
		if (array_key_exists('DGMNF', $tab_erreurs_importation)) {
			// Liste des modules non présents
			$commande = $this->commande_cat.$this->fichier_log_ipc." | grep '".$this->getFrDate($this->date_rapport)."' | grep 'DGMNF' | awk -F';' '{print $9}' | sort | uniq";
			$last_module_absent = exec($commande, $tab_modules_absents);
		}
		$liste_modules_absents = "";
		foreach ($tab_modules_absents as $module_absent) {
			$liste_modules_absents .= $module_absent." ";
		}
		// Si une erreur de type LINK est rencontrée : Création du tableau des erreurs de liens en supprimant les éventuels doublons
		$tab_link_absents = array();
		if (array_key_exists('LINK', $tab_erreurs_importation)) {
			// Liste des modules non présents
			$commande = $this->commande_cat.$this->fichier_log_ipc." | grep '".$this->getFrDate($this->date_rapport)."' | grep 'LINK' | awk -F';' '{print $6}' | sort | uniq";
			$last_link_absent = exec($commande, $tab_link_absents);
		}
		// Récupération des informations des transferts Ftp
		$tab_localisations = array();
		foreach ($this->localisations as $localisation) {
			$adresseIp = $localisation->getAdresseIp();
			$numeroLocalisation = $localisation->getNumeroLocalisation();
			// Récupération du programme associé à la localisation
			if ($localisation->getMode() != null) {
				$tab_localisations[$numeroLocalisation]['nomProgramme']	= $localisation->getMode()->getDesignation();
			} else {
				$tab_localisations[$numeroLocalisation]['nomProgramme'] = "Pas de programme";
			}
			// Récupération du nombre de fichiers à transférer
			$tab_nb_fichiers_a_transferer = array();
			$nb_fichiers_a_transferer = 0;
			$commande = $this->commande_cat.$this->fichier_log_transfert." | grep '".$this->getFrDate($this->date_rapport)."' | grep '$adresseIp' | grep 'Nombre de fichiers à transférer' | awk -F';' '{print $6}'";
			$last_nb_fichiers_a_transferer = exec($commande, $tab_nb_fichiers_a_transferer);
			foreach ($tab_nb_fichiers_a_transferer as $nb_fichiers) {
				$nb_fichiers_a_transferer += $nb_fichiers;	
			}
			$tab_localisations[$numeroLocalisation]['nb_fic_to_transfert'] = $nb_fichiers_a_transferer;
			// Enregistrement du nombre total de fichiers à transférer
			$nb_total_fichiers_transferes += $nb_fichiers_a_transferer;
			// Récupération du nombre de fichiers vides par localisation
			$commande = $this->commande_cat.$this->fichier_log_transfert." | grep '".$this->getFrDate($this->date_rapport)."' | grep '$adresseIp' |  grep 'Fichier vide détecté' | awk -F';' '{print \$NF}' | sort | uniq | wc -l";
			$nb_loc_fichiers_vides = exec($commande);
			$tab_localisations[$numeroLocalisation]['nb_fic_vides'] = $nb_loc_fichiers_vides;
			// Récupération du nombre de fichiers transférés
			$tab_nb_fichiers_transferes = array();
			$nb_fichiers_transferes	= 0;
			$commande = $this->commande_cat.$this->fichier_log_transfert." | grep '".$this->getFrDate($this->date_rapport)."' | grep '$adresseIp' | grep 'Nombre de fichiers transférés' | awk -F';' '{print $6}'";
			$last_nb_fichiers_transferes = exec($commande, $tab_nb_fichiers_transferes);
			foreach ($tab_nb_fichiers_transferes as $nb_fichiers) {
				$nb_fichiers_transferes += $nb_fichiers;
			}
			$tab_localisations[$numeroLocalisation]['nb_fic_transferes'] = $nb_fichiers_transferes;
			// Nombre d'erreurs
			$commande = $this->commande_cat.$this->fichier_log_transfert." | grep '".$this->getFrDate($this->date_rapport)."' | grep '$adresseIp' | grep 'ERROR' | wc -l";
			$tab_localisations[$numeroLocalisation]['nb_erreurs'] = exec($commande);
			// Type d'erreur et nombre d'occurences
			$tab_liste_erreurs = array();
			$commande = $this->commande_cat.$this->fichier_log_transfert." | grep '".$this->getFrDate($this->date_rapport)."' | grep '$adresseIp' | grep 'ERROR' | awk -F';' '{print $4}' | sort | uniq";
			$last_erreur = exec($commande, $tab_liste_erreurs);
			$tab_localisations[$numeroLocalisation]['Erreurs'] = array();
			foreach ($tab_liste_erreurs as $erreur) {
				$commande = $this->commande_cat.$this->fichier_log_transfert." | grep '".$this->getFrDate($this->date_rapport)."' | grep '$adresseIp' | grep 'ERROR' | awk -F';' '{print $4}' | grep \"$erreur\" | wc -l";
				$nb_occurences = exec($commande);
				$tab_localisations[$numeroLocalisation]['Erreurs'][$erreur]=$nb_occurences;
			}	
			// Nombre de tentatives de connexions ftp
			$commande = $this->commande_cat.$this->fichier_log_transfert." | grep '".$this->getFrDate($this->date_rapport)."' | grep '$adresseIp' | grep 'Tentative de Connexion' | wc -l";
			$tab_localisations[$numeroLocalisation]['tentativesConnexions'] = exec($commande);
			$commande = $this->commande_cat.$this->fichier_log_transfert." | grep '".$this->getFrDate($this->date_rapport)."' | grep '$adresseIp' | grep 'Connexion établie' | wc -l";
			$tab_localisations[$numeroLocalisation]['connexions'] = exec($commande);
		}
		// Récupération du site courant 
		$numParagraphe = 1;
		// Titre du mail
		$titre = "Rapport Journalier du ".$this->getFrDate($this->date_rapport)." pour le site ".$this->site->getAffaire().' - '.$this->site->getIntitule();
		$nomMailSave = "Rapport_Journalier_du_".$this->getMailDate($this->date_rapport)."_pour_le_site_".$this->site->getAffaire().'_'.$this->site->getIntitule().'.html';
		// Contenu du mail
		$liste_messages[] = $this->getFrDate($this->date_rapport)." : Rapport Journalier du site ".$this->site->getAffaire().' - '.$this->site->getIntitule();
		$liste_messages[] = "Liste des évènements d'importation en base";
		$liste_messages[] = "$numParagraphe) Détails";
		$numParagraphe = $numParagraphe + 1;
		if ($autorisation_envoi_rapport_erreur == 0) {
			$liste_messages[] = '<span style="color:red;">ATTENTION : Rapports des erreurs de transferts Ftp désactivés !</span>';
		}
		if ($nb_fichiers_errones > 0) {
			$liste_messages[] = '<span style="color:red;">Nombre de fichiers corrompus : '.$nb_fichiers_errones.'</span><br/>';
		} else {
			$liste_messages[] = 'Aucun fichier corrompu<br/>';
		}
		if ($nb_fichiers_vides > 0) {
			$liste_messages[] = '<span style="color:red;">Nombre de fichiers vides : '.$nb_fichiers_vides.'</span><br/>';
		} else {
			$liste_messages[] = 'Aucun fichier vide<br/>';
		}
		if ($nb_erreurs_en_base > 0) {
			$messageTmp = '<span style="color:red;">Nombre de données en erreur : '.$nb_erreurs_en_base;
			if ($nb_erreurs_DD != 0){
				$messageTmp .= ' ('.$nb_erreurs_DD.' données en doublons)';
			}
			$messageTmp .= '</span><br/>';
			$liste_messages[] = $messageTmp;
		} else {
			$liste_messages[] = 'Aucune donnée en erreur<br/>';
		}
		if (($nb_fichiers_importes < $nb_total_fichiers_transferes) || ($nb_fichiers_importes == 0)) {
			$liste_messages[] = '<span style="color:red;">Nombre de fichiers importés : '.$nb_fichiers_importes.'/'.$nb_total_fichiers_transferes.'</span><br/>';
		} else {
			$liste_messages[] = "Nombre de fichiers importés : ".$nb_fichiers_importes.'/'.$nb_total_fichiers_transferes.'<br/>';
		}
		if ($nb_fichiers_reimportes != 0) {
			$liste_messages[] = '<span style="color:red;">Nombre de fichiers réimportés : '.$nb_fichiers_reimportes.'</span><br/>';
		}
		// Ecriture des dates des fichiers importés
		$liste_messages[] = "$numParagraphe) Dates des fichiers importés";
		$numParagraphe = $numParagraphe + 1;
		foreach ($tabFichiersImportes as $fichierImporte) {
			// On ajoute un espace pour que la date ne soit pas prise pour un titre
			$liste_messages[] = ' '.$fichierImporte."<br/>";	
		}
		if ($nb_erreurs_importation > 0) {
			$liste_messages[] = "Nombre d'erreurs d'importation : ".$nb_erreurs_importation;
			$liste_messages[] = "$numParagraphe) Liste des erreurs";
			$numParagraphe = $numParagraphe + 1;
			foreach ($tab_erreurs_importation as $key => $nb_erreurs) {
				if ($nb_erreurs > 1) {
					$liste_messages[] = "$key => $nb_erreurs occurences<br/>";
				} else {
					$liste_messages[] = "$key => $nb_erreurs occurence<br/>";
				}
			}
		}
		$liste_messages[] = "$numParagraphe) Liste des modules non présents en base";
		$numParagraphe = $numParagraphe + 1;
		if (array_key_exists('DGMNF', $tab_erreurs_importation)) {
			$liste_messages[] = $liste_modules_absents.'<br/>';
		} else {
			$liste_messages[] = '-<br/>';
		}
		$liste_messages[] = '</table>';
		$liste_messages[] = "$numParagraphe) Liste des Liens manquants";
		$numParagraphe = $numParagraphe + 1;
		if (array_key_exists('LINK', $tab_erreurs_importation)) {
			foreach ($tab_link_absents as $link_absent) {
				$liste_messages[] = "$link_absent<br/>";
			}
		} else {
			$liste_messages[] = "-<br/>";
		}
		// Contenu concernant les équipements
		$liste_messages[] = "Liste des évènements de transfert Ftp";
		foreach ($this->localisations as $localisation) {
			$numParagrapheFtp = 1; 
			$numero = $localisation->getNumeroLocalisation();
			$designation = $localisation->getDesignation();
			$adresseIp = $localisation->getAdresseIp();
			$liste_messages[] = "Equipement : $designation ( $adresseIp )";
			$liste_messages[] = "$numParagrapheFtp) Système";
			$numParagrapheFtp = $numParagrapheFtp + 1;
			$liste_messages[] = "Programme installé : ".$tab_localisations[$numeroLocalisation]['nomProgramme'];
			$liste_messages[] = "$numParagrapheFtp) Transferts";
			$numParagrapheFtp = $numParagrapheFtp + 1;
			$nb_loc_fichiers_vides = $tab_localisations[$numero]['nb_fic_vides'];
			if ($nb_loc_fichiers_vides > 0) {
				$liste_messages[] = '<span style="color:red;">Nombre de fichiers vides : '.$nb_loc_fichiers_vides.'</span><br/>';
			} else {
				$liste_messages[] = 'Aucun fichier vide<br/>';
			}
			$liste_messages[] = "Nombre de fichiers à transférer : ".$tab_localisations[$numero]['nb_fic_to_transfert'].'<br/>';
			$liste_messages[] = "Nombre de fichiers transférés : ".$tab_localisations[$numero]['nb_fic_transferes'].'<br/>';
			$liste_messages[] = "Nombre de connexions : ".$tab_localisations[$numero]['connexions'].'/'.$tab_localisations[$numero]['tentativesConnexions'].'<br/>';
			if ($nb_loc_fichiers_vides != 0) {
				$this->nbErreursFtp = 1;
			}
			if ($tab_localisations[$numero]['connexions'] != $tab_localisations[$numero]['tentativesConnexions']) {
				$this->nbErreursFtp = 1;
			}
			if ($tab_localisations[$numero]['nb_fic_to_transfert'] != $tab_localisations[$numero]['nb_fic_transferes']){
				$this->nbErreursFtp = 1;
			}
			if (count($tab_localisations[$numero]['Erreurs']) > 0) {
				$liste_messages[] = "$numParagrapheFtp) Liste des erreurs";
				$numParagrapheFtp = $numParagrapheFtp + 1;
				foreach ($tab_localisations[$numero]['Erreurs'] as $erreur => $nb_erreurs) {
					if ($nb_erreurs > 1) {
						$liste_messages[] = '<span style="color:red;">'."$erreur => $nb_erreurs occurences</span><br/>";	
					} else {
						$liste_messages[] = '<span style="color:red;">'."$erreur => $nb_erreurs occurence</span><br/>";
					}
				}
			}
		}

        // Ajout des éléments du rapport d'analyse au rapport journalier
        $liste_messages[] = "Liste des évènements d'analyse";
        $liste_messages_analyse = $this->rapportSyntheseModule(false, $post_date_rapport);
        $liste_messages_mail = array_merge($liste_messages, $liste_messages_analyse);
		
        // Ajout du 25/04/2017: Sauvegarde du mail dans un dossier dédié en fonction de la valeur de la variable sauvegarde_rapports_journaliers
        $sauvegarde_rapport = $this->configuration->SqlGetParam($this->dbh, 'sauvegarde_rapports_journaliers');
        if ($sauvegarde_rapport == 1){
            $this->email->saveMail($this->email_admin, $nomMailSave, $liste_messages_mail);
        }

        // Modification du 25/04/2017-> la variable rapport_journalier est remplacée par autorisation_mails - Ajout du 30/09/2015 Ipc Version 1.11.5 : Les rapports sont envoyés si le paramètre de configuration [autorisation_mails] = 1
        $autorisation_envoi_rapport = $this->configuration->SqlGetParam($this->dbh, 'autorisation_mails');
        if ($autorisation_envoi_rapport == 0) {
			$commande = "rm $flagRapportJournalier";
			exec($commande);
            return("Envoi du rapport journalier refusé: Veuillez revoir le paramètre 'autorisation_mails'");
        }

        // Ajout du 20/04/2017 : Envoi du rapport si la variable sendMail est définie à true ou si une erreur est déclarée ou si la variable de configuration envoi_rapports_journaliers = true
        //      1       $nb_total_fichiers_transferes > $nb_fichiers_importes + 1
        //      1.1     $nb_total_fichiers_transferes = 0 OU $nb_fichiers_importes = 0
        //      2       Nombre d'erreur != 0
        //      3       Nombre de fichiers corrompus != 0
        //      4       Nombre de connexion FTP != Nombre de tentative de connexion FTP
        //      5       Pourcentage de présence d'un message > 90%
        if (($nb_total_fichiers_transferes > ($nb_fichiers_importes + 1)) || ($nb_total_fichiers_transferes == 0) || ($nb_fichiers_importes == 0) || ($nb_fichiers_errones != 0) || ($nb_erreurs_en_base != 0) || ($this->nbErreursFtp != 0) || ($this->nbErreursAnalyse != 0)) {
                $this->email->send($this->email_admin, $titre, 'Mail', $liste_messages_mail);
                $message_retour = "Rapport Journalier envoyé à l'adresse ".$this->email_admin;
        } else {
            if ($this->sendMail == true) {
                $this->email->send($this->email_admin, $titre, 'Mail', $liste_messages_mail);
                $message_retour = "Rapport Journalier envoyé à l'adresse ".$this->email_admin;
            } else {
				// Ajout du 25/04/2017: Si il n'y a pas d'erreur et que la demande d'envoi du rapport ne provient pas du module OUTIL, le rapport est envoyé si la variable de conf: envoi_rapports_journaliers = true (false par défaut)
            	$autorisation_envoi_rapport = $this->configuration->SqlGetParam($this->dbh, 'envoi_rapports_journaliers');
				if ($autorisation_envoi_rapport == 1) {
					$this->email->send($this->email_admin, $titre, 'Mail', $liste_messages_mail);
					$message_retour = "Rapport Journalier envoyé à l'adresse ".$this->email_admin;
				} else {
                	$message_retour = "Rapport non envoyé. Aucune erreur détectée et la variable de configuration envoi_rapports_journaliers est définie à false.";
				}
            }
        }
        // Libération du flag
        $commande = "rm $flagRapportJournalier";
        exec($commande);
        return($message_retour);
	}
}



public function rapportSyntheseModule($toSend, $post_date_rapport = null) {
	$this->setFichiersLog($post_date_rapport);
	$this->nbErreursAnalyse = 0;
	$flagRapportAnalyse = '/tmp/.flagSymfonyRapportAnalyse';
	if (file_exists($flagRapportAnalyse)) {
		return('Le rapport d\'Analyse est déjà en cours de création');
	} else {
		$commande = "touch $flagRapportAnalyse";
		exec($commande);
		// Récupération de la date du jour précédent + création des intervalle de la période de recherche
		$date_jour_mail = date('d/m/Y', strtotime("1 day ago" ));
		$horodatage_deb = $this->date_rapport.' 00:00:00';
		$horodatage_fin = $this->date_rapport.' 23:59:59';
		// Récupération d'un tableau des différents genres : 	idGenre[intitule] = Intitulé du Genre
		// idGenre[numero]	= Numéro du Genre
		$genre = new genre();
		$tab_genres = $genre->SqlGetAllGenre($this->dbh);
		$tab_des_genres = array();
		foreach ($tab_genres as $key=>$genre) {
			$tab_des_genres[$genre['id']]['intitule'] = $genre['intitule_genre'];
			$tab_des_genres[$genre['id']]['numero'] = $genre['numero_genre'];
		}
		// Récupération de la liste des modules
		$module = new Module();
		$tabModules = $module->SqlGetModulesGenreAndUnit($this->dbh);
		// Création d'un tableau : 	idModule[Genre] = genre
		// idModule[Module] = catN°MoN°Me
		$tab_des_modules = array();
		foreach ($tabModules as $key=>$module) {
			$tab_des_modules[$module['id']]['genre'] = $module['idGenre'];
			$tab_des_modules[$module['id']]['module'] = $module['categorie'].$this->service_fillNumbers->fillNumber($module['numeroModule'], 2).$this->service_fillNumbers->fillNumber($module['numeroMessage'], 2);
		}
		$titre = "Rapport d'analyse du $date_jour_mail pour le site ".$this->site->getAffaire().' - '.$this->site->getIntitule();
		if ($toSend == true) {
			$liste_messages[] = "$date_jour_mail : Rapport d'analyse du site ".$this->site->getAffaire().' - '.$this->site->getIntitule();
		}
		foreach ($this->localisations as $localisation) {
			$localisation_id = $localisation->getId();
			$numero = $localisation->getNumeroLocalisation();
			$designation = $localisation->getDesignation();
			$adresseIp = $localisation->getAdresseIp();
			$nb_donnees = 0;
			$nb_donnees_jour = null;
			// Récupération des modules et du nombre de données de la journée précédente
			$donnee = new Donnee();
			// Création d'un tableau des modules de la journée précédente : idModule['module'] = CodeModule
			$tabJour_modules = array();
			// Création d'un tableau de genres de la journée précédente : 	idGenre['intitule'] = intitule
			// idGenre['nb'] = Nombre d'occurences	
			$tabJour_genres = array();
			$horodatage_deb = $this->date_rapport.' 00:00:00';
			$horodatage_fin = $this->date_rapport.' 23:59:59';
			// Récupération des données entre deb et fin provenant de la localisation donnée
			$tab_modules = $donnee->sqlGetNbModules($this->dbh, $horodatage_deb, $horodatage_fin, $localisation_id);
			// Enregistrement d'une table contenant la liste des ids des modules rencontrés pour la journée - Et le nombre d'occurence  : Pour chaque module de la table d'échange
			foreach ($tab_modules as $key=>$module) {
				// Nombre de données récupérées la journée précédente
				$nb_donnees += $module['nb'];
				// Si le numéro du module est rencontré une première fois création de la ligne correspondant à ce module
				if (! array_key_exists($module['id'], $tabJour_modules)) {
					$tabJour_modules[$module['id']]['module'] = $tab_des_modules[$module['id']]['module'];
					$tabJour_modules[$module['id']]['nb'] = $module['nb'];
				} else {
					$tabJour_modules[$module['id']]['nb'] = $tabJour_modules[$module['id']]['nb'] + $module['nb'];
				}
				// Si le numéro de genre est rencontré une première fois
				$genre_id = $tab_des_modules[$module['id']]['genre'];
				if (! array_key_exists($genre_id, $tabJour_genres)) {
					$tabJour_genres[$genre_id]['intitule'] = $tab_des_genres[$genre_id]['intitule'];
					$tabJour_genres[$genre_id]['nb'] = $module['nb'];
				} else {
					$tabJour_genres[$genre_id]['nb'] = $tabJour_genres[$genre_id]['nb'] + $module['nb']; //1;
				}
			}
			// Pour chaque localisation : comptage du nombre de données de la journée et inscription dans le rapport d'analyse
			if ($nb_donnees > 1) {
				$nb_donnees_jour = "$nb_donnees données";
			} else {
				$nb_donnees_jour = "$nb_donnees donnée";
			}
			$liste_messages[] = "Equipement : $designation ( $adresseIp ) : $nb_donnees_jour";
			$volume_genre = array();
			$edition_genre = array();
			$volume = array();
			$edition = array();
			foreach ($tabJour_modules as $key => $row) {
				$volume[$key] = $row['nb'];
				$edition[$key] = $row['module'];
			}
			array_multisort($volume, SORT_DESC, $edition, SORT_ASC, $tabJour_modules);
			foreach ($tabJour_genres as $key => $row) {
				$volume_genre[$key]  = $row['nb'];
				$edition_genre[$key] = $row['intitule'];
			}
			array_multisort($volume_genre, SORT_DESC, $edition_genre, SORT_ASC, $tabJour_genres);
			if (count($tabJour_modules) > 0) {
				$liste_messages[] = "1) Liste et occurences des messages"; 
				$liste_messages[] = "<table>";
				$liste_messages[] = "<tr><th>Intitulé du Module</th><th>Nb de messages</th><th>Pourcentage</th></tr>";
				foreach ($tabJour_modules as $key => $module) {
					$pourcentage = round($module['nb'] * 100 / $nb_donnees, 4);
					// Ajout du 20/04/2017 : Si le pourcentage d'un message dépasse les 90% (ajout du 11/05/2017 et que le nombre de message est > 1000) on affiche le rapport
					if ( ($pourcentage > $this->pourcentage_max) && ($module['nb'] > $this->nombre_messages_max) ) {
						$this->nbErreursAnalyse = 1;
					} else if ($module['nb'] > $this->nombre_max_messages) {
						// Ajout du 27/06/2018 : Si le nombre de messages dépasse la limite = Erreur déclarée dans le rapport
						$this->nbErreursAnalyse = 1;
					}
					// Si le pourcentage est > au pourcentage max et que le nombre de messages dépasse la limite OU Si le nombre de messages dépasse la limite 2 -> Affichage de l'information en rouge
					if ( (($pourcentage > $this->pourcentage_max) && ($module['nb'] > $this->nombre_messages_max)) || ($module['nb'] >  $this->nombre_max_messages) ) {
						$liste_messages[] = '<tr style="color:red;"><td>'.$module['module'].'</td/><td>'.$module['nb'].' messages </td><td>('.$pourcentage.' %)</td></tr>';
					} else {
						$liste_messages[] = "<tr><td>".$module['module'].'</td/><td>'.$module['nb'].' messages </td><td>('.$pourcentage.' %)</td></tr>';
					}
				}	
				$liste_messages[] = "</table>";
			}
			if (count($tabJour_genres) > 0) {
				$liste_messages[] = "2) Liste et occurences des genres";
				$liste_messages[] = "<table>";
				$liste_messages[] = "<tr><th>Genre</th><th>Nb de messages</th><th>Pourcentage</th></tr>";
				foreach ($tabJour_genres as $key=>$genre) {
					$pourcentage = round($genre['nb'] * 100 / $nb_donnees, 4);
					$liste_messages[] = '<tr><td>'.$genre['intitule'].'</td/><td>'.$genre['nb']. ' messages</td><td>('.round($genre['nb']*100/$nb_donnees, 4).' %)</td></tr>';
				}
				$liste_messages[] = "</table>";			
			}
		}
		if ($toSend == true) {
    		// Ajout du 30/09/2015 Ipc Version 1.11.5 : Les rapports sont envoyés si le paramètre de configuration [autorisation_mails] = 1
    		$autorisation_envoi_rapport = $this->configuration->SqlGetParam($this->dbh, 'autorisation_mails');
    		if ($autorisation_envoi_rapport == 0) {
    		    //echo "Envoi du rapport d'analyse refusé: Veuillez revoir le paramètre 'autorisation_mails'\n";
				$commande = "rm $flagRapportAnalyse";
				exec($commande);
    		    return("Envoi du rapport d'analyse refusé: Veuillez revoir le paramètre 'autorisation_mails'");
    		}
			$this->email->sendAnalyse($this->email_admin, $titre, 'Mail', $liste_messages);
		}
		// Libération du flag
		$commande = "rm $flagRapportAnalyse";
		exec($commande);
		if ($toSend == true) {
			return("Rapport d'Analyse envoyé à l'adresse ".$this->email_admin);
		} else {
			return($liste_messages);
		}
	}
}


//	Fonction permmettant l'envoi d'un email de rapport de connexion
public function rapportSecurite($post_date_rapport = null) {
    // Ajout du 30/09/2015 Ipc Version 1.11.5 : Les rapports sont envoyés si le paramètre de configuration [autorisation_mails] = 1
    $autorisation_envoi_rapport = $this->configuration->SqlGetParam($this->dbh, 'autorisation_mails');
    if ($autorisation_envoi_rapport == 0) {
        return("Envoi du mail de sécurité refusé: Veuillez revoir le paramètre 'autorisation_mails'");
    }

	$this->setFichiersLog($post_date_rapport);
	$flagRapportSecurity = '/tmp/.flagSymfonyRapportSecurity';
	if (file_exists($flagRapportSecurity)) {
		return('Le rapport de sécurité est déjà en cours de création');
	} else {
		$commande = "touch $flagRapportSecurity";
		exec($commande);
		// Récupération de la date du jour précédent
		if ($post_date_rapport === null) {
			$post_date_rapport = $this->date_rapport;
			// Date jour mail correspond à la date du rapport avec un format francais
			$date_jour_mail = date('d/m/Y', strtotime("1 day ago" ));
		} else {
			$date_jour_mail = $post_date_rapport;
		}
		// Titre du mail
		$titre = "Rapport de Sécurité du $date_jour_mail pour le site ".$this->site->getAffaire().' - '.$this->site->getIntitule();
		$tab_log_connexions = array();
		$tab_des_connexions = array();
		$total_tentatives = 0;
		$total_success = 0;
		$total_fails = 0;
		$commande = $this->commande_cat."'".$this->fichier_log_security."' | grep '$post_date_rapport' | grep -A1 'Tentative de connexion'";
		$tmp_connexions = exec($commande, $tab_connexions);
		$tmp_nbIndices = count($tab_connexions);
		// Parcours du tableau des connexions
		for($indiceTab = 0; $indiceTab < $tmp_nbIndices; $indiceTab = $indiceTab + 2) {
			$ligne_connexion = $tab_connexions[$indiceTab];
			$ligne_retour_connexion = $tab_connexions[$indiceTab+1];
			// Vérification que la ligne contient des logs (La ligne doit contenir au moins un caractère alphanumérique
			$pattern_check_lign = '/^[^a-z]+$/';
			if (preg_match($pattern_check_lign, $ligne_connexion)) {
				$indiceTab --;
				continue;
			}
			// Récupération du label de l'utilisateur
			$pattern = '/Tentative de connexion d?e?\s?(.+?)$/';
			if (preg_match($pattern, $tab_connexions[$indiceTab], $tabRetour)) {
				$total_tentatives ++;
				$label = $tabRetour[1];
				// Recherche du type de connexion SUCCESS ou FAILED
				// Recherche des droits de connexion (Admin;Technicien ou Client)
				$droits_connexion = $this->droitsConnexion($ligne_retour_connexion);
				if (substr($label, 0, 6) == 'locale') {
					$label = 'locale';
					// Pour les accès locaux : les droits d'accès sont définis dans la ligne de tentative d'accès
					$droits_connexion['droits'] = $this->droitsConnexionLocal($ligne_connexion);
				}
				// Si le tableau de l'utilisateur est déjà Initialisé
				if (isset($tab_des_connexions[$label])) {
					$tab_des_connexions[$label]['nbTentatives'] = $tab_des_connexions[$label]['nbTentatives'] + 1;
					if ($droits_connexion['retour'] == 'SUCCESS') {
						$total_success ++;
						// Si une première connexion ayant ces droits pour l'utilisateur est detectée : Initialisation des paramètres
						if (! isset($tab_des_connexions[$label][$droits_connexion['droits']])) {
							$tab_des_connexions[$label][$droits_connexion['droits']] = array();
							$tab_des_connexions[$label][$droits_connexion['droits']]['nbSuccess'] = 1;
						} else {
							// Pour les autres connexions : Incrémentation du compteur
							$tab_des_connexions[$label][$droits_connexion['droits']]['nbSuccess'] = $tab_des_connexions[$label][$droits_connexion['droits']]['nbSuccess'] + 1;
						}
					} else {
						// Nombre de connexions en échec
						$total_fails ++;
						if (! isset($tab_des_connexions[$label]['nbFails'])) {
							$tab_des_connexions[$label]['nbFails'] = 1;
						} else {
							$tab_des_connexions[$label]['nbFails'] = $tab_des_connexions[$label]['nbFails'] + 1;
						}
					}
					// Récupération du retour de la connexion (SUCCESS OU FAILED)
				} else {
					$tab_des_connexions[$label] 			= array();
					$tab_des_connexions[$label]['log'] 		= array();
					$tab_des_connexions[$label]['nbTentatives'] 	= 1;
					if ($droits_connexion['retour'] == 'SUCCESS') {
						$total_success ++;
						$tab_des_connexions[$label][$droits_connexion['droits']] = array();
						$tab_des_connexions[$label][$droits_connexion['droits']]['nbSuccess'] = 1;
					} else {
						$total_fails ++;
						$tab_des_connexions[$label]['nbFails'] = 1;
					}
				}
				$tab_des_connexions[$label]['log'][] = $droits_connexion['horodatage'].' - '.$droits_connexion['droits'].' : '.$droits_connexion['retour'];
			}
		}
		$commande_deconnexion = $this->commande_cat."'".$this->fichier_log_security."' | grep '$post_date_rapport' | grep 'Demande de déconnexion'";
		$tmp_connexions = exec($commande_deconnexion, $tab_deconnexion);
		foreach ($tab_deconnexion as $deconnexion) {
			$pattern_deconnexion = '/^(.+?);Demande de déconnexion\sd?e?\s?(.+?)$/';
			if (preg_match($pattern_deconnexion, $deconnexion, $tab_de_deconnexion)) {
				$horodatage = $tab_de_deconnexion[1];
				$label = $tab_de_deconnexion[2];
				$tab_des_connexions[$label]['log'][] = $horodatage.' - Déconnexion';
			}
		}
		// Trie des logs
		foreach ($tab_des_connexions as $keyLabel => $param) {
			asort($tab_des_connexions[$keyLabel]['log']);
		}
		// Si aucune connexion pas d'envoi de rapport -> (Evite le spam)
		if (count($tab_des_connexions) > 0) {
			$liste_messages = array();
			$liste_messages[] = "$date_jour_mail : Rapport de sécurité du site ".$this->site->getAffaire().' - '.$this->site->getIntitule();
			$liste_messages[] = "1) Informations générales";
			$liste_messages[] = "Nombre d'utilisateurs ayant effectués des connexions : ".count($tab_des_connexions)."<br />";
			$liste_messages[] = "Nombre de tentatives de connexion : $total_tentatives<br />"; 
			$liste_messages[] = "Nombre de connexions en succès : $total_success<br />";
			$liste_messages[] = "Nombre de connexion en échec : $total_fails<br />";
			$liste_messages[] = "1) Détails des connexions";
			foreach ($tab_des_connexions as $keyLabel => $connexion) {
				if ($keyLabel == 'local') {
					$liste_messages[] = "Connexion de : $keyLabel<br />";
				} else {
					$liste_messages[] = "Connexion $keyLabel<br />";
				}
				foreach ($tab_des_connexions[$keyLabel]['log'] as $log) {
					$liste_messages[] = "- $log<br />";
				}
				$liste_messages[] = "<br />";
			}
			$this->email->sendAnalyse($this->email_adminIpcWeb, $titre, 'Mail', $liste_messages);
			$commande = "rm $flagRapportSecurity";
			exec($commande);
			return("Rapport de sécurité envoyé à l'adresse ".$this->email_adminIpcWeb);
		} else {
			$commande = "rm $flagRapportSecurity";
			exec($commande);
			return("Aucune connexion détectée -> Pas d'envoi de rapport");
		}
	}
}


//	Retourne le retour de la connexion indiqué dans la ligne de connexion (SUCCESS OU FAILED)
public function retourConnexion($ligne) {
	$retour_connexion = null;
	// Recherche du type de connexion SUCCESS ou FAILED
	$pattern_retour_connexion = '/^.+?;Connexion\s(.+?);/';
	if (preg_match($pattern_retour_connexion, $ligne, $tabRetourConnexion)) {
		$retour_connexion = $tabRetourConnexion[1];
	}
	return($retour_connexion);
}

public function droitsConnexionLocal($ligne) {
	$droits_connexion = '';
	$pattern_droits = '/\[(.+?)\]$/';
	if (preg_match($pattern_droits, $ligne, $tabRetourLocal)) {
		$droits_connexion = $tabRetourLocal[1];
	}
	return($droits_connexion);
}

public function droitsConnexion($ligne) {
	$type_connexion = array();
	$pattern_droits = '/^(.+?);Connexion\s(.+?);.*?(?:user="(.+?)",)?/';
	if (preg_match($pattern_droits, $ligne, $tabRetourLabel)) {
		$type_connexion['horodatage'] = $tabRetourLabel[1];
		$type_connexion['retour'] = $tabRetourLabel[2];
		$pattern_droitCompte = '/user="(.+?)",/';
		if (preg_match($pattern_droitCompte, $ligne, $tabRetourDroit)) {
			$type_connexion['droits'] = $tabRetourDroit[1];
		} else {
			$type_connexion['droits'] = null;
		}
	}
	return($type_connexion);
}
	
//	Fonction permettant l'envoi d'un mail de test
public function rapportTestMail($post_date_rapport = null) {
    // Ajout du 30/09/2015 Ipc Version 1.11.5 : Les rapports sont envoyés si le paramètre de configuration [autorisation_mails] = 1
    $autorisation_envoi_rapport = $this->configuration->SqlGetParam($this->dbh, 'autorisation_mails');
    if ($autorisation_envoi_rapport == 0) {
        return("Envoi du mail test refusé: Veuillez revoir le paramètre 'autorisation_mails'");
    }
	$this->setFichiersLog($post_date_rapport);
	if ($post_date_rapport == null) {
		$post_date_rapport=$this->getFrDate($this->date_jour);
	}
	$liste_messages = array();
	$version_boilerbox = $this->configuration->SqlGetParam($this->dbh, 'numero_version');
	$titre = "Test mail : site ".$this->site->getAffaire().' - '.$this->site->getIntitule();
	$liste_messages[] = "$post_date_rapport : Test d'envoi de mail";
	$liste_messages[] = "Version de BoilerBox : $version_boilerbox";
	$this->email->send($this->email_admin, $titre, 'Mail', $liste_messages);
	return ("Mail de test envoyé à l'adresse ".$this->email_admin);
}

// 	Fonction qui retourne l'intitulé ou le numéro du genre en fonction de l'id donné
private function getTypeGenre($tabGenre, $idGenre, $type) {
	if ($type == 'intitule') {
		return $tabGenre[$idGenre]['intitule'];
	}
	if ($type == 'numero') {
		return $tabGenre[$idGenre]['numero'];
	}
}

// Fonction qui va lire les paramètres d'administration pour les indiquer et envoyer un mail d'alerte en cas d'espace disque insuffisant
public function lectureSystemParametre() {
	// Lancement du script d'analyse du FS
	exec($this->commande_analyse_fs);
	echo "commande : ".$this->commande_analyse_fs."<br />";
	echo "fs : ".$this->fileParamSystem;
	// Lecture des paramètres
	$tab_parametre = array();
	$handle = @fopen($this->fileParamSystem, "r");
	if ($handle) {
		while (($buffer = fgets($handle, 4096)) !== false) {
			$pattern_result_df = '/^ResultatDF;(.+?);(.+?);(.+?);(.+?);(.+?);(.+?)$/';
			$pattern_mysql = '/TailleMysql:(.+?)$/';
			$pattern_boilerbox = '/TailleBoilerBox:(.+?)$/';
			if (preg_match($pattern_result_df, $buffer, $tab_resultDF)) {
				$tab_parametre['df'] = array();
				$tab_parametre['df']['systFic'] = $tab_resultDF[1];
				$tab_parametre['df']['sizeDisc'] = $tab_resultDF[2];
				$tab_parametre['df']['sizeUsed'] = $tab_resultDF[3];
				$tab_parametre['df']['sizeUnused'] = $tab_resultDF[4];
				$tab_parametre['df']['percent'] = $tab_resultDF[5];
				$tab_parametre['df']['filesystem'] = $tab_resultDF[6];
			} elseif (preg_match($pattern_mysql, $buffer, $tab_resultMysql)) {
				$tab_parametre['mysql'] = array();
				$tab_parametre['mysql']['size'] = $tab_resultMysql[1];
			} elseif (preg_match($pattern_boilerbox, $buffer, $tab_resultBoilerBox)) {
				$tab_parametre['boilerbox'] = array();
				$tab_parametre['boilerbox']['size'] = $tab_resultBoilerBox[1];
			}
		}
		if (!feof($handle)) {
			echo "Erreur: fgets() a échoué\n";
		}
		fclose($handle);
	}
	return($tab_parametre);
}   

// Fonction qui analyse le filesystem et envoi un mail en cas de seuil dépassé
// Appelée par la crontab
public function analyseFsSystem() {
	$tabParametres = $this->lectureSystemParametre();
	if (intval($tabParametres['df']['percent']) > $this->seuil_fs_alerte) {
		// Envoi du rapport d'alerte
		$mesageRetour = $this->rapportSystem($tabParametres);
	}
	return('Analyse du filesystem effectuée');
}


public function rapportSystem($tabParametres, $post_date_rapport = null) {
    // Ajout du 30/09/2015 Ipc Version 1.11.5 : Les rapports sont envoyés si le paramètre de configuration [autorisation_mails] = 1
    $autorisation_envoi_rapport = $this->configuration->SqlGetParam($this->dbh, 'autorisation_mails');
    if ($autorisation_envoi_rapport == 0) {
        return("Envoi du rapport système refusé: Veuillez revoir le paramètre 'autorisation_mails'");
    }
    if ($post_date_rapport == null) {
        $post_date_rapport = $this->getFrDate($this->date_jour);
    }
	$this->setFichiersLog($post_date_rapport);
	$date_jour_mail = date('d/m/Y');
	$liste_messages = array();
	$titre = "Rapport système : site ".$this->site->getAffaire().' - '.$this->site->getIntitule();
	$liste_messages[] = "$date_jour_mail : Rapport système du site ".$this->site->getAffaire().' - '.$this->site->getIntitule();
	$liste_messages[] = "1) Alertes";
	if (intval($tabParametres['df']['percent']) > $this->seuil_fs_alerte) {
		$liste_messages[] = "<span style='color:red;'>ALERTE : Seuil d'utilisation disque dépassé -> Pourcentage d'utilisation ".$tabParametres['df']['percent']." > $this->seuil_fs_alerte % !</span><br />";
	}
	$liste_messages[] = '2) Détails';
	$liste_messages[] = "<table><tr><td>Système de fichier :</td><td>".$tabParametres['df']['systFic']."</td></tr>";
	$liste_messages[] = "<tr><td>Espace disque total :</td><td>".$tabParametres['df']['sizeDisc']."</td></tr>";
	$liste_messages[] = "<tr><td>Espace disque utilisé :</td><td>".$tabParametres['df']['sizeUsed']."</td></tr>";
	$liste_messages[] = "<tr><td>Espace disque libre :</td><td>".$tabParametres['df']['sizeUnused']."</td></tr>";
	if (intval($tabParametres['df']['percent']) > $this->seuil_fs_alerte) {
		$liste_messages[] = "<tr><td><span style='color:red;'>Pourcentage d'espace disque utilisé :</span></td><td><span style='color:red;'>".$tabParametres['df']['percent']."</span></td></tr>";
	} else {
		$liste_messages[] = "<tr><td>Pourcentage d'espace disque utilisé :</td><td>".$tabParametres['df']['percent']."</td></tr>";
	}
	$liste_messages[] = "<tr><td>Espace disque utilisé par le site BoilerBox :</td><td>".$tabParametres['boilerbox']['size']."</td></tr>";
	$liste_messages[] = "<tr><td>Espace disque utilisé par Mysql :</td><td>".$tabParametres['mysql']['size']."</td></tr></table>";
	$this->email->send($this->email_admin,$titre,'Mail',$liste_messages);
	return ("Rapport Système envoyé à l'adresse ".$this->email_admin);
}


// Fonction qui retouaner la date passée en paramètre : Du format Y/m/d au format d/m/Y
private function getFrDate($enDate) {
	$date_retour  = '';
	$pattern_date = '/^(\d{4})([-\/])(\d{2})[-\/](\d{2})$/';
	if (preg_match($pattern_date,$enDate,$tabDate)) {
		$date_retour = $tabDate[4].$tabDate[2].$tabDate[3].$tabDate[2].$tabDate[1];
	} else {
		$date_retour = $enDate;
	}
	return $date_retour;
}


private function getMailDate($enDate) {
    $date_retour  = '';
    $pattern_date = '/^(\d{4})[-\/](\d{2})[-\/](\d{2})$/';
    if (preg_match($pattern_date, $enDate, $tabDate)) {
        $date_retour = $tabDate[3].$tabDate[2].$tabDate[1];
    } else {
        $date_retour = $enDate;
    }
    return $date_retour;
}



//  Fonction qui retouaner la date passée en paramètre : Du format Y/m/d au format d/m/Y
private function getEnDate($enDate) {
	$date_retour  = '';
	$pattern_date = '/^(\d{2})([-\/])(\d{2})[-\/](\d{4})$/';
	if (preg_match($pattern_date,$enDate,$tabDate)) {
		$date_retour = $tabDate[4].$tabDate[2].$tabDate[3].$tabDate[2].$tabDate[1];
	} else {
		$date_retour = $enDate;
	}
	return $date_retour;
}

private function setFichiersLog($date_rapport) {
	// Récupération des logs de la date du jour précédent
	if ($date_rapport == null) {
		$this->date_rapport = date('Y/m/d', strtotime("1 day ago" ));
	} else {
		$this->date_rapport	= $this->getEnDate($date_rapport);
	}
	// Si la date du rapport n'est pas la date du jour, recherche dans un fichier backup
	if (strtotime($this->date_rapport) != strtotime($this->date_jour)) {
		$date_fichier_rapport = date('Ymd', strtotime($this->date_rapport));
		$this->fichier_log_ipc = $this->log->getLogDir().'backup/'.$date_fichier_rapport.'/'.$date_fichier_rapport.'*_importBin.log.bz2';
		$this->fichier_log_transfert = $this->log->getLogDir().'backup/'.$date_fichier_rapport.'/'.$date_fichier_rapport.'*_transfertFtp.log.bz2';
		$this->fichier_log_security = $this->log->getLogDir().'backup/'.$date_fichier_rapport.'/'.$date_fichier_rapport.'*_login.log.bz2';
		$this->commande_cat = "bzcat ";
	} else {
		$this->fichier_log_ipc = $this->log->getLogDir().'importBin.log';
		$this->fichier_log_transfert = $this->log->getLogDir().'transfertFtp.log';
		$this->fichier_log_security = $this->log->getLogDir().'login.log';
		$this->commande_cat = "cat ";
	}
}

// Ajout du 20/04/2017 : Indique si le mail doit être envoyé ou pas
public function setSendMail($valueSend){
	$this->sendMail = $valueSend;
}


// Déplacement des doublons
public function moveDoublons(){
    $entities_tmpdoublons =  $this->em->getRepository('IpcProgBundle:Donneetmp')->findBy(
                                                                            array('erreur' => 'DD'),
                                                                            array('horodatage' => 'ASC'));
    foreach($entities_tmpdoublons as $entity_tmpdoublon) {
        $entity_doublon = new DonneeDoublon();
        $entity_doublon->setErreur($entity_tmpdoublon->getErreur());
        $entity_doublon->setHorodatage($entity_tmpdoublon->getHorodatage());
        $entity_doublon->setCycle($entity_tmpdoublon->getCycle());
        $entity_doublon->setValeur1($entity_tmpdoublon->getValeur1());
        $entity_doublon->setValeur2($entity_tmpdoublon->getValeur2());
        $entity_doublon->setNumeroGenre($entity_tmpdoublon->getNumeroGenre());
        $entity_doublon->setCategorie($entity_tmpdoublon->getCategorie());
        $entity_doublon->setNumeroModule($entity_tmpdoublon->getNumeroModule());
        $entity_doublon->setNumeroMessage($entity_tmpdoublon->getNumeroMessage());
        $entity_doublon->setNomFichier($entity_tmpdoublon->getNomFichier());
        $entity_doublon->setAffaire($entity_tmpdoublon->getAffaire());
        $entity_doublon->setNumeroLocalisation($entity_tmpdoublon->getNumeroLocalisation());
        $entity_doublon->setProgramme($entity_tmpdoublon->getProgramme());
        $this->em->persist($entity_doublon);
        $this->em->remove($entity_tmpdoublon);
    }
    $this->em->flush();
}

}
