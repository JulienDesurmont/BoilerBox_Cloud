<?php
// src/Ipc/ProgBundle/Command/RecuperationModbus.php

//      Commande permettant de lancer le service ipc_supervision_get_infosModbus par ligne de commande

namespace Ipc\ProgBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Ipc\ProgBundle\Entity\Site;

class RecuperationModbusCommand extends ContainerAwareCommand {
private $em;

// Configuration du nom par lequel la commande est appelée dans une commande php
protected function configure() {
	$this
		->setName('modbus:get')
		->setDescription('Importation des données modbus');
}


private function readNewModbus($entity_localisation) {
	// 1 Recherche des mots à récupérer en modbus
	// 2 Récupération du service permettant la lecture modbus
	// 3 Lecture des données modbus
	// 4 Enregistrement des valeurs ModBus
	//1
	$entity_configModBus = $this->em->getRepository('IpcConfigurationBundle:ConfigModbus')->findOneByLocalisation($entity_localisation);
	if ($entity_configModBus != null) {
		//2
		$service_modbus = $this->getContainer()->get('ipc_prog.modbus');
		//3
		$new_entity_ConfigModBus = $service_modbus->readConfigModbus($entity_configModBus);
		if (gettype($new_entity_ConfigModBus) == 'object') {
			$entity_configModBus = $new_entity_ConfigModBus;
		} else {
			$entity_configModBus->setMessage("Aucune donnée modbus récupérée pour l'Automate ".$entity_localisation->getDesignation()." (".$entity_localisation->getAdresseIp()." )");
		}
	}
	//4
	$this->em->flush();
	return(0);
}


// Fonction de log d'un message
protected function setLog($message) {
	$fichierLog = __DIR__.'/../../../../web/logs/modbus.log';
	date_default_timezone_set('Europe/Paris');
	$message = date("Y-m-d H:i:s;").$message."\n";
	$fp	= fopen($fichierLog,"a");
	fwrite($fp,$message);
	fclose($fp);
}


protected function execute(InputInterface $input, OutputInterface $output) {
	$this->setLog('Début de la commande modbus');
	// 1 Récupération des valeurs modbus pour chaque localisation ayant sa variable 'live_automate_x' définie
	$this->em = $this->getContainer()->get('ipc_prog.connectbd')->getManager();
	$service_transfertFtp = $this->getContainer()->get('ipc_prog.transfertFtp');
	$connexion = $this->getContainer()->get('ipc_prog.connectbd');
	$dbh = $connexion->getDbh();
	$tmp_site = new Site();
	$site_id = $tmp_site->SqlGetIdCourant($dbh);
	$site = $this->em->getRepository('IpcProgBundle:site')->find($site_id);
	$entities_localisation = $this->em->getRepository('IpcProgBundle:Localisation')->findBySite($site_id);
	// 1
	$tab_des_ModBus = array();
	foreach ($entities_localisation as $entity_localisation) {
		$this->setLog('Récupération modbus pour la localisation '.$entity_localisation->getDesignation().' ['.$entity_localisation->getAdresseIp().']');
		$new_EntityConfigModbus = $this->readNewModbus($entity_localisation);
	}
	$connexion->disconnect();
	$this->setLog('Fin de la commande modbus');
}

}
