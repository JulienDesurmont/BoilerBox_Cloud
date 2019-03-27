<?php
// src/Ipc/ProgBundle/Command/CreationEtatCommand.php

namespace Ipc\ProgBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Ipc\ProgBundle\Entity\Etat;

class CreationEtatCommand extends ContainerAwareCommand {

protected function configure() {
	// Nom par lequel la commande est appelée dans une commande php
	$this
		->setName('creation:rapportsEtat')
		->setDescription('Création des Etats');
}

protected function execute(InputInterface $input, OutputInterface $output) {
	$service_etat = $this->getContainer()->get('ipc_prog.etats');
	// Récupération des états présents en base de données
	$etat = new Etat();
	$entities_Etat = $this->getContainer()->get('ipc_prog.connectbd')->getManager()->getRepository('IpcProgBundle:Etat')->findAll();
	$tabEtat = array();
	foreach ($entities_Etat as $entityEtat) {
		// Si le champ active est à 0 c'est que la recherche ne doit pas être faite
		$active = $entityEtat->getActive();
		if ($active == 1) {
			//echo "Recherche de l'etat ".$entityEtat->getIntitule();
			$retourAnalyse = $service_etat->EtatAnalyseDeMarche('creation',$entityEtat);
		}
	}
	return(0);
}

}
