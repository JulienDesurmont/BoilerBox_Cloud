<?php
// src/Ipc/OutilsBundle/Command/VerificationDBCommand.php

namespace Ipc\OutilsBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Ipc\ProgBundle\Entity\Fichier;


class VerificationDBCommand extends ContainerAwareCommand {
	protected function configure() {
		$this	->setName('verificationDB:donnee')
				->setDescription('Vérification du nombre de données dans les tables t_donnees');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$srv_verificationDB = $this->getcontainer()->get('ipc_outil.getNbDBDonnees');
		$srv_verificationDB->setSqlNbDBDonnees();	
	}
}
