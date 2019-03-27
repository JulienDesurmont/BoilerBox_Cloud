<?php
// src/Ipc/ProgBundle/Command/CreationRapportSecurite.php
//      Commande permettant de lancer le service ipc_prog.rapports par ligne de commande
namespace Ipc\ProgBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreationRapportSecuriteCommand extends ContainerAwareCommand {

protected function configure() {
	$this
		->setName('creation:rapportsSecurite')
		->setDescription('Création du rapport de sécurité');
}

protected function execute(InputInterface $input, OutputInterface $output) {
	$service_creationRapport = $this->getContainer()->get('ipc_prog.rapports');
	$service_creationRapport->rapportSecurite();
}

}
