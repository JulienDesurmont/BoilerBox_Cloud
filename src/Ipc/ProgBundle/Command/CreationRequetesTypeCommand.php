<?php
// src/Ipc/ProgBundle/Command/CreationRequetesTypeCommand.php

namespace Ipc\ProgBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreationRequetesTypeCommand extends ContainerAwareCommand {

protected function configure() {
	$this
		->setName('creation:requetesType')
		->setDescription('Création des Requêtes Type');
}

// La commande   - scan le répertoire des dossiers binaires
//- récupère le service d'insertion des donnés des fichiers binaires
protected function execute(InputInterface $input, OutputInterface $output) {
	// Création des fichiers Requêtes Type
	$service_requete = $this->getContainer()->get('ipc_prog.requetesType');
	$service_requete->index();
	return(0);
}

}
