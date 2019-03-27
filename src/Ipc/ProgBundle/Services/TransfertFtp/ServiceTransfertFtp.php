<?php 
//src/Ipc/ProgBundle/Services/TransfertFtp/ServiceTransfertFtp
//	Service pemettant le transfert des fichiers ( des automates ) par le protocol ftp 
namespace Ipc\ProgBundle\Services\TransfertFtp;

use Ipc\ProgBundle\Entity\Site;
use Ipc\ProgBundle\Entity\Configuration;

class ServiceTransfertFtp {
protected $dbh;
protected $site;
protected $localisations;
protected $log;
protected $fichier_log;
// Dossier dans lequel sont placés les fichiers transférés : Variable dossier_fichiers_originaux
protected $dossier_local;
protected $email;
protected $configuration;
protected $email_admin;
protected $tab_erreurs_transfert_ftp;
protected $nombre_total_transfert;
protected $autorisation_envoi_rapport;
protected $frequence_rapport;
protected $frequence_max_rapport;
protected $date_frequence_rapport;
protected $etat_connexion;
protected $autorisation_frequence_rapport;
protected $em;
protected $entity_configuration;
protected $s_fill_numbers;

public function __construct($srv_connexion, $log, $email, $fillNumbers) {
	$this->dbh = $srv_connexion->getDbh();
	$this->em = $srv_connexion->getManager();

	$this->log = $log;
	$this->email = $email;
	$this->fichier_log = 'transfertFtp.log';
	$this->s_fill_numbers = $fillNumbers;
	// Récupération des infos sur le site
	$site = new Site();
	$site_id = $site->SqlGetIdCourant($this->dbh);
	$this->site = $this->em->getRepository('IpcProgBundle:Site')->find($site_id);
	$this->localisations = $this->site->getLocalisations();
	$this->configuration = new configuration();
	$this->dossier_tmpftp = $this->configuration->SqlGetParam($this->dbh, 'dossier_fichiers_tmpftp');
	$this->dossier_local = $this->configuration->SqlGetParam($this->dbh, 'dossier_fichiers_originaux');
	$this->tab_erreurs_transfert_ftp = array();
	$this->nombre_total_transfert = 0;
	// Ajout du 30/09/2015 Ipc Version 1.11.5 : Les rapports sont envoyés si le paramètre de configuration [autorisation_rapports_erreur] = 1
	$autorisation_envoi_mail = $this->configuration->SqlGetParam($this->dbh, 'autorisation_mails');
	if ($autorisation_envoi_mail == 1) {
		$this->autorisation_envoi_rapport = $this->configuration->SqlGetParam($this->dbh, 'autorisation_rapports_erreur');
	} else {
		$this->autorisation_envoi_rapport = 0;
	}
	if ($this->autorisation_envoi_rapport == 0) {
		echo "Envoi des rapports d'erreur ftp non autorisé\n";
	}
}

public function importation() {
	$date_tmp = new \Datetime();
	$date_message = $date_tmp->format('d-m-Y H:i');
	$flagArretServeur = '/tmp/.flagSymfonyArretServeur';
	$flagArretServiceTransfertFtp = '/tmp/.flagArretServiceTransfertFtp';
	$this->email_admin = $this->configuration->SqlGetParam($this->dbh, 'admin_email');
	if ( (! $this->dossier_local) || (! is_dir($this->dossier_local)) ) {
		$this->log->setLog("[ERROR] [CRITIQUE];Configuration Error;;Le paramètre 'dossier_fichiers_originaux' ou le dossier pointé n'existe pas. Transfert des fichiers non possible.", $this->fichier_log);
		$liste_messages = array();
		$titre = $this->site->getAffaire()." Echec Critique";
		$liste_messages[] = "[ERROR] Transfert de fichiers impossible sur le site ".$this->site->getAffaire();
		$liste_messages[] = "Date : $date_message<br />";
		$liste_messages[] = "Le paramètre 'dossier_fichiers_originaux' ou le dossier pointé n'existe pas en base de donnée.<br />";
		$liste_messages[] = "Veuillez le créer pour définir le dossier des fichiers à convertir en binaire";
		$this->email->send($this->email_admin, $titre, 'Mail', $liste_messages);
		return(1);
	}
	if ( (! $this->dossier_tmpftp) || (! is_dir($this->dossier_tmpftp)) ) {
		$this->log->setLog("[ERROR] [CRITIQUE];Configuration Error;;Le paramètre 'dossier_fichiers_tmpftp' ou le dossier pointé n'existe pas n'existe pas. Transfert des fichiers non possible.", $this->fichier_log);
		$liste_messages	= array();
		$titre = $this->site->getAffaire()." Echec Critique";
		$liste_messages[] = "[ERROR] Transfert de fichiers impossible sur le site ".$this->site->getAffaire();
		$liste_messages[] = "Date : $date_message<br />";
		$liste_messages[] = "Le paramètre 'dossier_fichiers_tmpftp' ou le dossier pointé n'existe pas en base de donnée.<br />";
		$liste_messages[] = "Veuillez le créer pour définir le dossier de destination des fichiers transférés par ftp";
		$this->email->send($this->email_admin, $titre, 'Mail', $liste_messages); 
		return(1);
	}
	// Vérification : Si un flag de téléchargement Ftp existe -> Pas de téléchargement
	$flagFtp = "/tmp/.flagSymfonyDownloadFtp";
	if (file_exists($flagFtp)) {
		$this->log->setLog("[INFO];".$this->site->getAffaire().";Le téléchargement des fichiers par ftp est déjà en cours d'execution", $this->fichier_log);
	} else {
		// Création du flag importationFtp pour bloquer le téléchargement par d'autres programmes
		$commande = "touch $flagFtp";
		exec($commande);
		$commande = "chmod 666 $flagFtp";
		exec($commande);
		//$commande = "chown wwwrun $flagFtp";
		//exec($commande);
		// Log du début de récupération des fichiers distant
		$this->log->setLog("[INFO];".$this->site->getAffaire().";Début de transfert ftp", $this->fichier_log);
		// Boucle pour chaque localisation du site courant
		foreach ($this->localisations as $localisation) {
			$designation = $localisation->getDesignation();
			$adresseIp = $localisation->getAdresseIp();
			$login = $localisation->getLoginFtp();
			$password = $localisation->getPasswordFtp();
			// Récupération de la date de la dernière analyse d'envoi de rapport
			// Si la date n'est pas la date du jour => Changement de la date et initialisation du paramètre de fréquence des rapports
			$param_frequence_rapport = $this->configuration->SqlGetParam($this->dbh, "${adresseIp}_frequence_rapport_ftp");
			if (! $param_frequence_rapport) {
				$this->log->setLog("[ERROR] [CRITIQUE];Configuration Error;$adresseIp;Le paramètre '${adresseIp}_frequence_rapport_ftp' n'existe pas. Transfert des fichiers non possible.",$this->fichier_log);
				$liste_messages = array();
				$titre = $this->site->getAffaire()." Echec Critique";
				$liste_messages[] = "[ERROR] Transfert de fichiers impossible depuis la localisation ".$this->site->getAffaire().":$adresseIp";
				$liste_messages[] = "Date : $date_message<br />";
				$liste_messages[] = "Le paramètre '${adresseIp}_frequence_rapport_ftp' n'existe pas en base de donnée.";
				$this->email->send($this->email_admin, $titre, 'Mail', $liste_messages);
				continue;
			}
			$this->entity_configuration = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre("${adresseIp}_frequence_rapport_ftp");
			// Récupération des informations du paramètre ${adresseIp}_frequence_rapport_ftp 
			// - 1 : Date de la tentative de connexion.
  			// - 2 : Nombre de tentatives en échec pour considérer que la connexion est défaillante.
			// - 3 : Nombre de tentatives en échec lors de la dernière connexion
			// - 4 : Booleen : Etat de la connexion -> 1 Ok; 0 Nok
			$tab_frequence_rapport = explode(';', $param_frequence_rapport);
			if (count($tab_frequence_rapport) != 4) {
				$this->log->setLog("[ERROR] [CRITIQUE];Configuration Error;$adresseIp;Le paramètre '${adresseIp}_frequence_rapport_ftp' n'est pas correctement renseigné.",$this->fichier_log);
                $liste_messages = array();
                $titre = $this->site->getAffaire()." Echec Critique";
                $liste_messages[] = "[ERROR] Paramètre de configuration incorrect pour la localisation ".$this->site->getAffaire().":$adresseIp";
                $liste_messages[] = "Date : $date_message<br />";
                $liste_messages[] = "Le paramètre '${adresseIp}_frequence_rapport_ftp' n'est pas correctement renseigné.";
                $this->email->send($this->email_admin, $titre, 'Mail', $liste_messages);
                continue;
			}
			$this->date_frequence_rapport = $tab_frequence_rapport[0];
			$this->frequence_max_rapport = $tab_frequence_rapport[1];
			$this->frequence_rapport = $tab_frequence_rapport[2];
			$this->etat_connexion = $tab_frequence_rapport[3];
			$date_tmp = new \Datetime();
			$date = strtotime($date_tmp->format('Y-m-d'));
			// Si la date n'est pas la date du jour => Changement de la date et initialisation du paramètre de fréquence des rapports
			if (date(strtotime($this->date_frequence_rapport)) != $date) {
				$this->date_frequence_rapport = $date_tmp->format('d-m-Y');
				$this->frequence_rapport = 0;
			}	
			//$this->log->setLog("[INFO];$adresseIp;Paramètres récupérés:".$this->date_frequence_rapport.";".$this->frequence_max_rapport.";".$this->frequence_rapport, $this->fichier_log);
			// Si on indique -1 au nombre max : Blocage de l'envoi des mails
			if ($this->frequence_max_rapport == -1) {
				$this->log->setLog("[INFO];$adresseIp;Envoi des rapports d'erreurs bloqué par le paramètre frequence_rapport_ftp", $this->fichier_log);
				$this->autorisation_frequence_rapport = 0;
			} else {
				if ($this->frequence_rapport == 0) {
					// Envoi du rapport
					$this->autorisation_frequence_rapport = 1;
					if ($this->frequence_max_rapport != 0) {
						$this->frequence_rapport ++;
					}
				} elseif ($this->frequence_rapport < $this->frequence_max_rapport) {
					// Pas d'envoi de rapport si le nombre de la fréquence est < au nombre max défini
					$this->autorisation_frequence_rapport = 0;
					$this->frequence_rapport ++;
				} elseif ($this->frequence_rapport == $this->frequence_max_rapport){
					// Pas d'envoi du rapport si le nombre de la fréquence est = au nombre max défini
					$this->autorisation_frequence_rapport = 0;
					$this->frequence_rapport = 0;
				}
			}
			// Fin de partie : Récupération de la date de la dernière analyse d'envoi de rapport *************************
			// Appel de la fonction de connexion à l'équipement : retourne identifiant de connexion ou null
			$id_connexion_ftp = $this->connexion($adresseIp, $login, $password, $designation);
			// Fin de fonction si echec de connexion
			if ($id_connexion_ftp == null) {
				// Passage à l'équipement suivant
				continue;
			}
			// Récupération du contenu du dossier 
			// En cas d'echec : log + passage à l'équipement suivant
			$liste_fichiers_distants = ftp_nlist($id_connexion_ftp, '.');
			$nb_fichiers_distants = count($liste_fichiers_distants);
			if ($nb_fichiers_distants == 0) {
				$this->log->setLog("[INFO];$adresseIp;Echec lors du listing des fichiers du dossier ou aucun fichier à transférer",$this->fichier_log);
				// Lire dans le fichier de log si pour l'adresse ip des fichiers ont été transférés il y a moins de x jours
				$this->deconnexion($id_connexion_ftp, $adresseIp);
				// Recherche d'un transfert réussit
				$last_transfert = $this->rechercheLastTransfert("$adresseIp;correctement transféré", $adresseIp, $designation);
				// Modification des paramètres fréquence rapport
				$this->miseAjourFrequence();
				// Passage à l'équipement suivant
				continue;
			} else {
				$this->log->setLog("[INFO];$adresseIp;Nombre de fichiers à transférer;$nb_fichiers_distants",$this->fichier_log);
				// Si des fichies sont à transférer: Lancement du transfert Ftp
				// Nombre de fichier transféré par localisation
				$nb_fichiers_transferes = 0;
				foreach ($liste_fichiers_distants as $nom_fichier) {
					// Si la demande d'arret du serveur est detectée : Arrêt du script de transfert Ftp
					// Si le flag d'arret du serveur est présent : Arrêt du traitement
					if (file_exists($flagArretServeur) || file_exists($flagArretServiceTransfertFtp)) {
						$this->log->setLog("[INFO];$adresseIp;Arrêt du serveur demandé : Arrêt du script d'importation en base de données",$this->fichier_log);
						$this->tab_erreurs_transfert_ftp[] = "Arrêt du serveur demandé : Arrêt du script d'importation en base de données";
						break;
					}
					// 1 Fichier Transféré ; 0 Fichier Non Transféré
					$retourTransfert = $this->transfertFtp($id_connexion_ftp, $nom_fichier, $adresseIp,$designation);
					$nb_fichiers_transferes   += $retourTransfert;
				}
				$this->log->setLog("[INFO];$adresseIp;Nombre de fichiers transférés;$nb_fichiers_transferes", $this->fichier_log);
				// Si le nombre de fichiers à transférer est différent du nombre de fichier transférés : Envoi d'un mail avec indication des erreurs rencontrées
				if ($nb_fichiers_transferes != $nb_fichiers_distants) {
					$titre = "Echec de transfert Ftp depuis le site ".$this->site->getAffaire()." - ".$this->site->getIntitule()." - Automate : $designation";
					$liste_messages = array();
					$liste_messages[] = $this->site->getAffaire()." - ".$this->site->getIntitule()." : Echec de transfert ftp depuis l'automate $designation ($adresseIp)";
					$liste_messages[] = "Date : $date_message<br />";
					$liste_messages[] = "Liste des erreurs<br />";
					foreach ($this->tab_erreurs_transfert_ftp as $erreur_transfert) {
						$liste_messages[] = $erreur_transfert;
					}
					// Si l'envoi des mails d'erreur est autorisé, envoi des mails si le nombre de fréquence des rapports est < au nombre de rapport/jour
					// On incrémente l'indicateur de la fréquence à chaque tentative de transfert ftp.
					// Si la féquence = 0 : On envoi le rapport
					// Si la fréquence est inférieure au nombre max de la fréquence on incrémente le compteur
					if ($this->autorisation_envoi_rapport == '1') {
						if ($this->autorisation_frequence_rapport == 0) {
							$this->email->send($this->email_admin,$titre,'Mail',$liste_messages);
						}
					}
				}
			}
			// Modification des paramètres fréquence rapport
			$this->miseAjourFrequence();
			// Déconnexion 
			$this->deconnexion($id_connexion_ftp, $adresseIp);
		}
		$this->log->setLog("[INFO];".$this->site->getAffaire().";Fin des transferts ftp sur le site", $this->fichier_log);
		# Libération du flag
		$commande = "rm $flagFtp";
		exec($commande);
	}
}

protected function miseAjourFrequence() {
	// Modification des paramètres fréquence rapport
	$tmp_new_parametre_frequence_rapport = $this->date_frequence_rapport.';'.$this->frequence_max_rapport.';'.$this->frequence_rapport.';'.$this->etat_connexion;
	$this->entity_configuration->setValeur($tmp_new_parametre_frequence_rapport);
	$this->entity_configuration->SqlUpdate($this->dbh);
	return(0);
}

protected function transfertFtp($connexion_id, $nom_fichier_distant, $adresseIp, $designation) {
	$this->log->setLog("[INFO];$adresseIp;Début de transfert du fichier $nom_fichier_distant de l'équipement $designation", $this->fichier_log);
	$erreur = null;
	// Indique si le transfert est réussit ou en échec
	$indicateur_transfert = 0;
	$nom_fichier_local = $this->dossier_tmpftp.'/'.$nom_fichier_distant;
	$nom_fichier_origine = $this->dossier_local.'/'.$nom_fichier_distant;
	$this->log->setLog("[INFO];$adresseIp;FTP param : $connexion_id, $nom_fichier_local, $nom_fichier_distant, FTP_BINARY", $this->fichier_log);
	// Bug détecté si le fichier existe déjà dans le dossier tmpftp. => Correction : supprimer le fichier si il existe déjà
	if (file_exists($nom_fichier_local)) {
		$this->log->setLog("[ERROR];$adresseIp;Le fichier $nom_fichier_distant existe déjà dans le répertoire tmpftp.", $this->fichier_log);
		unlink($nom_fichier_local);
		$this->log->setLog("[DEBUG];$adresseIp;Fichier $nom_fichier_distant supprimé du répertoire tmpftp.", $this->fichier_log);
	}
	if (ftp_get($connexion_id, $nom_fichier_local, $nom_fichier_distant, FTP_BINARY)) {
		$this->log->setLog("[INFO];$adresseIp;$nom_fichier_distant;FTP GET OK", $this->fichier_log);
		$taille_fichier_distant = ftp_size($connexion_id, $nom_fichier_distant);
		$taille_fichier_local = filesize($nom_fichier_local);
		// Si les tailles diffère, une erreur de copie a été rencontrée : Pb filesystem etc.
		// Si les tailles sont identiques : Suppression du fichier distant
		// Si la taille d'un des fichiers est égale à 0 -> Pas de suppression du fichier distant
		if (($taille_fichier_distant != 0) && ($taille_fichier_local != 0)) {
			if ($taille_fichier_distant == $taille_fichier_local) {
				// Suppression des fichiers ftp distants si le téléchargement s'est correctement passé
				if (ftp_delete($connexion_id, $nom_fichier_distant)) {
					// Déplacement du fichier local vers le dossier des fichiers à convertir en binaire
					if (copy($nom_fichier_local, $nom_fichier_origine)) {
						if (unlink($nom_fichier_local)) {
							// Incrémentation de la variable des fichiers transférés : Toutes localisations confondues
							$this->nombre_total_transfert += 1;
							$this->log->setLog("[INFO];$adresseIp;Fichier $nom_fichier_distant de l'équipement $designation correctement transféré;".$this->nombre_total_transfert, $this->fichier_log);
						} else {
							$erreur = "[ERROR];Suppression du fichier local;$nom_fichier_local;Echec lors de la suppression du fichier, Veuillez le supprimer manuellement";
						}
					} else {
						$erreur = "[ERROR];Copie du fichier;$nom_fichier_local;Echec lors de la copie du fichier vers le répertoire des fichiers à convertir";
					}
				} else {
					$erreur = "[ERROR];Suppression du fichier distant;$adresseIp;Erreur lors de la suppression du fichier $nom_fichier_distant sur l'équipement $designation";
				}
			} else {
				$erreur = "[ERROR];Poids des fichiers;$adresseIp:$nom_fichier_distant;Le fichier copié de l'équipement $designation n'a pas la même taille en local : Destination [$nom_fichier_local]. Pas de suppression du fichier distant";
				if (! unlink($nom_fichier_local)) {
					$erreur .= " [ERROR];FND;$adresseIp;Erreur lors de la suppression du fichier $nom_fichier_distant sur l'équipement $designation";
				}
			}
		} else {
			$erreur = "[ERROR];Fichier vide;$adresseIp;Fichier vide détecté;Fichier distant $nom_fichier_distant non supprimé";
		}
	} else {
		$erreur = "[ERROR];Erreur de transfert;$adresseIp;Erreur lors du transfert ftp du fichier $nom_fichier_distant depuis l'équipement $designation";
	}
	if ($erreur != null) {
		// Si une erreur a lieu lors du transfert : incrémentation du tableau des erreurs de transfert : Tableau qui est transmit par mail à la fin du téléchargement
		$this->tab_erreurs_transfert_ftp[] = $erreur;
		$this->log->setLog($erreur, $this->fichier_log);
	} else {
		$indicateur_transfert = 1;
	}
	$this->log->setLog("[INFO];$adresseIp;Fin de transfert du fichier $nom_fichier_distant de l'équipement $designation", $this->fichier_log);
	return($indicateur_transfert);
}

		
// Etablissement d'une connexion ftp vers une localisation distante
// si la connexion est en echec : Log de l'erreur CRITIQUE + Appel au service d'envoi de mail
protected function connexion($adresseIp, $login, $password, $designation) {
	$erreur = null;
	// Etablissement de la connexion à l'équipement
	$this->log->setLog("[INFO];$adresseIp;Tentative de Connexion",$this->fichier_log);
	$connexion_id = ftp_connect($adresseIp);
	if (! $connexion_id) {
		$erreur = "[ERROR];Connexion Error;$adresseIp;Echec de la connexion à l'équipement : $designation"; 
	} else {
		// Identification sur l'équipement
		if (@ftp_login($connexion_id, $login, $password)) {
			$this->log->setLog("[INFO];$adresseIp;Connexion établie vers l'équipement $designation", $this->fichier_log);
			// Activation du mode passif :  En mode passif, les données de connexion sont initiées par le client, plutôt que par le serveur. Ce mode peut être nécessaire lorsque le client est derrière un pare-feu. 
			if (! ftp_pasv($connexion_id, true)) {
				$erreur = "[ERROR];Connexion Error;$adresseIp;Echec de l'activation du mode passif sur la connexion à l'équipement : $designation";
				// Appel de la fonction de cloture de connexion
				$this->deconnexion($connexion_id, $adresseIp);
			}
		} else {
			// Appel de la fonction de cloture de connexion
			$this->deconnexion($connexion_id, $adresseIp);
			$erreur = "[ERROR];Connexion Error;$adresseIp;Echec lors de l'identification à la connexion sur l'équipement : $designation";
		}
	}
	// Si aucune erreur : Retour de l'identifiant de connexion
	if ($erreur == null) {
		// Si la connexion est correcte et si l'Etat de la connexion précédente est en echec : Envoi d'un mail de récupération de la connexion
		if ($this->etat_connexion == 0) {
			$this->log->setLog("[ERROR];Connexion Error;$adresseIp;La connexion FTP est récupérée.", $this->fichier_log);
			$date_tmp = new \Datetime();
			$date_message = $date_tmp->format('d-m-Y H:i');
			$titre = $this->site->getAffaire()." - ".$this->site->getIntitule()." - Récupération de connexion FTP vers $designation";
            $liste_messages = array();
            $liste_messages[] = "Récupération de la connexion FTP sur ".$this->site->getIntitule();
			$liste_messages[] = "1) Détail";
			$liste_messages[] = "Le ".$this->s_fill_numbers->getDateFromHorodatage('date', $date_message)." à ".$this->s_fill_numbers->getDateFromHorodatage('heure', $date_message)."<br />";
			$liste_messages[] = "2) Informations";
			$liste_messages[] = "La connexion FTP vers l'équipement $adresseIp est récupérée.";
			$this->email->send($this->email_admin, $titre, 'Mail', $liste_messages);
			// Réinitialisation du paramètre 'frequence_rapport' et changement d'état du paramètre de connexion courante : 'etat_connexion'
			$this->frequence_rapport = 0;
			$this->etat_connexion = 1;
			$this->miseAjourFrequence();
		}
		return($connexion_id);
	} else {
		$message_mail_critique = "[CRITIQUE];$adresseIp;";
		$titre = $this->site->getAffaire()." - ".$this->site->getIntitule()." - Perte de connexion FTP vers $designation";
		$date_tmp = new \Datetime();
		$date_message = $date_tmp->format('d-m-Y H:i');
		if ($this->autorisation_envoi_rapport == '1') {
			if ($this->autorisation_frequence_rapport == 1) {
				// Lorsqu'on considère la connexion en erreur, Si la connexion n'était pas déjà en etat 'Erreur' on envoi un mail et on change le paramètre : Etat de la connexion
				// -> Permet de n'envoyer un mail qu'une fois lors de la perte de connexion
				if ($this->etat_connexion != 0) {
					$this->etat_connexion = 0;	
        			$liste_messages = array();
        			$liste_messages[] = "Perte de la connexion FTP vers ".$this->site->getIntitule();
        			$liste_messages[] = "1) Détail";
        			$liste_messages[] = "Le ".$this->s_fill_numbers->getDateFromHorodatage('date', $date_message)." à ".$this->s_fill_numbers->getDateFromHorodatage('heure', $date_message)."<br />";
        			$liste_messages[] = "%lc%".$erreur;
        			$liste_messages[] = "2) Informations";
					$liste_messages[] = "Aucun fichier transféré, risque de surchage sur l'équipement.<br />";
        			$liste_messages[] = "La vérification des paramètres FTP et de l'état de la connexion est nécessaire.<br />";
					$this->email->send($this->email_admin, $titre, 'Mail', $liste_messages);
					$message_mail_critique .= "Envoi du mail d'erreur effectué";
					$this->miseAjourFrequence();
				} else {
					$message_mail_critique = "Mail d'erreur envoyé précédemment";
				}
			} else {
				if ($this->etat_connexion == 0) {
					$message_mail_critique = "Mail d'erreur envoyé précédemment";
				} else {
					$message_mail_critique .= "Mail d'erreur non envoyé - Blocage par le paramètre autorisation_frequence_rapport ($this->frequence_rapport / $this->frequence_max_rapport).";
					$this->miseAjourFrequence();
				}
			}
		} else {
			if ($this->etat_connexion == 0) {
				$message_mail_critique = "Mail d'erreur envoyé précédemment.";
			} else {
				$message_mail_critique .= "Mail d'erreur non envoyé - Blocage par le paramète autorisation_envoi_rapport.";
				$this->miseAjourFrequence();
			}
		}
		$this->log->setLog($erreur, $this->fichier_log);
		$this->log->setLog($message_mail_critique, $this->fichier_log);
		return(null);
	}
}


protected function deconnexion($connexion_id, $adresseIp) {
	if (ftp_close($connexion_id)) {
		$this->log->setLog("[INFO];$adresseIp;Déconnexion",$this->fichier_log);
	} else {
		$this->log->setLog("[ERROR];Deconnexion Error;$adresseIp;Echec lors de la déconnexion", $this->fichier_log);
	}
}


// Recherche de la date du dernier fichier transféré
protected function rechercheLastTransfert($liste_mots, $adresseIp, $designation) {
	$date_tmp = new \Datetime();
	$date_message = $date_tmp->format('d-m-Y H:i');
	// Lecture du fichier de log à la recherche des termes : adresse Ip + correctement transféré
	$texte_log = $this->log->rechercheLastTexte($this->fichier_log, $liste_mots);
	// Si pas de texte : Aucun transfert réussit. Envoi d'un message d'alert
	if (! $texte_log) {
		$titre = $this->site->getAffaire()." - Echec des transfert Ftp des fichiers de l'équipement $designation";
		$liste_messages = array();
		$liste_messages[] = "Echec des transfert Ftp depuis ".$this->site->getIntitule();
		$liste_messages[] = "1) Détail";
		$liste_messages[] = "Date : $date_message<br />";
		$liste_messages[] = "[ERROR];Transfert Error;$adresseIp;Aucun fichier transféré depuis l'équipement $designation";
		$liste_messages[] = "2) Informations";
		$liste_messages[] =  "Risque de surchage sur l'équipement. Veuillez analyser svp";
		return(1);
	} else {
		// Recherche de la date du message : Format JJ/MM/AAAA
		$pattern = '/^(.+?);/';
		$date_du_transfert = "";
		if (preg_match($pattern, $texte_log, $date)) {
			$date_du_transfert = strtotime($this->inverseDate($date[1]));
		} else {
			// La ligne est mal configuré : Email 
			$titre = $this->site->getAffaire()." - $designation - Echec de log";
			$liste_messages = array();
			$liste_messages[] = "Echec de lecture des logs depuis ".$this->site->getIntitule();
			$liste_messages[] = "1) Détails";
			$liste_messages[] = "Date : $date_message<br />";
			$liste_messages[] = "[ERROR];Log Error;$adresseIp;Ligne de log incorrecte retournée lors de la recherche du dernier fichier transféré";
			$liste_messages[] = "log : $texte_log";
			$this->email->send($this->email_admin, $titre, 'Mail', $liste_messages);
			return(1);
		}
		// Récupération de la date de référence
		$nb_max_jours_sans_transfert = $this->configuration->SqlGetParam($this->dbh, 'nb_max_jours_sans_transfert');
		// Si le parametre n'est pas définit : Alerte Email
		if (! $nb_max_jours_sans_transfert) {
			$this->log->setLog("[ERROR] [CRITIQUE];Configuration Error;;Le paramètre 'nb_max_jours_sans_transfert' n'existe pas.", $this->fichier_log);
			$titre = "Echec Critique : Paramètre de configuration manquant";
			$liste_messages = array();
			$liste_messages[] = "Date : $date_message<br />";
			$liste_messages[] = "[ERROR];[nb_max_jours_sans_transfert];Paramètre de configuration manquant<br />";
			$liste_messages[] = "Ce paramètre indique le nombre de jours sans transfert de fichiers avant déclanchement d'une alert email<br />";
			$liste_messages[] = "Veuillez créer le paramètre en base de données svp";
			$this->email->send($this->email_admin, $titre, 'Mail', $liste_messages);
			return(1);
		}
		$date_de_reference = strtotime(date('Y/m/d'))-($nb_max_jours_sans_transfert*86400);
		// Si la date est < à la date de reference ( = date du jour - 2 jours) : Alert Email
		if ($date_du_transfert < $date_de_reference) {
			if ($this->autorisation_envoi_rapport == '1') {
				if ($this->autorisation_frequence_rapport == 1) {
					$titre = $this->site->getAffaire()." - $designation - Echec Critique : Transfert Ftp";
					$liste_messages = array();
					$liste_messages[] = "Echec des transfert Ftp depuis ".$this->site->getIntitule();
					$liste_messages[] = "1) Détail";
					$liste_messages[] = "Date : $date_message<br />";
					$liste_messages[] = "[ERROR];Transfert Error;$adresseIp;Aucun fichier transféré depuis plus de $nb_max_jours_sans_transfert jours depuis l'équipement ".$this->site->getIntitule()." - ".$designation;
					$liste_messages[] = "2) Informations";
					$liste_messages[] = "Risque de surchage sur l'équipement. Veuillez analyser svp";
					$this->email->send($this->email_admin, $titre, 'Mail', $liste_messages);
				}
			}
			return(1);
		}
	}
}

protected function inverseDate($datel) {
	$date_retour = "";
	$pattern = '/^(.+?)[\/-](.+?)[\/-](.+?)$/';
	if (preg_match($pattern, $datel, $dater)) {
		$date_retour = $dater[3].'/'.$dater[2].'/'.$dater[1];
	}
	return($date_retour);
}

public function forcageSuppressionFtpVides() {
    $date_tmp = new \Datetime();
    $date_message = $date_tmp->format('d-m-Y H:i');
    $flagArretServeur = '/tmp/.flagSymfonyArretServeur';
    $flagArretServiceTransfertFtp = '/tmp/.flagArretServiceTransfertFtp';
    $this->email_admin = $this->configuration->SqlGetParam($this->dbh, 'admin_email');
    if ( (! $this->dossier_local) || ( ! is_dir($this->dossier_local)) ) {
        $this->log->setLog("[ERROR] [CRITIQUE];Configuration Error;;Le paramètre 'dossier_fichiers_originaux' ou le dossier pointé  n'existe pas. Transfert des fichiers non possible.", $this->fichier_log);
        $liste_messages = array();
        $titre = $this->site->getAffaire()." Echec Critique";
        $liste_messages[] = "[ERROR] Transfert de fichiers impossible sur le site ".$this->site->getAffaire();
        $liste_messages[] = "Date : $date_message<br />";
        $liste_messages[] = "Le paramètre 'dossier_fichiers_originaux' ou le dossier pointé n'existe pas en base de donnée.<br />";
        $liste_messages[] = "Veuillez le créer pour définir le dossier des fichiers à convertir en binaire";
        $this->email->send($this->email_admin, $titre, 'Mail', $liste_messages);
        return(1);
    }
    if ( (! $this->dossier_tmpftp) || (! is_dir($this->dossier_tmpftp)) ) { 
        $this->log->setLog("[ERROR] [CRITIQUE];Configuration Error;;Le paramètre 'dossier_fichiers_tmpftp' ou le dossier pointé n'existe pas. Transfert des fichiers non possible.", $this->fichier_log);
        $liste_messages = array();
        $titre = $this->site->getAffaire()." Echec Critique";
        $liste_messages[] = "[ERROR] Transfert de fichiers impossible sur le site ".$this->site->getAffaire();
        $liste_messages[] = "Date : $date_message<br />";
        $liste_messages[] = "Le paramètre 'dossier_fichiers_tmpftp' ou le dossier pointé  n'existe pas en base de donnée.<br />";
        $liste_messages[] = "Veuillez le créer pour définir le dossier de destination des fichiers transférés par ftp";
        $this->email->send($this->email_admin, $titre, 'Mail', $liste_messages);
        return(1);
    }

    // Vérification : Si un flag de téléchargement Ftp existe -> Pas de téléchargement
    $flagFtp = "/tmp/.flagSymfonyDownloadFtp";
	$nb_total_fichiers_supprimes = 0;
    if (file_exists($flagFtp)) {
        $this->log->setLog("[INFO];".$this->site->getAffaire().";Le téléchargement des fichiers par ftp est déjà en cours d'execution", $this->fichier_log);
    } else {
        // Création du flag importationFtp pour bloquer le téléchargement par d'autres programmes
        $commande = "touch $flagFtp";
        exec($commande);
        $commande = "chmod 666 $flagFtp";
        exec($commande);
        //$commande = "chown wwwrun $flagFtp";
        //exec($commande);
        // Log du début de récupération des fichiers distant
        $this->log->setLog("[INFO];".$this->site->getAffaire().";Début de forcage de suppression des fichiers ftp vides", $this->fichier_log);
        foreach ($this->localisations as $localisation) {
            $designation = $localisation->getDesignation();
            $adresseIp = $localisation->getAdresseIp();
            $login = $localisation->getLoginFtp();
            $password = $localisation->getPasswordFtp();

            $id_connexion_ftp = $this->connexion($adresseIp, $login, $password, $designation);
            // Fin de fonction si echec de connexion
            if ($id_connexion_ftp == null) {
                // Passage à l'équipement suivant
                continue;
            }
            // Récupération du contenu du dossier
            // En cas d'echec : log + passage à l'équipement suivant
            $liste_fichiers_distants = ftp_nlist($id_connexion_ftp, '.');
            $nb_fichiers_distants = count($liste_fichiers_distants);
            if ($nb_fichiers_distants != 0) {
                $this->log->setLog("[INFO];$adresseIp;Nombre de fichiers analysés;$nb_fichiers_distants",$this->fichier_log);
                $nb_fichiers_vide_supprimes = 0;
                foreach ($liste_fichiers_distants as $nom_fichier) {
                    $nb_fichiers_vide_supprimes += $this->suppressionFtpVide($id_connexion_ftp, $nom_fichier, $adresseIp,$designation);
                }
                if ($nb_fichiers_vide_supprimes != 0) {
                    $nb_total_fichiers_supprimes += $nb_fichiers_vide_supprimes;
                    $titre = "Forcage de la suppression des fichiers ftp vide sur ".$this->site->getAffaire()." - ".$this->site->getIntitule()." - Automate : $designation";
					$liste_messages = array();
                    $liste_messages[] = $this->site->getAffaire()." - ".$this->site->getIntitule().": Forcage de la suppression des fichiers ftp vide sur $designation ($adresseIp)";
                    $liste_messages[] = "Date : $date_message<br />";
                    $liste_messages[] = "Nombre de fichiers vides supprimés: $nb_fichiers_vide_supprimes";
                    $this->email->send($this->email_admin,$titre,'Mail',$liste_messages);
                }
            }
            $this->log->setLog("[INFO];$adresseIp;Nombre de fichiers supprimés;$nb_total_fichiers_supprimes",$this->fichier_log);
            // Déconnexion
            $this->deconnexion($id_connexion_ftp, $adresseIp);
        }
        $this->log->setLog("[INFO];".$this->site->getAffaire().";Nombre de fichiers supprimés;$nb_total_fichiers_supprimes", $this->fichier_log);
        $this->log->setLog("[INFO];".$this->site->getAffaire().";Fin du forcage de suppression des fichiers ftp vides.", $this->fichier_log);
        # Libération du flag
        $commande = "rm $flagFtp";
        exec($commande);
    }
	return ($nb_total_fichiers_supprimes);
}

// Suppression des fichiers ftp dont la taille est égale à 0
protected function suppressionFtpVide($connexion_id, $nom_fichier_distant, $adresseIp, $designation) {
    // indicateur qui renseigne si un fichier vide a été supprimé
    $indicateur_suppression = 0;
    $nom_fichier_local = $this->dossier_tmpftp.'/'.$nom_fichier_distant;
    $nom_fichier_origine = $this->dossier_local.'/'.$nom_fichier_distant;
    if (ftp_get($connexion_id, $nom_fichier_local, $nom_fichier_distant, FTP_BINARY)) {
        $taille_fichier_distant = ftp_size($connexion_id, $nom_fichier_distant);
        $taille_fichier_local = filesize($nom_fichier_local);
        if (($taille_fichier_distant == 0) && ($taille_fichier_local == 0)) {
            // Suppression sur le serveur ftp distant
            if (ftp_delete($connexion_id, $nom_fichier_distant)) {
                // Suppression du fichier local
                if (unlink($nom_fichier_local)) {
                    $this->log->setLog("[INFO];$adresseIp;Fichier $nom_fichier_distant de taille 0 sur $designation correctement supprimé", $this->fichier_log);
                    $indicateur_suppression = 1;
                }
            }
        }
    }
    return($indicateur_suppression);

}


}
