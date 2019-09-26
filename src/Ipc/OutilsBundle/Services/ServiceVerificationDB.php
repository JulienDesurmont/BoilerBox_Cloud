<?php
// src/Ipc/OutilBundle/Services/ServiceVerificationDB.php

namespace Ipc\OutilsBundle\Services;

use Ipc\ProgBundle\Entity\Donnee;
use Ipc\ProgBundle\Entity\Configuration;

// Service qui vérifie le nombre de données dans la BD indiquée sur les X derniers jours
class ServiceVerificationDB {

private $srv_doctrine;
private $dbh;
private $em;
private $database_name;
private $tab_associatif_configuration_cloud;
private $tab_sites_suivis;
private $nb_jours_cloud;
private $srv_logs;
private $nb_jours;
private $srv_mailing;



    public function __construct($srv_connexion, $srv_configuration_cloud, $srv_logs, $srv_mailing) {
        $this->em       							= $srv_connexion->getManager();
        $this->dbh      							= $srv_connexion->getDbh();
        $this->database_name 						= $srv_connexion->getBase();
		$this->tab_associatif_configuration_cloud 	= $srv_configuration_cloud->recuperationVariables();
		$this->tab_sites_suivis						= $srv_configuration_cloud->recuperationSitesSuivis();
		$this->nb_jours_cloud						= $this->tab_associatif_configuration_cloud['nb_jours_nb_db_donnees'];
		$this->srv_logs								= $srv_logs;
		$this->srv_mailing							= $srv_mailing;
    }

	public function getNbJours() {
		return $this->nb_jours_cloud;
	}



    // retourne l'entité du paramètre nombre de jours pour la recherche du nombre de données en DB
    // Créée l'entité si elle n'existe pas avec comme valeur par défaut : 3
    public function getEntityParamNbJours() {
		$ent_configuration = null;
        $ent_configuration = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('nb_jours_nb_db_donnees');
        return($ent_configuration);
    }

	public function getNbDBDonnees() {
		$ent_nb_db_donnee = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('nb_db_donnees');
		if ($ent_nb_db_donnee) {
			return $ent_nb_db_donnee->getValeur();
		} else {
			return -1;
		}
	}


    // Inscrit le  nombre de données (de la table t_donnee) des X derniers jours de la DB en cours d'utilisation dans la table t_configuration dans le paramètre de configuration 'Nb_DB_Donnees'
    public function setSqlNbDBDonnees($nb_jours=null) {
		$date_du_jour = date('d/m/Y');
        $nb_db_donnees = $this->getSqlNbDBDonnees($nb_jours, $this->database_name);
        $ent_configuration = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('nb_db_donnees');
        if ($ent_configuration) {
            $ent_configuration->setValeur($date_du_jour.';'.$nb_db_donnees);
            $ent_configuration->SqlUpdateValue($this->dbh);
        } else {
            $ent_configuration = new configuration();
            $ent_configuration->setParametre('nb_db_donnees');
            $ent_configuration->setDesignation('Nombre de données en table t_donnee sur les X derniers jours');
            $ent_configuration->setValeur($date_du_jour.';'.$nb_db_donnees);
            $ent_configuration->setParametreAdmin(true);
            $ent_configuration->SqlInsert($this->dbh);
        }
		$fichier_log = 'parametresIpc.log';
		$message_log = "Recherche du $date_du_jour;Base de donnée [".$this->database_name."];Sur les ".$this->nb_jours." derniers jours;Nombre de données dans la table t_donnee=$nb_db_donnees";
		$this->srv_logs->setLog($message_log, $fichier_log);
    }


    // Compte le nombre de données (de la table t_donnee) des X derniers jours de la DB en cours d'utilisation
    // Le X est donné par la variable de configuration [nb_jours_nb_db_donnees]
    public function getSqlNbDBDonnees($nb_jours=null, $database=null) {
        if ($nb_jours == null) {
            $ent_configuration = $this->getEntityParamNbJours();
            $nb_jours = $ent_configuration->getValeur();
        }
		$this->nb_jours = $nb_jours;
        if ($database == null) {
            $database = $this->database_name;
        }
        $rep_nb_db_donnees = null;
        $rep_nb_db_donnees = $this->em->getRepository('IpcProgBundle:Donnee')->myCountDonneesFromDB($this->dbh, $database, $nb_jours);
        return($rep_nb_db_donnees);
    }



