<?php 
//src/Ipc/ProgBundle/Services/ModBus/ServiceModBus
//	Service effectuant le transfert des fichiers ftp des localisations du site courant

namespace Ipc\ProgBundle\Services\ModBus;

use Ipc\ProgBundle\Entity\Site;
use Ipc\ConfigurationBundle\Entity\ModbusMaster;
use Ipc\ConfigurationBundle\Entity\IecType;
use Ipc\ConfigurationBundle\Entity\ConfigModbus;

class ServiceModBus {
protected $dbh;
protected $site;
protected $localisations;
protected $log;
protected $fichier_log;
// Dossier dans lequel sont placés les fichiers transférés : Variable dossier_fichiers_originaux
protected $dossier_local;
protected $email;
protected $configuration;
protected $email_admin;
protected $srv_transfertFtp;
protected $flagClotureModbus = '/tmp/.flagSymfonyClotureModbus';
protected $flagCreationBinaire='/tmp/.flagSymfonyScriptCreationBinaires';
protected $em;
protected $adresseMot;

public function __construct($srv_connexion, $srv_transfertFtp, $log, $email) {
	$this->dbh = $srv_connexion->getDbh();
	$this->em = $srv_connexion->getManager();

	$this->log = $log;
	$this->email = $email;
	$this->fichier_log = 'modbus.log';
	$this->srv_transfertFtp = $srv_transfertFtp;
	// Récupération des infos sur le site courant
	$site = new Site();
	$site_id = $site->SqlGetIdCourant($this->dbh);
	$this->site = $this->em->getRepository('IpcProgBundle:Site')->find($site_id);
	$this->adresseMot = array();
	$this->adresseMot['automates'] = $this->site->getLocalisations();

	// 						!!! 2005 NE PAS CHANGER 2005 !!!
	// Adresse du mot reservé pour déclanchement de l'écriture du fichier de log : 2005
	// Modification du 16/01/2019 : L'adresse du mot est dans la table des localisations $localisation->getAdresseModbus()
	//$this->adresseMot['binaryFiles']['downloadFtp'] = 2005;
	// 						!!! 2005 NE PAS CHANGER 2005 !!!

	// Ecriture de la valeur 1 pour création des fichiers de logs sur l'automate
	$this->adresseMot['dataBinaryFiles'] = array();
	$this->adresseMot['dataBinaryFiles']['activate'] = array(1);
	$this->adresseMot['dataBinaryFiles']['desactivate'] = array(0);
	$this->adresseMot['typeBinaryFiles'] = array("INT");
	return(0);
}

//  Lecture d'un mot par application modbus
public function readModbus($typeMot) {
	if ($typeMot == 'downloadFtp') {
		// Parcours de la liste des localisations pour lecture du mot sur chacune d'elles
		$codeRetour = -1;
		foreach ($this->adresseMot['automates'] as $localisation) {
			$codeRetour = $this->modbusReadDownload($localisation);
		}
		return(0);
	}
}

// Lecture du tableau de configuration Modbus : Utilisé pour l'affichage Live des valeurs par accès modbus
public function readConfigModbus(ConfigModbus $entityConfigModbus) {
	// Si une adresse de localisation est définie
	if ($entityConfigModbus != null) {
		$entityConfigModbus = $this->modbusReadConfigDownload($entityConfigModbus);
	}
	return($entityConfigModbus);
}

// Lecture du tableau de configuration Modbus : Utilisé pour l'affichage Live des valeurs par accès modbus
public function readInfosModbus(ConfigModbus $entityConfigModbus) {
	// Si une adresse de localisation est définie
	if ($entityConfigModbus != null) {
		// Traitement pour classification par famille
		$entityConfigModbus = $this->trieFamilles($entityConfigModbus);
	}
	return($entityConfigModbus);
}


private function getMultipleRegister($ipLocalisation, $designationLocalisation, $recData, $modbus, $deb, $nombreRegistre) {
	try {
		// Lecture de tous les registres dans une requête
		$tabMultipleRegister = $modbus->readMultipleRegisters(0,$deb,$nombreRegistre);
		if ($tabMultipleRegister == 'false1') {
			$this->log->setLog("[ERROR];[MODBUS];Lecture Multi-Registres;$designationLocalisation;$ipLocalisation;Echec de le connexion.",$this->fichier_log);
			$this->log->setLog("[ERROR];[MODBUS];Lecture Multi-Registres;".$modbus->getErrorMsg(),$this->fichier_log);
			return(-1);
		} else if ($tabMultipleRegister == 'false0') {
			$this->log->setLog("[ERROR];[MODBUS];Lecture Multi-Registres;$designationLocalisation;$ipLocalisation;Echec de lecture live readMultipleRegisters.",$this->fichier_log);
			return(-1);
		}
		if (is_array($tabMultipleRegister)) {
			// Le tableau récupéré a les indices de 0 à X => Création d'un tableau ayant les indices de deb à nombreRegistre
			$tabTmpRegistre = array();
			$numeroRegistre = $deb * 2 - 2;
			foreach ($tabMultipleRegister as $key=>$registre) {
				$tabTmpRegistre[$numeroRegistre] = $registre;
				$numeroRegistre ++;
			}
			$recData = $recData + $tabTmpRegistre;
		}
	}
	catch (\Exception $e) {
		// Print error information if any
		echo $modbus;
		echo $e;
		return(-1);
	}
	return($recData);
}


// Utilisé pour l'affichage Live des valeurs par accès modbus
public function modbusReadConfigDownload(ConfigModbus $entityConfigModbus) {
	$ipLocalisation = $entityConfigModbus->getIp();
	//	Rechercher des valeurs des adresses min et max
	$adresseMin = '';
	$adresseMax = '';
	foreach($entityConfigModbus->getDonneesLive() as $entityDonneeLive) {
		$adresseTmp	= $entityDonneeLive->getAdresse();
		// L'adresse récupérée peut être au format xx;xx
		$tab_adresse = explode(';', $adresseTmp);
		foreach($tab_adresse as $adresse) {
			// Pour les booleens, l'adresse se trouve avant le caractère X
			$pattern_bool = '/^(.+?)[Xx]/';
			if (preg_match($pattern_bool, $adresse, $tab_match)) {
				if ($adresseMin == '') {
					$adresseMin = $tab_match[1];
				} else {
					if ($tab_match[1] < $adresseMin) {
						$adresseMin = $tab_match[1];
					}
				}	
				if ($adresseMax == '') {
					$adresseMax = $tab_match[1];
				} else {
					if ($tab_match[1] > $adresseMax) {
						$adresseMax = $tab_match[1];
					}
				}
			} else {
				if ($adresseMin == '') {
					$adresseMin = $adresse;
				} else {
					if ($adresse < $adresseMin) {
						$adresseMin = $adresse;
					}
				}
				if ($adresseMax == '') {
					$adresseMax = $adresse;
				} else {
					if ($adresse > $adresseMax) {
						$adresseMax = $adresse;
					}	
				}
			}
		}
	}
	$valeurMin = floor($adresseMin/100) * 100;	
	$valeurMax = floor($adresseMax/100) * 100;
	$designationLocalisation = $entityConfigModbus->getDesignation();
	$tabValeursRetour = null;
	$modbus = new ModbusMaster($ipLocalisation);
	$recData = array();
	for ($pointeur_modbus = $valeurMin; $pointeur_modbus <= $adresseMax; $pointeur_modbus = $pointeur_modbus + 100) {
		$recData = $this->getMultipleRegister($ipLocalisation, $designationLocalisation, $recData, $modbus, $pointeur_modbus, 100);
		if ($pointeur_modbus == $valeurMin) {
			if ($recData == -1){ return(-1); }
		}
	}
	$num = 0;
	$mot = 1;
	$last_donnee = null;
	foreach ($recData as $donnee) {
		if (($num % 2 != 0)) {
			$mot ++;
		}
		$last_donnee = $donnee;
		$num ++;
	}
	// Pour chaque donnée Live : recherche de la valeur 
	foreach ($entityConfigModbus->getDonneesLive() as $entityDonneeLive) {
		$entityDonneeLive->setValeur(null);
		// Plusieurs valeurs peuvent être à rechercher par données Live (ex : Cas de en-tête)
		$tabDesAdresses = explode(';', $entityDonneeLive->getAdresse());
		$tabDesTypes = explode(';', $entityDonneeLive->getType());
		foreach ($tabDesAdresses as $key=>$adresse) {
			// Récupération du numéro du mot à lire
			$numWord = $tabDesAdresses[$key];
			$typeMot = $tabDesTypes[$key];
			$numBit = null;
			$pattern_bool = '/^(.+?)[Xx](.+?)$/';
			if (preg_match($pattern_bool, $numWord, $tab_word)) {
				$numWord = $tab_word[1];
				$numBit = $tab_word[2];
				$entityDonneeLive->setNumBit($numBit);
			}
			// Récupération des valeurs composant le mot
			// Si c'est un boolean 	: 1 registre  (2 octets)
			// Si c'est un réel 	: 2 registres (4 octets)
			$pointeur = $numWord * 2 - 2;
			$tabData = array();
			$valeur = null;
			if (strtoupper($typeMot) == 'INT') {
				// Récupération des valeurs des deux octets composant le registre
				$tabData[] = $recData[$pointeur + 1];
				$tabData[] = $recData[$pointeur];
				$entityDonneeLive->binToInt($tabData);
			} else if (strtoupper($typeMot) == 'REAL') {
				// Récupération des 2 Registres (4 octets) composant le reel
				for ($i = $pointeur + 3; $i >= $pointeur; $i--) {
					$tabData[] = $recData[$i];
				}
				$entityDonneeLive->binToReel($tabData);
			} else if (strtoupper($typeMot) == 'BOOL') {
				// Récupération des valeurs des deux octets composant le registre
				$tabData[] = $recData[$pointeur + 1];
				$tabData[] = $recData[$pointeur];
				$entityDonneeLive->binToBool($tabData);
			}
		}
	}
	// Traitement pour classification par famille
	return($entityConfigModbus);
}

// Fonction qui récupére sélectionne les données de même famille, les tries et retourne l'unique donnée correcte de la famille
public function trieFamilles($entityConfigModbus) {
	// 1 Parcours de chaque donnée de la liste des entités d'une localisation
	// Création du tableau des familles
	foreach($entityConfigModbus->getDonneesLive() as $key => $donneeLive) {
		if ($donneeLive->getPlacement() != 'enTete') {
			// Lecture de la famille de la donnée traitée
			$famille = $donneeLive->getFamille();
			if (isset($tabFamilles[$famille])) {
				$tabFamilles[$famille][] = $donneeLive;
			} else {
				$tabFamilles[$famille] = array();
				$tabFamilles[$famille][] = $donneeLive;
			}
		}
	}
	// Réinitialisation du message d'alerte / d'information
	$entityConfigModbus->setMessage('');
	if (isset($tabFamilles)) {
		// 2 Parcours du tableau des Familles pour effectuer le trie	
		foreach ($tabFamilles as $key => $tabEntitiesFamille) {
			// Récupération du nombre de données à trier
			$entityConfigModbus = $this->trieFamille($entityConfigModbus, $tabEntitiesFamille);
		}
	}
	return($entityConfigModbus);
}


// Fonction qui prend en argument la liste des entités ConfigModbus et le tableau des keys de famille à trier
// Si la famille comporte un mot : Ressort le mot 
// Si la famille comporte plusieurs mots : Ressort le mot dont la valeur correspond à la valeurEntreeVrai
// Si plusieurs mots sont en sortie : Enregistrement d'un message d'erreur
public function trieFamille(ConfigModbus $entityConfigModbus, $tabEntitiesFamille) {
	$nb_suppression = 0;
	$nb_membres = count($tabEntitiesFamille);
	// Si la famille comporte plus d'un mot : Recherche du mot en sortie
	if ($nb_membres > 1) {
		foreach ($tabEntitiesFamille as $key2 => $entityFamille) {
			// Une valeur Vrai = NULL est considérée comme toujours vrai (pas de vérification nécessaire)
			if (is_null($entityFamille->getValeurEntreeVrai()) == false) {
				if ($entityFamille->getValeur() != $entityFamille->getValeurEntreeVrai()) {
					$nb_suppression ++;
					if ($nb_suppression < $nb_membres) {
						$entityConfigModbus->removeDonneesLive($entityFamille);
					}
				}
			}
		}
		// Si le nombre d'objets - le nombre de suppression est supérieur à 1 : Un message d'alerte est relevé
		if ($nb_membres - $nb_suppression > 1) {
			$entityConfigModbus->setMessage($entityConfigModbus->getMessage()."<br />Attention ! Plusieurs messages corrects sont trouvés pour la famille ".$entityFamille->getFamille()." [ ".$entityFamille->getLabel()." ]");
		}
	}
	return($entityConfigModbus);	
}

//  Lecture d'un mot par application modbus
private function readOneModbus($typeMot, $localisation) {
	if ($typeMot == 'downloadFtp') {
		$codeRetour = $this->modbusReadDownload($localisation);
		return($codeRetour);
	}
}


private function modbusReadDownload($localisation) {
	$designationLocalisation = $localisation->getDesignation();
    $ipLocalisation = $localisation->getAdresseIp();
	$adresseModbus = $localisation->getAdresseModbus();

	$modbus = new ModbusMaster($ipLocalisation);
	try {
		$recData = $modbus->readMultipleRegisters(0, $adresseModbus, 1);
		if ($recData == false) {
			$this->log->setLog("[ERROR];[MODBUS];mot(".$adresseModbus.");$designationLocalisation;$ipLocalisation;Echec de la fonction readMultipleRegisters.", $this->fichier_log);
			return(-1);
		}
	} catch (\Exception $e) {
		// Print error information if any
		echo $modbus;
		echo $e;
		return(-1);
	}
	return ($recData[1]);	
}

// Fonction effectuant l'ecriture du mot 2005
private function modbusModDownload($typeAction, $localisation) {
	$designationLocalisation = $localisation->getDesignation();
	$ipLocalisation = $localisation->getAdresseIp();
	$adresseModbus = $localisation->getAdresseModbus();
	$retourModbusFonction = true;
	// Création de l'objet Modbus
	$modbus = new ModbusMaster($ipLocalisation);
	// Si c'est l'action d'activation qui est demandée : Côture des fichiers + Téléchargement Ftp
	if ($typeAction == 'activate') {
		$this->log->setLog("[INFO];[MODBUS];mot(".$adresseModbus.");$designationLocalisation;$ipLocalisation;Début d'analyse.", $this->fichier_log);
		try {
			// Ecriture CodeFonction 6
			$this->log->setLog("[INFO];[MODBUS];mot(".$adresseModbus.");$designationLocalisation;$ipLocalisation;Forcage de clôture des fichiers par appel applicatif Modbus.", $this->fichier_log);
			$retourModbusFonction = $modbus->writeSingleRegister(0, $adresseModbus, $this->adresseMot['dataBinaryFiles'][$typeAction], $this->adresseMot['typeBinaryFiles']);
			if ($retourModbusFonction == false) {
				$this->log->setLog("[ERROR];[MODBUS];mot(".$adresseModbus.");$designationLocalisation;$ipLocalisation;Echec de la fonction writeSingleRegister.", $this->fichier_log);
				return(-1);
			}
		} catch (Exception $e) {
			echo $modbus;
			echo $e;
			$this->log->setLog("[ERROR];[MODBUS];mot(".$adresseModbus.");$designationLocalisation;$ipLocalisation;$e", $this->fichier_log);
			return(-1);
		}
		// Variable permettant de calculer le Temps d'execution de la boucle
		$timestart = microtime(true);
		// Lecture du code retour jusqu'à ce qu'il soit = 0 ou que la limite de 15 secondes soit atteinte
		// Code 0 => Fichier clôturé, Télechagement possible
		// 15 secondes ecoulées	=> Echec de clôture du fichier : Fin de traitement & Remise à 0 de la variable modbus du déclanchement du télechagement Ftp
		while ($codeRetour = $this->readOneModbus('downloadFtp', $localisation) != 0) {
			// Récupération de la durée d'execution de la boucle en secondes
			$timeend = microtime(true);
			$time = $timeend-$timestart;
			$boucle_load_time = number_format($time, 3);
			// Sortie de boucle après 15 secondes max
			if ($boucle_load_time >= 15) {
				$this->log->setLog("[ERROR];[MODBUS];mot(".$adresseModbus.");$designationLocalisation;$ipLocalisation;Temps limite dépassé. Impossible de clôturer les fichiers.", $this->fichier_log);
				break;
			}
			// Sortie de boucle si la fonction est en echec : CR -1
			if (intVal($codeRetour) == -1) {
				$this->log->setLog("[ERROR];[MODBUS];mot(".$adresseModbus.");$designationLocalisation;$ipLocalisation;Echec de la fonction (CR $codeRetour). Veuillez contacter votre administrateur.", $this->fichier_log);
				$codeRetour = -1;
				break;
			}
			// Temporistation d'une seconde pour ne pas surcharger le réseau par des requêtes modbus
			sleep(1);
		}
		$this->log->setLog("[INFO];[MODBUS];mot(".$adresseModbus.");$designationLocalisation;$ipLocalisation;Fin d'action Modbus.", $this->fichier_log);
		// Si le code retour n'est pas 0. Pas de fichier clôturé.
		if ($codeRetour != 0) {
			return(-1);
		}
	} elseif ($typeAction == 'desactivate') {
		// Remise à 0 de la variable
		$this->log->setLog("[INFO];[MODBUS];mot(".$adresseModbus.");$designationLocalisation;$ipLocalisation;Remise à 0 du forcage clôture de fichier (mot :".$adresseModbus.").", $this->fichier_log);
		$retourModbusFonction = $modbus->writeSingleRegister(0, $adresseModbus, $this->adresseMot['dataBinaryFiles'][$typeAction], $this->adresseMot['typeBinaryFiles']);
		if ($retourModbusFonction == false) {
			$this->log->setLog("[ERROR];[MODBUS];mot(".$adresseModbus.");$designationLocalisation;$ipLocalisation;Echec de la fonction writeSingleRegister.", $this->fichier_log);
			return(-1);
		}
		$this->log->setLog("[INFO];[MODBUS];mot(".$adresseModbus.");$designationLocalisation;$ipLocalisation;Fin d'action Modbus.", $this->fichier_log);
	}
	return(0);
}

// Ecriture d'un mot par application modbus : TypeAction = Activation ou Désactivation
// TypeMot = Mot à modifier
// IpLocalisation = adresse ip de la localisation si une seule localisation est impactée par la modification
public function writeModbus($typeAction, $typeMot, $ipLocalisation) {
	$document_root = getenv("DOCUMENT_ROOT");
	// Cloture des fichiers par appel modbus et téléchargement uniquement si un traitement identique n'est pas déjà en cours
	if (! file_exists($this->flagClotureModbus)) {
		file_put_contents($this->flagClotureModbus,time());
		$this->log->setLog("[INFO];[MODBUS];Action demandée : $typeAction sur $ipLocalisation", $this->fichier_log);
		// Limite de temps max pour le transfert FTP en secondes
		$limitTransfertFTP 	= 10;
		// Limite de temps max pour l'importation des données en base en secondes
		$limitTransfertImport = 1 * 60;
		$transfertFtp = false;
		// Parcours de la liste des localisations
		foreach ($this->adresseMot['automates'] as $localisation) {
			// Variable indiquant que l'action d'activation ou de désactivation de la variable est demandée
			$action = false;
			if ($ipLocalisation != 'all') {
				// Si une localisation spécifique est demandée (représentée par son adresseIp) : L'action a lieu pour cette localisation uniquement
				if ($localisation->getAdresseIp() == $ipLocalisation) {
					$action = true;
				}
			} else {
				// Si toutes les localisations sont à traiter : L'action a toujours cours
				$action = true;
			}
			// Si l'action a cours pour la localisation en cours d'analyse
			if ($action == true) {
				if ($typeMot == 'downloadFtp') {
					// Appel de la fonction d'écriture du mot 2005
					$codeRetour = $this->modbusModDownload($typeAction, $localisation);
					if ($codeRetour != 0) {
						$this->log->setLog("[ERROR];[MODBUS];mot(".$localisation->getAdresseModbus().");".$localisation->getAdresseIp().";".$localisation->getDesignation().";Fin d'action Modbus : Erreurs rencontrées. Veuillez Contacter votre administrateur.", $this->fichier_log);
					} else {
						$transfertFtp = true;
					}
				}
			}
		}
		unlink($this->flagClotureModbus);



		// Téléchargement des fichiers Ftp si la demande d'activation a été demandée et si au moins une clôture de fichier est en succés
		if ($typeAction == 'activate') {
			if ($transfertFtp == true) {
				$this->log->setLog("[INFO];[MODBUS];transfert(Ftp);Début de transfert ftp", $this->fichier_log);
				// Début de téléchargement ftp
				$timestartFTP = microtime(true);
				$this->srv_transfertFtp->importation();
				sleep(1);
				$this->log->setLog("[INFO];[MODBUS];transfert(Ftp);Service de transfert ftp en cours d'execution", $this->fichier_log);
				// Attente de la fin du transfert FTP : Par la vérification de la présence du flag de transfert
				// Sortie de boucle après une limite de temps 
				$flagFtp = "/tmp/.flagSymfonyDownloadFtp";
				while (file_exists($flagFtp)) {
					$this->log->setLog("existe", $this->fichier_log);
					$timeendFTP = microtime(true);
					sleep(3);
					$timeFTP = $timeendFTP - $timestartFTP;
					$boucle_load_time_FTP = number_format($timeFTP, 3);
					if ($boucle_load_time_FTP >= $limitTransfertFTP) {
						$this->log->setLog("[INFO];[MODBUS];transfert(Ftp);Timeout atteind - Des fichiers sont toujours en cours de téléchagement ftp", $this->fichier_log);
						break;
					}
				}
				$this->log->setLog("[INFO];[MODBUS];transfert(Ftp);Fin de transfert ftp", $this->fichier_log);





				$this->log->setLog("[INFO];[MODBUS];import(Bin);Création des fichiers binaires", $this->fichier_log);
				// Lancement de la création des fichiers binaires
				$commande = "sudo ".$document_root."/web/sh/GestionTransferts/TransfertsIpc/creationBin.sh $document_root";
				exec($commande);
				// Attente de la création des fichiers binaires
				$timestartCreation = microtime(true);
				while (file_exists($this->flagCreationBinaire)) {
					$timeendCreation = microtime(true);
					sleep(1);
					$timeCreation = $timeendCreation - $timestartCreation;
					$boucle_load_time_Import = number_format($timeCreation, 3);
					if ($boucle_load_time_Import >= $limitTransfertImport) {
						$this->log->setLog("[INFO];[MODBUS];import(Bin);Timeout atteind - Des fichiers binaires sont toujours en cours de création", $this->fichier_log);
						break;
					}
				}
				// Lancement de l'importation des fichiers en base
				$this->log->setLog("[INFO];[MODBUS];import(Bin);Importation des données en base", $this->fichier_log);
				$commande = "sudo ".$document_root."/web/sh/GestionTransferts/TransfertsIpc/importBin.sh $document_root";
				exec($commande);
				$flagImportBin = "/tmp/.flagSymfonyImportBinaires";
				$timestartImport = microtime(true);
				while (file_exists($flagImportBin)) {
					$timeendImport = microtime(true);
					sleep(1);
					$timeImport = $timeendImport - $timestartImport;
					$boucle_load_time_Import = number_format($timeImport, 3);
					if ($boucle_load_time_Import >= $limitTransfertImport) {
						$this->log->setLog("[INFO];[MODBUS];Timeout atteind - Des fichiers sont toujours en cours d'importation", $this->fichier_log);
						break;
					}
				}
				$this->log->setLog("[INFO];[MODBUS];Fin de traitement",$this->fichier_log);
			} else {
				$this->log->setLog("[ERROR];[MODBUS];mot(".$localisation->getAdresseModbus().");Fin d'action : Aucune clôture de fichier detectée. Annulation du téléchargement Ftp.", $this->fichier_log);
			}
		}
	} else {
		$this->log->setLog("[INFO];[MODBUS];Action demandée : $typeAction sur $ipLocalisation", $this->fichier_log);
		$this->log->setLog("[INFO];[MODBUS];Drapeau ".$this->flagClotureModbus." trouvé : Une action similaire est déjà en cours d'execution", $this->fichier_log);
	}
	return(0);
}

}
