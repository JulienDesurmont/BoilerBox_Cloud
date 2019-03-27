<?php
// src/Ipc/ProgBundle/Command/KillQueryCommand.php

//      Commande permettant de lancer le service ipc_prog.importbin par ligne de commande

namespace Ipc\ProgBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class KillQueryCommand extends ContainerAwareCommand {

protected function configure() {
	$this
		->setName('kill:query') 
		->setDescription('Kill des requêtes sql')
		->addArgument('queryId', InputArgument::OPTIONAL,'Requête id ?');
}

// La commande   - scan le répertoire des dossiers binaires
// - récupère le service d'insertion des donnés des fichiers binaires
protected function execute(InputInterface $input, OutputInterface $output) {
	$queryId = $input->getArgument('queryId');
	exec("echo 'kill query $queryId' | mysql -ucargo -padm5667");
	return(0);
}

}
