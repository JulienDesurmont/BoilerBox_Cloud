<?php
//src/Ipc/ProgBundle/Services/ServiceLog.php

namespace Ipc\ProgBundle\Services;

use Ipc\ProgBundle\Entity\Configuration;

class ServiceLog {

public function __construct($doctrine, $connexion) {
	$this->doctrine	= $doctrine;
	$dbh = $connexion->getDbh();
	$configuration = new Configuration();
	date_default_timezone_set($configuration->SqlGetParam($dbh, 'timezone'));
}

protected function getLogDir() {
	return __DIR__.'/../../../../web/logs/';
}

// Fonction qui lit un fichier de log à la recherche du texte passé en paramètre
public function recherchetexte($fichier_log, $texte) {
	$ficlog = $this->getLogDir().$fichier_log;
	// Ouverture du fichier de log
	$fp = fopen($ficlog,'r');
	$contents = fread($fp, filesize($ficlog));
	fclose($fp);
}

public function setLog($message, $nomFichier) {
	$ficlog = $this->getLogDir().$nomFichier;
	$message = date("d/m/Y H:i:s;").$message."\n";
	$fp = fopen($ficlog, "a");
	fwrite($fp, $message);
	fclose($fp);
}

public function logErreur($typeErreur, $tabarg, $nomFichier) {
	$repository_erreur = $this->doctrine->getManager()->getRepository('IpcProgBundle:Erreur');
	switch ($typeErreur) {
	case 'site' :
		// On log le message d'erreur : Site incorrect
		$erreur = $repository_erreur->findOneByCode('S1NR');
		$patterns = array();
		$patterns[0] = '/\?s/';
		$replacements = array();
		$replacements[0] = $tabarg['affaire'];
		$texte = $erreur->getTexte($patterns, $replacements);
		$this->setLog("BD;Erreur S1NR;".$texte, $nomFichier);
		break;
	case 'localisation':
		// On log le message d'erreur Localisation incorrecte
		$erreur = $repository_erreur->findOneByCode('L1NR');
		$patterns = array();
		$patterns[0] = '/\?l/';
		$patterns[1] = '/\?a/';
		$replacements = array();
		$replacements[0] = $tabarg['nuLocalisation'];
		$replacements[1] = $tabarg['affaire'];
		$texte = $erreur->getTexte($patterns, $replacements);
		$this->setLog("BD;Erreur L1NR;".$texte, $nomFichier);
		break;
	case 'date' :
		// On log le message d'erreur : Format de date incorrect
		$erreur = $repository_erreur->findOneByCode('F4WT');
		$patterns = array();
		$patterns[0] = '/\?h/';
		$pattern[1] = '/\?f/';
		$replacements = array();
		$replacements[0] = $tabarg['dateFichier'];
		$replacements[1] = $fichier->getNom();
		$texte = $erreur->getTexte($patterns, $replacements);
		$this->setLog("BD;Erreur F4WT;".$texte, $nomFichier);
		break;
	case 'module' :
		// On log le Message d'erreur Module non trouvé
		$erreur = $repository_erreur->findOneByCode('M1NR');
		$patterns = array();
		$patterns[0] = '/\?c/';
		$patterns[1] = '/\?m/';
		$patterns[2] = '/\?me/';
		$patterns[3] = '/\?g/';
		$replacements = array();
		$replacements[0] = $tabarg["categorie"];
		$replacements[1] = $tabarg["module"];
		$replacements[2] = $tabarg["message"];
		$replacements[3] = $tabarg["genre"];
		$texte = $erreur->getTexte($patterns, $replacements);
		$this->setLog("BD;Erreur M1NR;".$texte, $nomFichier);
		break;
	case 'sommeDonnees' :
		// On log le message d'erreur Somme des données incorrect
		$erreur = $repository_erreur->findOneByCode('D2WS');
		$patterns = array();
		$replacements = array();
		$texte = $erreur->getTexte($patterns, $replacements);
		$this->setLog("Erreur D2WS;".$texte, $nomFichier);
		break;
	case 'nomFichier' :
		// On log le message d'erreur : Nom du fichier incorrect
		$erreur = $repository_erreur->findOneByCode('F2WN');
		$patterns = array();
		$patterns[0] = '/\?f/';
		$replacements = array();
		$replacements[0] = $tabarg['nomFichier'];
		$texte = $erreur->getTexte($patterns, $replacements);
		$this->setLog("BD;Erreur F2WN;".$texte, $nomFichier);
		break;
	case 'fichierTraite' :
		// On log le message d'erreur : Fichier déjà traité
		$erreur = $repository_erreur->findOneByCode('F3AR');
		$patterns = array();
		$patterns[0] = '/\?f/';
		$patterns[1] = '/\?t/';
		$replacements = array();
		$replacements[0] = $tabarg['nomFichier'];
		$replacements[1] = $tabarg['horodatage'];
		$texte = $erreur->getTexte($patterns, $replacements);
		$this->setLog("BD;Erreur F3AR;".$texte, $nomFichier);
		break;
	case 'donneeDupliquee' :
		// On log le message d'erreur : La donnée est dupliquée en base de donnée
		$erreur = $repository_erreur->findOneByCode('D1AR');
		$patterns = array();
		// horodatage format string
		$patterns[0] = '/\?h/';
		// cycle
		$patterns[1] = '/\?c/';
		// quadruplet identifiant le module
		$patterns[2] = '/\?q/';	
		$patterns[3] = '/\?f/';
		$replacements = array();
		$replacements[0] = $tabarg['horodatage'];
		$replacements[1] = $tabarg['cycle'];
		$replacements[2] = $tabarg['quadruplet'];
		$replacements[3] = $tabarg['nomFichier'];
		$texte = $erreur->getTexte($patterns, $replacements);
		$this->setLog("BD;Erreur D1AR;".$texte, $nomFichier);
		break;
	case 'moduleDonneeIncorrect' :
		// On log le message d'erreur : La donnée est incorrecte (la quadruplet n'existe pas en base de donnée)
		$erreur = $repository_erreur->findOneByCode('D3IQ');
		$patterns = array();
		$patterns[0] = '/\?h/'; 
		$patterns[1] = '/\?c/';
		$patterns[2] = '/\?q/'; 
		$patterns[3] = '/\?f/';
		$replacements = array();
		$replacements[0] = $tabarg['horodatage'];
		$replacements[1] = $tabarg['cycle'];
		$replacements[2] = $tabarg['quadruplet'];
		$replacements[3] = $tabarg['nomFichier'];
		$texte = $erreur->getTexte($patterns, $replacements);
		$this->setLog("BD;Erreur D3IQ;".$texte, $nomFichier);
		break;
	}
	return(0);
}

}
