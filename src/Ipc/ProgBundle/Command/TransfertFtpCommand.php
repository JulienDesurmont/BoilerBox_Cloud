<?php
// src/Ipc/ProgBundle/Command/TransfertFtpCommand.php

namespace Ipc\ProgBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Ipc\ProgBundle\Entity\Fichier;

class TransfertFtpCommand extends ContainerAwareCommand {

protected function configure() {
	$this
		->setName('transfert:ftp')
		->setDescription('Transfert Ftp des fichiers distants');
}

protected function execute(InputInterface $input, OutputInterface $output) {
	$service_transfertFtp = $this->getContainer()->get('ipc_prog.transfertFtp');
	$service_transfertFtp->importation();
	return(0);
}

}