    // Compare le nombre de données entre la table t_donnee de la base passée en paramètre et la valeur du paramètre de cette meêm base
    // Retourne 0 si le nombre de données est identique entre les deux bases
    // Retourne -1 si une erreur non prévue est levéé
    public function compareAllDBDonnees($dbh) {
		$message_retour = '';
		foreach($this->tab_sites_suivis as $database_name){
			$liste_contenus = [];
			$error = false;
        	$ret_message = "Base : $database_name<br />";
			$nb_jours_de_recherche = $this->em->getRepository('IpcProgBundle:Configuration')->myGetSqlValueOf($this->dbh, $database_name, 'nb_jours_nb_db_donnees');
			$nb_donnees_distant = $this->em->getRepository('IpcProgBundle:Configuration')->myGetSqlValueOf($this->dbh, $database_name, 'nb_db_donnees');
			$nb_donnees_cloud = $this->em->getRepository('IpcProgBundle:Donnee')->myCountDonneesFromDB($this->dbh, $database_name, $nb_jours_de_recherche);

    		$pattern = '/^(.+?);(.+?)$/';
    		if(preg_match($pattern, $nb_donnees_distant, $tab_nb_db_donnees_distant)) {
        		//Vérification de la date avec la date du jour./
        		$date_du_jour = date('d/m/Y');
        		if ($date_du_jour != $tab_nb_db_donnees_distant[1]) {
        		    $ret_message .= "La date de récupération du comptage sur le site distant ($tab_nb_db_donnees_distant[1]) n'est pas la date du jour.<br /><br />";
        		    $ret_message .= "Veuillez vérifier que la sychronisation n'est pas stoppée";
					$liste_contenus =
					$error = true;
        		} else if ($nb_donnees_cloud != $tab_nb_db_donnees_distant[2]) {
        		    $ret_message .= "Le nombre de données n'est pas égale entre le cloud et le site distant (Cloud : $nb_donnees_cloud - Distant : $tab_nb_db_donnees_distant[2])<br /><br />";
        		    $ret_message .= "Veuillez vérifier que la sychronisation n'est pas stoppée";
					$error = true;
        		} else {
					$ret_message .= "Le nombre de message sur les $nb_jours_de_recherche derniers jours est identique : $nb_donnees_cloud";
				}
    		}

			if ($error == true) {
				$this->srv_mailing->sendAssistance('Désynchronisation du Cloud', 'Désynchronisation du Cloud', $reite_courant-_message);
				// Envoie d'un mail a Assistance
			}	
			$message_retour .= $ret_message."<br />*****<br />";
		}
		return($message_retour);
    }


	public function compareNbDBDonnees() {
		$ent_site_courant = $this->em->getRepository('IpcProgBundle:Site')->myFindCourant();
		if (! $this->getEntityParamNbJours()) {
			$ret_message = "Le site distant doit être en version 2.8.0 minimum pour effectuer la comparaison.<br /><br />";
			$ret_message .= "Veuillez mettre à jour le site ".$ent_site_courant->getAffaire();
			return($ret_message);
		}
		// Paramètre du site distant
		$nb_jours_de_recherche = $this->getEntityParamNbJours()->getValeur();

		// Récupération du nombre de données sur le CLOUD
		$nb_donnees_cloud = $this->getSqlNbDBDonnees();

		// Récupération du nombre de données sur le site distant (Vérification que la date de récupération des données est à jour)
    	$nb_donnees_distant = $this->getNbDBDonnees();


		if ($nb_donnees_distant == -1) {
        	$ret_message = "(cloud) $nb_donnees_cloud != (distant) $nb_donnees_distant<br/><br />";
        	$ret_message .= "La crontab du site distant doit rechercher les valeurs tous les jours.<br /><br />";
        	$ret_message .= "Veuillez vérifier la crontab du site ".$ent_site_courant->getAffaire();
        	return($ret_message);
    	}

		$pattern = '/^(.+?);(.+?)$/';
    	if(preg_match($pattern, $nb_donnees_distant, $tab_nb_db_donnees_distant)) {
        	//Vérification de la date avec la date du jour./
        	$date_du_jour = date('d/m/Y');
        	if ($date_du_jour != $tab_nb_db_donnees_distant[1]) {
        	    $ret_message = "La date de récupération du comptage sur le site distant ($tab_nb_db_donnees_distant[1]) n'est pas la date du jour.<br /><br />";
        	    $ret_message .= "Veuillez vérifier que la sychronisation n'est pas stoppée";
        	    return($ret_message);
        	}
        	if ($nb_donnees_cloud != $tab_nb_db_donnees_distant[2]) {
       	     	$ret_message = "Le nombre de données n'est pas égale entre le cloud et le site distant (Cloud : $nb_donnees_cloud - Distant : $tab_nb_db_donnees_distant[2])<br /><br />";
       	     	$ret_message .= "Veuillez vérifier que la sychronisation n'est pas stoppée";
				return($ret_message);
        	}
		}

	    $s = '';
    	if ($nb_donnees_cloud > 1) {
    	    $s = 's';
    	}
    	// Récupération de la valeur X (nombre de jours de recherche)
    	$s2 = '';
    	if ($nb_jours_de_recherche > 1) {
    	    $s2 = 's';
    	}
    	$ret_message = "Site : ".$ent_site_courant->getAffaire()."<br /><br />";
    	$ret_message .= "Le nombre de données dans la table [ t_donnee ] est identique sur le cloud et sur le site distant pour les $nb_jours_de_recherche dernier$s2 jour$s2 : $nb_donnees_cloud";
    	return($ret_message);
	}


	public function calculNbDBDonnees() {
		$ent_site_courant = $this->em->getRepository('IpcProgBundle:Site')->myFindCourant();
		// Paramètre du CLOUD
		$nb_jours_de_recherche = $this->getNbJours();
		// Récupération du nombre de données sur la base du CLOUD
		$nb_donnees = $this->getSqlNbDBDonnees($nb_jours_de_recherche);

		$s = '';
		if ($nb_donnees > 1) {
			$s = 's';
		}
    	// Récupération de la valeur X (nombre de jours de recherche)
    	$s2 = '';
    	if ($nb_jours_de_recherche > 1) {
    	    $s2 = 's';
    	}
    	$ret_message = "Site : ".$ent_site_courant->getAffaire()."<br /><br />";
    	$ret_message .= "$nb_donnees donnée$s trouvée$s dans la table [ t_donnee ] sur les ";
    	$ret_message .= "$nb_jours_de_recherche dernier$s2 jour$s2";
		return($ret_message);	
	}
}

