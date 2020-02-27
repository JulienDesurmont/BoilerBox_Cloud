<?php
//src/Ipc/ProgBundle/Services/GestionLog/ServiceGestionLog.php
namespace Ipc\ProgBundle\Services\GestionLog;

use Ipc\ProgBundle\Entity\Configuration;

class ServiceGestionLog {

private $em;

public function __construct($srv_connexion) {
	$this->em = $srv_connexion->getManager();
	$configuration = new Configuration();

	$entity_timezone = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('timezone');
	if ($entity_timezone === null) {
		$configuration->setParametre('timezone');
        $configuration->setDesignation('Fuseau horaire');
        $configuration->setValeur('Europe/Paris');
        $configuration->setParametreAdmin(true);
		$configuration->setParametreTechnicien(false);
		$this->em->persist($configuration);
		$this->em->flush();
	}
	date_default_timezone_set($this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('timezone')->getValeur());
}

public function getLogDir() {
	return __DIR__.'/../../../../../web/logs/';
}

// Fonction qui lit un fichier de log à la recherche du texte passé en paramètre et retourne la dernière ligne
public function rechercheLastTexte($fichier_log, $texte) {
	$TexteRetour = null;
	$tab_texteRetour = array();
	$ficlog = $this->getLogDir().$fichier_log;
	// Le texte est composé d'une suite de mots séparés par des ;
	$liste_mots = explode(';',$texte);
	// Création de la ligne de commande
	$cmd = "grep --binary-files=text '";
	foreach ($liste_mots as $mot) {
		$cmd .= $mot.'.*';
	}
	// Suppression des deux derniers caractères (le dernier couple .*)
	$cmd = substr($cmd,0,-2);
	$cmd .= "' $ficlog";
	// Execution de la commande : recherche des lignes contenant le texte $texte : Récupération de la dernière ligne retournée par la commande
	$TexteRetour = exec($cmd);
	if ($TexteRetour) {
		$TexteRetour .= "\n\n";
	}
	return($TexteRetour);
}
              

// Fonction qui lit un fichier de log à la recherche du texte passé en paramètre -> Le texte peut être une suite de mot séparée par des ;
public function rechercheTexte($fichier_log, $texte, $option) {
	$TexteRetour = null;
	$tab_texteRetour = array();
	$ficlog = $this->getLogDir().$fichier_log;
	// Le texte est composé d'une suite de mots séparés par des ;
	$liste_mots = explode(';', $texte);
	// Création de la ligne de commande
	$cmd = "grep --binary-files=text '";
	if (substr($fichier_log, -4) === '.bz2') {
		$cmd = "zgrep '";	
	}
	foreach ($liste_mots as $mot) {
		$cmd .= $mot.'.*';
	}
	// Suppression des deux derniers caractères (le dernier couple .*)
	$cmd = substr($cmd, 0, -3);
	$cmd .= "' $ficlog";
	// Version 2 : Recherche des lignes par execution de la commande shell grep
	// Execution de la commande : recherche des lignes contenant le texte $texte
	$TexteRetour = exec("$cmd", $tab_texteRetour);
	if ($option == 'lastFicInfo') {
		//  -1 car commence à 0
		$nbligneslog = count($tab_texteRetour) - 1;
		$pattern='/\[INFO\];;Fin d\'importation du fichier/';
		$tab_logRetour = array();
		foreach ($tab_texteRetour as $key => $lignelog) {
			if (($key != $nbligneslog) && (preg_match($pattern,$lignelog))) {
				$tab_logRetour = array();
			} else {
				$tab_logRetour[] = $tab_texteRetour[$key];
			}
		}
		$TexteRetour = implode("\n", $tab_logRetour);
		if ($TexteRetour) {
			$TexteRetour .= "\n\n";
		}
	} else {
		// Concatenation des lignes du tableau retourné
		$TexteRetour = implode("\n", $tab_texteRetour);
		if ($TexteRetour) {
			$TexteRetour .= "\n\n";
		}
	}
	return($TexteRetour);
}

public function setLog($message, $nomFichier) {
	$ficlog = $this->getLogDir().$nomFichier;
	$message = date("d/m/Y;H:i:s;").$message."\n";
	$fp = fopen($ficlog,"a");
	fwrite($fp,$message);
	fclose($fp);
}

public function logErreur($typeErreur, $tabarg, $nomFichier) {
	$repository_erreur = $this->em->getRepository('IpcProgBundle:Erreur');
	switch($typeErreur) {
	case 'site':
		// On log le message d'erreur : Site incorrect
		$erreur = $repository_erreur->findOneByCode('S1NR');
		$patterns = array();
		$patterns[0] = '/\?s/';
		$replacements = array();
		$replacements[0] = $tabarg['affaire'];
		$texte = $erreur->getTexte($patterns, $replacements);
		$this->setLog("BD;Erreur S1NR;".$texte,$nomFichier);
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
	case 'date':
		// On log le message d'erreur : Format de date incorrect
		$erreur = $repository_erreur->findOneByCode('F4WT');
		$patterns = array();
		$patterns[0] = '/\?h/';
		$pattern[1] = '/\?f/';
		$replacements = array();
		$replacements[0] = $tabarg['dateFichier'];
		$replacements[1] = $fichier->getNom();
		$texte = $erreur->getTexte($patterns, $replacements);
		$this->setLog("BD;Erreur F4WT;".$texte,$nomFichier);
		break;
	case 'module':
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
	case 'sommeDonnees':
		// On log le message d'erreur Somme des données incorrect
		$erreur = $repository_erreur->findOneByCode('D2WS');
		$patterns = array();
		$replacements = array();
		$texte = $erreur->getTexte($patterns, $replacements);
		$this->setLog("Erreur D2WS;".$texte, $nomFichier);
		break;
	case 'nomFichier':
		// On log le message d'erreur : Nom du fichier incorrect
		$erreur = $repository_erreur->findOneByCode('F2WN');
		$patterns = array();
		$patterns[0] = '/\?f/';
		$replacements = array();
		$replacements[0] = $tabarg['nomFichier'];
		$texte = $erreur->getTexte($patterns, $replacements);
		$this->setLog("BD;Erreur F2WN;".$texte, $nomFichier);
		break;
	case 'fichierTraite':
		// On log le message d'erreur : Fichier Déjà Traité
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
	case 'donneeDupliquee':
		// On log le message d'erreur : La donnée est dupliquée en base de donnée
		$erreur = $repository_erreur->findOneByCode('D1AR');
		$patterns = array();
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
		$this->setLog("BD;Erreur D1AR;".$texte,$nomFichier);
		break;
	case 'moduleDonneeIncorrect':
		// On log le message d'erreur : La donnée est incorrecte (la quadruplet n'existe pas en base de donnée)
		$erreur = $repository_erreur->findOneByCode('D3IQ');
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
		$this->setLog("BD;Erreur D3IQ;".$texte, $nomFichier);
		break;
	}
	return(0);
}

}
