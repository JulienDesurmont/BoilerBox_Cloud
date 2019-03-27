<?php
// src/Ipc/ProgBundle/Command/ImportBinCommand.php

//      Commande permettant de lancer le service ipc_prog.importbin par ligne de commande

namespace Ipc\ProgBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Ipc\ProgBundle\Entity\Fichier;
use Ipc\ProgBundle\Entity\Module;
use Ipc\ProgBundle\Entity\Site;
use Ipc\ProgBundle\Entity\Localisation;

class ImportBinCommand extends ContainerAwareCommand {

protected function configure() {
	$this
		->setName('import:bin')
		->setDescription('Importation de fichiers binaire');
}

// La commande   - scan le répertoire des dossiers binaires
// - récupère le service d'insertion des donnés des fichiers binaires
protected function execute(InputInterface $input, OutputInterface $output) {
	// Durée limite d'execution des scripts après laquelle une libération des ressources est nécessaire
	$limiteExecution = 20;
	// Entité temporaire [Fichier] pour récupérer le dossier des fichiers binaires
	$fichier = new Fichier(); 
	$dossier_binaire = $fichier->getBinaryDir();
	$flagArretServeur = '/tmp/.flagSymfonyArretServeur';
	$flagArretServiceImportBin = '/tmp/.flagArretServiceImportBin';
	$fichier = exec("ls $dossier_binaire", $liste_fichiers, $retour);
	$error = false;
	if ($retour == 0) {
		$nbfichiers = count($liste_fichiers);
		if ($nbfichiers != 0) {
			$entity_module = new Module();
			$connexion = $this->getContainer()->get('ipc_prog.connectbd');
			$dbh = $connexion->getDbh();
			$tab_modules_sql = $entity_module->SqlGetModules($dbh);
			$tab_modules = array();
			foreach ($tab_modules_sql as $module) {
				$keyTab = $module['categorie'].$this->fillNumber($module['numero_module']).$this->fillNumber($module['numero_message']).$this->fillNumber($module['genre_id']).$this->fillNumber($module['mode_id']);
				$tab_modules[$keyTab] = $module['id'];
			}
			// Création du tableau des liens modules_id localisations_id
			$site_courant = new Site();
			// Récupération de l'id du site courant
			$id_site_courant = $site_courant->SqlGetIdCourant($dbh);
			// Récupération des localisations du site courant
			$localisation = new Localisation();
			$tab_localisations = $this->getContainer()->get('ipc_prog.connectbd')->getManager()->getRepository('IpcProgBundle:Localisation')->SqlGetLocalisation($dbh, $id_site_courant);
			$tab_des_localisations = array();
			$liste_id_localisations_courantes = '';
			foreach ($tab_localisations as $key => $localisation) {
				$tab_des_localisations[$localisation['id']] = $localisation['designation'];
				$liste_id_localisations_courantes .= $localisation['id'].',';
			}
			$liste_id_localisations_courantes = substr($liste_id_localisations_courantes, 0, -1);
			$service_importbin = $this->getContainer()->get('ipc_prog.importbin');
			// Compteur permettant de vérifier le temps d'importation des fichiers après le 1er fichier
			$nbFichiersImportes = 0;
			foreach ($liste_fichiers as $fic) {
				// Si le flag d'arret du serveur est présent : Arrêt du traitement
				if (file_exists($flagArretServeur) || file_exists($flagArretServiceImportBin)) {
					$this->setLog("Arrêt du serveur ou du service demandé : Arrêt du script d'importation en base de données");
					break;
				}
				$timestart = microtime(true);
				// Permet d'indiquer le chemin complet du fichier
				$fich = $dossier_binaire.$fic; 
				if (is_file($fich)) {
					// Importation du fichier : Retourne -1 si le fichier est déjà en cours d'importation
					$retour = $service_importbin->importation($fic, $tab_modules, $tab_des_localisations);
					if ($retour == -1) {
						$error = true;
						break;
					}
				}
				if ($nbFichiersImportes > 0) {
					// Récupération de la date de fin
					$timeend = microtime(true);
					$time = $timeend-$timestart;	
					// Calcul du temps d'éxecution
					$page_load_time = number_format($time);
					// Si le traitement a durée plus de X secondes, arrêt du programme pour libération des ressources
					if ($page_load_time > $limiteExecution) {
						$this->setLog("Le traitement du fichier [ $fic ] a mis plus de $limiteExecution secondes ($page_load_time secondes). Arrêt du traitement");
						break;
					}
				}
				$nbFichiersImportes ++;
			}
		}
	} else {
		throw $this->createNotFoundException('Impossible d\'ouvrir le dossier [ '.$dossier_binaire.' ]');
	}
	if ($error == false) {
		$output->writeln(0);
	} else {
		$output->writeln(1);
	}
}

protected function fillNumber($num) {
	$pattern = '/^(.)$/';
	if (preg_match($pattern, $num)) {
		$num = "0".$num;
	}
	return($num);
}


// Fonction de log d'un message
protected function setLog($message) {
$fichierLog = __DIR__.'/../../../../web/logs/system.log';
date_default_timezone_set('Europe/Paris');
$message = date("Y-m-d H:i:s;").$message."\n";
$fp = fopen($fichierLog, "a");
fwrite($fp, $message);
fclose($fp);
}

}
