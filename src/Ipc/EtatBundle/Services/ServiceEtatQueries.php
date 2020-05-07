<?php
//src/Ipc/EtatBundle/Services/EtatQueries.php
namespace Ipc\EtatBundle\Services;

use Ipc\ProgBundle\Entity\Configuration;
use Ipc\ProgBundle\Entity\Donnee;

class ServiceEtatQueries {

/* Liste des fonctions */
/*
private 	function 	transformeMessage		($message1)
protected 	function 	getEtatsDir				()
protected 	function 	isInCondition			($valeur,$condA,$valCondition,$val2Condition)
protected 	function 	getTraitementTabPeriode	($dateDeb,$dateFin,$tabloATraiter,$typeComparaison)
protected 	function 	addDateTime				($timeInit,$timeToAdd)
protected   function    addDateTimestamp		($timestampInit,$timestampToAdd)
public 		function 	__construct				($srv_connexion,$serviceLog,$serviceConfiguration)
public 		function 	getMostMessages			($dateDeb,$dateFin,$idLocalisation,$type,$idType,$parametreA,$nbMessages,$condA,$valA,$valA2,$condB,$valB,$valB2)
public 		function 	comparaisonDeModules	($dateDeb,$dateFin,$idLA,$idA,$condA,$valA,$valA2,$idLB,$idB,$condB,$valB,$valB2,$typeComparaison)
public 		function 	analyseModuleCompteur	($dateDeb,$dateFin,$idLA,$idA,$condA,$valA,$valA2)
public 		function 	analyseModuleTest		($dateDeb,$dateFin,$idLA,$idA,$condA,$valA,$valA2)
public 		function 	analyseModuleForcage	($dateDeb,$dateFin,$idLA,$idA,$condA,$valA,$valA2)
public 		function 	comptageSurPeriode		($dateDeb,$dateFin,$calcul,$idLA,$idA,$condA,$valA,$valA2)
public 		function 	occurencesSurPeriode	($dateDeb,$cycleDeb,$dateFin,$cycleFin,$idLA,$idA,$condA,$valA,$valA2)
public 		function 	intCalculSurPeriode		($dateDeb,$dateFin,$calcul,$idLA,$idA,$condA,$valA,$valA2)
public 		function 	getTabCalculPar			($periode,$dateDeb,$dateFin,$calcul,$idLA,$idA,$fichier)
public 		function 	calculDifferenceMoyenne	($periode,$dateDeb,$dateFin,$idLA,$idA,$idLB,$idB,$fichierA,$fichierB,$fichierDiff)
public 		function 	calculPar				($periode,$dateDeb,$dateFin,$calcul,$idLA,$idA)
public 		function 	calculParJour			($dateDeb,$dateFin,$calcul,$idLA,$idA)
public 		function 	calculParHeure			($dateDeb,$dateFin,$calcul,$idLA,$idA)
public 		function 	calculParMinute			($dateDeb,$dateFin,$calcul,$idLA,$idA)
public 		function 	EtatAnalyseDeMarche		($action,$entityEtat)
public 		function 	getMaxDuration			($dateDeb,$cycleB,$dateFin,$idLB,$idB,$condB,$valB,$valB2)
public 		function 	getLastMessages			($nombre)
private 	function 	reverseDate				($dateToBeTransformed, $format)
*/


protected $dbh;
protected $em;
protected $serviceLog;
protected $serviceConfiguration;
protected $limitFirstDate;
protected $entity_mode_module1;
protected $tabSystStatIoAvert;
private $debug;

private $id_localisation_1;
private $id_localisation_2;
private $id_module_1;
private $id_module_2;
private $condition_1;
private $condition_2;
private $valeur_1_de_condition_1;
private $valeur_2_de_condition_1;
private $valeur_1_de_condition_2;
private $valeur_2_de_condition_2;

protected $srv_fillNumbers;


public function __construct($srv_connexion, $serviceLog, $serviceConfiguration, $srv_fillNumbers) {
	$this->serviceLog = $serviceLog;
	$this->serviceConfiguration	= $serviceConfiguration;
	$this->dbh = $srv_connexion->getDbh();
	$this->em = $srv_connexion->getManager();
	$this->srv_fillNumbers = $srv_fillNumbers;
	$configuration = new Configuration();
	date_default_timezone_set($configuration->SqlGetParam($this->dbh, 'timezone'));
    $limitFirstDate = null;
    $limitFirstDate = $configuration->SqlGetParam($this->dbh, 'date_de_mise_en_service');
	$paramSystStatIoAvert = $configuration->SqlGetParam($this->dbh, 'etat_amc_codes_syst_stat_io_avert');
	$this->tabSystStatIoAvert = explode(';', $paramSystStatIoAvert);
	$this->limitFirstDate = $limitFirstDate;
	$this->debug = false;
}

// Retourne le format de date utilisés dans les fonctions
private function setFormatDate($dateEntree) {
    $dateSortie = $dateEntree;
    $pattern_date = '/^(\d+?)[\/-](\d+?)[\/-](\d+?)(\s.*)$/';
    if (preg_match($pattern_date, $dateEntree, $tabDate)) {
        $dateSortie = $tabDate[1].'-'.$tabDate[2].'-'.$tabDate[3].$tabDate[4];
    }
    return $dateSortie;
}


protected function getEtatsDir() {
	return __DIR__.'/../../../../web/etats/';
}

// FONCTION A
// Définition
//		@tab : Retourne les x messages les plus couremment rencontrés
//				type 			: Condition de recherche : Type (ex : 'genre')
//				intituleType 		: Condition de recherche : Intitulé type(ex : 'Defaut')  
//				nombre			: Condition de recherche : Nombre de messages à rechercher
//				Pour chaque message récupéré
//				messages|key|id 	: Id du message
//				messages|key|message	: Intitulé du message
//				messages|key|nbMessages : Nombre de messages sur la période
// Paramètres d'entrée
// 		dateDeb 		: Date de début de période
// 		dateFin 		: Date de fin de période
// 		idLocalisation 	: Localisation sur laquelle porte la recherche
// 		type			: Type du message à rechercher (Genre / Module / All)
// 		idType 			: Identifiant du type si il n'est pas 'All' (idGenre ou intitulé du Module)
// 		nbMessages 		: Nombre de messages à rechercher
// 		condA 			: Condition de restriction sur la valeur1 (>,<,!=,<=,>=,<>)
// 		valA			: Valeur de la condition de restriction
// 		valA2 			: Valeur 2 de la condition (utilisée lorsque la condition est 'between')
public function getMostMessages($dateDeb, $dateFin, $idLocalisation, $type, $idType, $parametreA, $nbMessages, $condA, $valA, $valA2, $condB, $valB, $valB2) {
	$tabRetour = array();
	$donnee = new Donnee();

	if (($type == 'genre') || ($type == 'module')){
		$listeMessages = $donnee->getIdModule($this->dbh, $type, $idType);
	} elseif ($type == 'codeModuleAR') {
		$tabCodeModulesExclusion = preg_split('/;/', $parametreA);
		$tabCodeModule = preg_split('/;/', $idType);
		$categorie = $tabCodeModule[0];
		$numeroModule = $tabCodeModule[1];
		$listeMessages = '';
		$tabMessages = array();
		$entitiesModuleAnomaliesRegulation = $this->em->getRepository('IpcProgBundle:Module')->findBy(array('categorie' => $categorie, 'numeroModule' => $numeroModule, 'mode' => $this->entity_mode_module1));
		//Liste des modules spéciaux
		foreach ($entitiesModuleAnomaliesRegulation as $entityModule) {
			if (in_array($entityModule->getCode(), $this->tabSystStatIoAvert)){
					$tabMessages[$entityModule->getId()] = array();
					// Recherche de toutes les valeurs 2 ( de £ ) pour le module 
					$tabValeurLivre = $this->rechercheValeurLivre($dateDeb, $dateFin, $idLocalisation, $entityModule->getId());
					foreach ($tabValeurLivre as $key => $tabLivre) {
						array_push($tabMessages[$entityModule->getId()], $tabLivre[0]);
					}
					$listeMessages = $listeMessages.$entityModule->getId().',';
			} else {
			// Si le module est dans la liste des modules à exclure, on ne le prend pas en compte
			if (! in_array($entityModule->getCode(), $tabCodeModulesExclusion)) {
				$listeMessages = $listeMessages.$entityModule->getId().',';
				$tabMessages[$entityModule->getId()] = null;
			}
			}
		}
		$listeMessages = substr($listeMessages ,0, -1);
	}
	
	
	// Si le type de module à rechercher est un défaut : Exclusion des id de modules de Came numérique
	if ($type == "genre") {
		$nouvelle_listeMessages = "";
		$tabtmpListe = explode(',', $listeMessages);
		$listeCame = $donnee->getIdModule($this->dbh, 'module', 'Came numérique £');
		$tabCame = explode(',', $listeCame);
		foreach ($tabtmpListe as $idMessage) {
		    if (! in_array($idMessage, $tabCame)) {
				$nouvelle_listeMessages .= $idMessage.',';
		    }
		}
		$nouvelle_listeMessages = substr($nouvelle_listeMessages, 0, -1);
		$listeMessages = $nouvelle_listeMessages;
	}
	$tabMostMessages = array();
	$nbOccurences = 0;
	if ($type == 'codeModuleAR') {
		if ($listeMessages != "") {
			$tabMostMessages = $donnee->getMostMessagesAR($this->dbh, $dateDeb, $dateFin, $idLocalisation, $tabMessages, $nbMessages, $condA, $valA, $valA2, $condB, $valB, $valB2);
			$nbOccurences = $donnee->getNbOccurences($this->dbh, $dateDeb, $dateFin, $idLocalisation, $listeMessages, $nbMessages, '=', '1', $valA2, $condB, $valB, $valB2);
		}
	} else {
		if ($listeMessages != "") {
			$tabMostMessages = $donnee->getMostMessages($this->dbh, $dateDeb, $dateFin, $idLocalisation, $listeMessages, $nbMessages, $condA, $valA, $valA2, $condB, $valB, $valB2);
			$nbOccurences = $donnee->getNbOccurences($this->dbh, $dateDeb, $dateFin, $idLocalisation, $listeMessages, $nbMessages, $condA, $valA, $valA2, $condB, $valB, $valB2);
		}
	}
	if ($type == 'genre') {
	    $intituleType = $this->em->getRepository('IpcProgBundle:Genre')->find($idType)->getIntituleGenre();
	} else if ($type == 'module') {
	    $intituleType = $idType;
	} else {
		$intituleType = $idType;
	}
	$tabRetour['type'] = $type;
	$tabRetour['intituleType'] = $intituleType;
	$tabRetour['nombre'] = $nbMessages;
	$tabRetour['nbOccurences'] = $nbOccurences;
	$tabRetour['messages'] = array();
	foreach ($tabMostMessages as $key => $message) {
	    $module = $this->em->getRepository('IpcProgBundle:Module')->find($message['module_id']);
	    $tabRetour['messages'][$key]['nbMessages'] = $message['nbMessages'];
	    $tabRetour['messages'][$key]['id'] = $message['module_id'];
		//Si le message est de type Io Avert, on remplace le livre par la valeur 2
		if (in_array($module->getCode(), $this->tabSystStatIoAvert)){
			$tabRetour['messages'][$key]['message'] = $module->getCode().': '.$this->transformeMessage($this->transformeMessage($module->getMessage(), 'livre', $message['valeur2']));
		} else {
			$tabRetour['messages'][$key]['message'] = $module->getCode().': '.$this->transformeMessage($module->getMessage());
		}
	}
	return($tabRetour);
}

// Fonction getMostMessage adaptée au module spécifique Came Numérique £
public function getMostMessagesCame($dateDeb, $dateFin, $idLocalisation, $nbMessages, $condA, $valA, $valA2, $condB, $valB, $valB2, $numeroCame) {
	$tabRetour = array();
	$donnee = new Donnee();
	$listeMessages = $donnee->getIdModule($this->dbh, 'module', 'Came numérique £');
	// DEBUG : echo "\nLISTE MESSAGE : $listeMessages\n";
	$nbOccurences = $donnee->getNbOccurences($this->dbh, $dateDeb, $dateFin, $idLocalisation, $listeMessages, $nbMessages, $condA, $valA, $valA2, $condB, $valB, $valB2);
	// DEBUG : echo "\nNbOccurence : $nbOccurences\n";
	$tabMostMessages = $donnee->getMostMessages($this->dbh, $dateDeb, $dateFin, $idLocalisation, $listeMessages, $nbMessages, $condA, $valA, $valA2, $condB, $valB, $valB2);
	// DEBUG : echo "Most mess :  $dateDeb, $dateFin, $idLocalisation, $listeMessages, $nbMessages";
	// DEBUG : print_r($tabMostMessages);
	$intituleType = "Came numérique $numeroCame";
	$tabRetour['type'] = 'module';
	$tabRetour['intituleType'] = $intituleType;
	$tabRetour['nombre'] = $nbMessages;
	$tabRetour['nbOccurences'] = $nbOccurences;
	$tabRetour['messages'] = array();
    foreach ($tabMostMessages as $key => $message) {
        $module = $this->em->getRepository('IpcProgBundle:Module')->find($message['module_id']);
        $tabRetour['messages'][$key]['message']     = $module->getCategorie().$this->srv_fillNumbers->fillNumber($module->getNumeroModule(),2).$this->srv_fillNumbers->fillNumber($module->getNumeroMessage(),2).': '.$this->transformeCameMessage($module->getMessage(), $numeroCame);
        $tabRetour['messages'][$key]['nbMessages']  = $message['nbMessages'];
        $tabRetour['messages'][$key]['id']          = $message['module_id'];
    }
    return($tabRetour);
}


// FONCTION B
// Définition
// Retourne les messages d'un module en fonction d'un autre module
// Paramètres d'entrée
//		dateDeb		: Date de début de période
//  	dateFin 	: Date de fin de période
//  	idLA 		: Id de la localisation du module A
//  	idA		: Identifiant du module A
//  	condA 		: Condition de restriction de la valeur1 pour la recherche du module A
//		valA		: Valeur de la condition de restriction
//		valA2		: Valeur2 de la condition de restriction (utilisée lorsque la condition est <> -> 'between')
//		idLB		: Id de la localisation du module B
//  	idB		: Identifiant du module B
//  	condB		: Condition de restriction de la valeur1 pour la recherche du module B
//		valB		: Valeur de la condition de restriction
//		valB2		: Valeur2 de la condition de restriction (utilisée lorsque la condition est <> -> 'between')
//  	typeComparaison : Choix du type de comparaison à effectuer ( Compare / Comptage / Both / None )
public function comparaisonDeModules($dateDeb, $dateFin, $idLA, $idA, $condA, $valA, $valA2, $idLB, $idB, $condB, $valB, $valB2, $typeComparaison, $debug) {
	$this->id_localisation_1 = $idLA;
	$this->id_localisation_2 = $idLB;
	$this->id_module_1 = $idA;
	$this->id_module_2 = $idB;
	$this->condition_1 = $condA;
	$this->condition_2 = $condB;
	$this->valeur_1_de_condition_1 = $valA;
	$this->valeur_2_de_condition_1 = $valA2;
	$this->valeur_1_de_condition_2 = $valB;
    $this->valeur_2_de_condition_2 = $valB2;




	if ($debug == true) {
		echo "Comparaison de type $typeComparaison sur la période $dateDeb au $dateFin\nSur la localisation $idLA [$idLB]\n$idA $condA $valA - $valA2, $idB $condB $valB - $valB2\n";
	}
    // Récupération des informations du module A
    $moduleA = $this->em->getRepository('IpcProgBundle:Module')->find($idA);
	$messageModuleA = $moduleA->getMessage();
	$uniteModuleA = $moduleA->getUnite();
	$typeComparaison = strtolower($typeComparaison);
	// Si une comparaison est demandée 
	//	Récupération des informations du module B
	if ($typeComparaison != 'none') {
        $moduleB = $this->em->getRepository('IpcProgBundle:Module')->find($idB);
        $messageModuleB = $moduleB->getMessage();
        $uniteModuleB = $moduleB->getUnite();
	}


	$tabPeriode = $this->analysePeriode('tableauPeriodique', 'compare', $this->id_localisation_1, $this->id_module_1, 0, $dateDeb, $dateFin, $this->condition_1, $this->valeur_1_de_condition_1, $this->valeur_2_de_condition_1);

    if ($this->debug == true) {
        echo "SYNTHESE : $typeComparaison\n";
        print_r($tabPeriode);
        echo "\n";
    }


	//
	// echo "Tableau des periode<br />";
	// echo "<br /><br />";
	// Une fois toutes les périodes satisfaisant la condition récupérées : Analyse des données de chaque période pour retourner la synthèse
	// Retourne 
	// messageModuleA : Message du module 1
	// uniteModuleA	: Unité du module 1 
	// Si une comparaison de module est demandée
	// messageModuleB : Message du module 2
	// uniteModuleB	: Unité du module 2
	// echo "Analyse des différentes périodes du tableau<br />";
	$tableauDeComparaison = array();
	$tableauDeComparaison = $this->getTraitementTabPeriode($dateDeb, $dateFin, $tabPeriode, $typeComparaison, $debug);
	$tableauDeComparaison['messageModuleA']	= $messageModuleA;
	$tableauDeComparaison['uniteModuleA'] = $uniteModuleA;
	if ($typeComparaison != 'none') {
	    $tableauDeComparaison['messageModuleB']	= $messageModuleB;
	    $tableauDeComparaison['uniteModuleB'] = $uniteModuleB;
 	}
    return($tableauDeComparaison);
}



private function analysePeriode($typeAnalyse = 'duree', $typeComparaison = 'none', $idLocalisation, $idModule, $cycle = 0,  $dateDeDebut, $dateDeFin, $conditionSurValeur, $valeur1DeLaCondition, $valeur2DeLaCondition) {

	if ($this->debug) {
		echo "\n\nAnalyse de type $typeAnalyse ($typeComparaison) sur la période du $dateDeDebut au $dateDeFin pour le module $idModule (loc $idLocalisation)\n";
		echo "Condition de validité : Valeur $conditionSurValeur $valeur1DeLaCondition (et $valeur2DeLaCondition)\n";
	}
	$donnee = new Donnee();
    $pointeur = $dateDeDebut;
	$tableauDesPeriodes = array();
    $dureeMaximum = 0;
	$dureeDesPeriodes = 0;
	$tableauDesDebutsDePeriode = array();
	$tableauDesFinDePeriode = array();
	$numeroPeriode = 0;

	// Recherche de la valeur avant le début de la période
	$dateDeValeurEnDebutDePeride = $this->serviceConfiguration->rechercheLastValue($idModule, $idLocalisation, $dateDeDebut, $this->limitFirstDate);
	if ($this->debug == true) {
        echo "Date de début de période trouvée le $dateDeValeurEnDebutDePeride\n";
    }
	$valeurEnDebutDePeriode = $donnee->sqlLastGetValeur($this->dbh, $dateDeValeurEnDebutDePeride, $this->addADay($dateDeValeurEnDebutDePeride), $idModule, $idLocalisation);
	if ($this->debug == true) {
		echo "Valeur en début de période = $valeurEnDebutDePeriode\n";
	}
	if ($valeurEnDebutDePeriode == '') {
		$valeurEnDebutDePeriode = 0;
	}
	// Si la valeur en début de période est en accord avec la condition de recherche, on initialise le premier point du tableau	
	if ($conditionSurValeur != null) {
		if ($this->isInCondition($valeurEnDebutDePeriode, $conditionSurValeur, $valeur1DeLaCondition, $valeur2DeLaCondition)) {
			if ($this->debug == true) {
				echo "Condition remplie : cycle $cycle\n";
			}
			$tableauDesDebutsDePeriode[0] = array();
            $tableauDesDebutsDePeriode[0]['horodatage'] = $dateDeDebut;
            $tableauDesDebutsDePeriode[0]['cycle'] = $cycle;
            $tableauDesDebutsDePeriode[0]['valeur1'] = $valeurEnDebutDePeriode;
		}
	} else {
        // Si aucune condition n'est demandée : Le premier point de la recherche satisfait forcement à la condition
        $tableauDesDebutsDePeriode[0] = array();
        $tableauDesDebutsDePeriode[0]['horodatage'] = $dateDeDebut;
        $tableauDesDebutsDePeriode[0]['cycle'] = $cycle;
        $tableauDesDebutsDePeriode[0]['valeur1'] = $valeurEnDebutDePeriode;
    }
	
	while ($pointeur < $dateDeFin) {
		$tempsPeriode = null;
		$dureeDeLaPeriode = 0;

		if ($this->debug == true) {
			echo "\nPeriode : $numeroPeriode\n";
			echo "$pointeur n'est pas >= à $dateDeFin\n";
		}
		// Lorsqu'on entre dans la boucle :
		// 		Si la valeur initiale est inclue dans la condition de recherche => Recherche de la fin de condition
		// 		Sinon recherche d'un début de condition valide
		// A chaque nouveau parcours de la boucle : recherche d'un nouveau début de la période satisfaisant à la condition
		if (($pointeur == $dateDeDebut) && ($numeroPeriode == 0)) { 
			if ($this->debug == true) {
                 echo "Pointeur au début\n";
            }
			if ($conditionSurValeur != null) {
				if (! $this->isInCondition($valeurEnDebutDePeriode, $conditionSurValeur, $valeur1DeLaCondition, $valeur2DeLaCondition)) {
					if ($this->debug == true) {
						echo "Recherche tableau des débuts de periode INIT\n";
					}
					$tableauDesDebutsDePeriode = $donnee->getTheValue($this->dbh, $pointeur, $cycle, $dateDeFin, $idLocalisation, $idModule, $conditionSurValeur, $valeur1DeLaCondition, $valeur2DeLaCondition, 'init');
				} else {
				    if ($this->debug == true) {
                       echo "Condition remplie\n";
                    }
				}
			} else {
				$tableauDesDebutsDePeriode = $donnee->getTheValue($this->dbh, $pointeur, $cycle, $dateDeFin, $idLocalisation, $idModule, $conditionSurValeur, $valeur1DeLaCondition, $valeur2DeLaCondition, 'init');
			}
		} else {
			if (($typeAnalyse == 'tableauPeriodique') || ($typeAnalyse == 'duree')) {
       			if ($this->debug == true) {
            		echo "Recherche tableau des débuts de periode\n";
        		}
				$tableauDesDebutsDePeriode = $donnee->getTheValue($this->dbh, $pointeur, $cycle, $dateDeFin, $idLocalisation, $idModule, $conditionSurValeur, $valeur1DeLaCondition, $valeur2DeLaCondition, 'init');
			} else if ($typeAnalyse == 'dureeEntreCondition') {
				// Lorsque l'on recherche la durée de la plus grande période entre deux conditions remplies, la nouvelle période débute dès la fin de la période précédente.
				$tableauDesDebutsDePeriode = $tableauDesFinDePeriode;
			}
		}
		if ($this->debug == true) {
            echo "Tableau des débuts de période\n";
			print_r($tableauDesDebutsDePeriode);
        }
		// Si il n'y a plus de valeur satisfaisant à la condition : Fin de recherche
		if (empty($tableauDesDebutsDePeriode)) {
			break;
		}
		// Recherche de la fin de période
		// A partir de la date de la valeur remplissant la condition de recherche, récupération de la première valeur du module lorsque la condition n'est pas remplie
		$directionDeRecherche = null;
		if (($typeAnalyse == 'tableauPeriodique') || ($typeAnalyse == 'duree')) {
			$directionDeRecherche = 'inverse';
		} else if ($typeAnalyse == 'dureeEntreCondition') {
			// Lorsque l'on recherche la durée de la plus grande période entre deux conditions remplies, la fin de période à lieu lorsqu'une nouvelle occurence de la condition remplie est trouvée.
			$directionDeRecherche = 'init';
		}
		if ($this->debug == true) {
			echo "Recherche tableau des fin de periode : $directionDeRecherche\n";
		}
		$tableauDesFinDePeriode = $donnee->getTheValue($this->dbh, $tableauDesDebutsDePeriode[0]['horodatage'], $tableauDesDebutsDePeriode[0]['cycle'], $dateDeFin, $idLocalisation, $idModule, $conditionSurValeur, $valeur1DeLaCondition, $valeur2DeLaCondition, $directionDeRecherche);


		if ($this->debug == true) {
			echo "Tableau des fin de période : ";
			print_r($tableauDesFinDePeriode);
			echo "FIN DE PERIODE\n";
		}
		// Si aucune valeur ne remplie la condition de recherche c'est que la condition est valable jusqu'à la fin de la période d'analyse
		$tmpDateDeDebut = new \Datetime($tableauDesDebutsDePeriode[0]['horodatage']);
		if (empty($tableauDesFinDePeriode)) {
			$tmpDateDeFin = new \Datetime($dateDeFin);
			$tableauDesPeriodes[$numeroPeriode]['dateFin'] = $this->setFormatDate($dateDeFin);
            $tableauDesPeriodes[$numeroPeriode]['millisecFin'] = 0;
			$cycle = 0;
			$pointeur = $dateDeFin;
			if ($this->debug == true) {
				echo "Nouveau pointeur de fin de période $pointeur\n";
			}
		} else {
			// Si une valeur de fin de période est trouvée : Cloture de la période qui remplie la condition et incrementation du pointeur pour effectuer la recherche d'une nouvelle période
			$tmpDateDeFin = new \Datetime($tableauDesFinDePeriode[0]['horodatage']);
			$tableauDesPeriodes[$numeroPeriode]['dateFin'] = $this->setFormatDate($tableauDesFinDePeriode[0]['horodatage']);
			$tableauDesPeriodes[$numeroPeriode]['millisecFin'] = $tableauDesFinDePeriode[0]['cycle'];
			$cycle = $tableauDesFinDePeriode[0]['cycle'];
			$pointeur = $tableauDesFinDePeriode[0]['horodatage'];
			if ($this->debug == true) {
				echo "Nouveau pointeur de fin de période $pointeur - $cycle\n";
			}
		}
		$dureeDeLaPeriode = $this->addDateTimestamp($dureeDeLaPeriode, $tmpDateDeFin->getTimestamp() - $tmpDateDeDebut->getTimestamp());
		if ($this->debug == true) { 
			echo "Durée de la période du ".$tmpDateDeDebut->format('Y-m-d H:i:s')." au ".$tmpDateDeFin->format('Y-m-d H:i:s')." = $dureeDeLaPeriode \n";
		}
		if ($dureeDeLaPeriode > $dureeMaximum) {
			$dureeMaximum = $dureeDeLaPeriode;
			if ($typeAnalyse == 'dureeEntreCondition') {
				$tempsPeriode = $this->addDateTime($tempsPeriode, $tmpDateDeDebut->diff($tmpDateDeFin));
			}
		}
		$tableauDesPeriodes[$numeroPeriode]['temps'] = $tmpDateDeDebut->diff($tmpDateDeFin);
		$tableauDesPeriodes[$numeroPeriode]['tempsTimestamp'] = $tmpDateDeFin->getTimestamp() - $tmpDateDeDebut->getTimestamp();
		$tableauDesPeriodes[$numeroPeriode]['dateDeb'] = $tableauDesDebutsDePeriode[0]['horodatage'];
		$tableauDesPeriodes[$numeroPeriode]['millisecDeb'] = $tableauDesDebutsDePeriode[0]['cycle'];
		$tableauDesPeriodes[$numeroPeriode]['valeurDeb'] = $tableauDesDebutsDePeriode[0]['valeur1'];
		$tableauDesPeriodes[$numeroPeriode]['valeurFin'] = $donnee->sqlLastGetValeurStrict($this->dbh, $tableauDesPeriodes[$numeroPeriode]['dateDeb'], $tableauDesPeriodes[$numeroPeriode]['dateFin'], $idModule, $idLocalisation);
		// Si aucune valeur n'est récupérée c'est que la valeur n'a pas variée entre le début et la fin : valeur de fin = valeur de début
		if ($tableauDesPeriodes[$numeroPeriode]['valeurFin'] == null) {
			$tableauDesPeriodes[$numeroPeriode]['valeurFin'] = $tableauDesPeriodes[$numeroPeriode]['valeurDeb'];
		}

	
	    // Si une comparaison avec un deuxième module est demandée : Analyse du 2eme module dans l'intervalle satisfaisant la condition
		if ($typeComparaison == 'compare') {
			if ($this->debug == true) {
				echo "pointeur : $pointeur\n";
				echo "Periode : $numeroPeriode\n";
				//print_r($tableauDesPeriodes);
				echo "Zone comparaison avec le module 2\n";
				echo "Recherche de la période du module 2 du ".$this->setFormatDate($tableauDesPeriodes[$numeroPeriode]['dateDeb'])." au ".$this->setFormatDate($tableauDesPeriodes[$numeroPeriode]['dateFin'])." pour le module ".$this->id_module_2." (".$this->id_localisation_2.")\n";
			}
			$tableauDesPeriodes[$numeroPeriode]['dureeModuleB'] = $this->analysePeriode('duree', 'none', $this->id_localisation_2, $this->id_module_2, $tableauDesPeriodes[$numeroPeriode]['millisecDeb'], $this->setFormatDate($tableauDesPeriodes[$numeroPeriode]['dateDeb']), $this->setFormatDate($tableauDesPeriodes[$numeroPeriode]['dateFin']), $this->condition_2, $this->valeur_1_de_condition_2, $this->valeur_2_de_condition_2);
			if ($this->debug == true) {
				echo "Fin de zone de comparaison\n";
				echo "TYPE COMPARAISON 2 : $typeComparaison - $typeAnalyse\n";
			}
		}
		if ($typeAnalyse == 'duree') {
			$dureeDesPeriodes = $dureeDesPeriodes + $dureeDeLaPeriode;
			if ($this->debug == true) {
				echo "Nouvelle durée total : $dureeDesPeriodes\n";
			}	
		}
		$numeroPeriode ++;
	}
	if ($this->debug) {
		echo "FIN ANALYSE\n";
	}	
	if ($typeAnalyse == 'duree') {
		if ($this->debug) {
            echo "\**** RETURN : Durée des périodes : $dureeDesPeriodes\n";
        }
		return($dureeDesPeriodes);
	} else if ($typeAnalyse == 'tableauPeriodique') {
	    if ($this->debug) {
   	    	echo "\**** RETURN : Tableau des périodes 2\n";
        	print_r($tableauDesPeriodes);
        	echo "\n";
   	 	}
		return($tableauDesPeriodes);	
	} else if ($typeAnalyse == 'dureeEntreCondition') {
		if ($this->debug) {
            echo "\**** RETURN : Durée entre condition : $dureeMaximum\n";
			print_r($tempsPeriode);
        }
		return($tempsPeriode);
	}
}

protected function searchNbRearmements($dateDeb, $dateFin, $idLocalisation, $idRearmement, $condRearmement, $valRearmement, $val2Rearmement, $idArretChaudiere1, $idArretChaudiere2, $idArretBruleur1, $idArretBruleur2, $idPFlamme, $condPFlamme, $valPFlamme, $valPFlamme2, $idPFlammeBif, $condPFlammeBif, $valPFlammeBif, $valPFlamme2Bif) {
	$condPFlamme = $this->transformeCondition($condPFlamme);
	$condPFlammeBif = $this->transformeCondition($condPFlammeBif);
	$nbRearmements = 0;
	$nbMaxRearmement = 0;
	$tabRearmement = array();
	$tabRearmement['debut'] = $dateDeb;
	$tabRearmement['fin'] = $dateFin;
	$tabRearmement['maxRearmement']	= 0;	
	$donnee	= new Donnee();
	// Recherche du message de début de période : Message d'arrêt de la flamme
	$pointeur = $dateDeb;
	$cycleA	= 0;
	// Variables utilisées pour calcul de la moyenne
	$nombre_de_periode = 0;
	$nombre_acquittement = 0;
	while (strtotime($pointeur) < strtotime($dateFin)) {
	    $nombre_de_periode ++;
	    // Recherche d'une erreur de bruleur ou de chaudière
	    //echo "Analyse : $pointeur, $cycleA, $dateFin, $idLocalisation, $idArretChaudiere1, $idArretChaudiere2, $idArretBruleur1, $idArretBruleur2\n";
	    $tableau_debut = $donnee->getTheValueDebutRearmement($this->dbh, $pointeur, $cycleA, $dateFin, $idLocalisation, $idArretChaudiere1, $idArretChaudiere2, $idArretBruleur1, $idArretBruleur2);
	    // Si le message d'erreur est trouvé
	    if (! empty($tableau_debut)) {
            //  Vérification du type de message rencontré (défaut bruleur ou défaut chaudière);
            // En cas d'arrêt de la chaudière : recherche de redémarrage d'un des deux bruleurs
            if ($tableau_debut[0]['module_id'] == $idArretChaudiere1 || $tableau_debut[0]['module_id'] == $idArretChaudiere2) {
	    	    // Recherche du message de fin de période : Présence flamme
	    	    $tableau_fin = $donnee->getTheValueBiModules($this->dbh, $tableau_debut['0']['horodatage'], $tableau_debut['0']['cycle'], $dateFin, $idLocalisation, $idPFlamme, $condPFlamme, $valPFlamme, $valPFlamme2, $idPFlammeBif, $condPFlammeBif, $valPFlammeBif, $valPFlamme2Bif, 'init');
			} elseif ($tableau_debut[0]['module_id'] == $idArretBruleur1) {
		    	// Recherche du message de fin de période : Présence flamme
		    	$tableau_fin = $donnee->getTheValue($this->dbh, $tableau_debut['0']['horodatage'], $tableau_debut['0']['cycle'], $dateFin, $idLocalisation, $idPFlamme, $condPFlamme, $valPFlamme, $valPFlamme2, 'init');	
			} else {
		    	$tableau_fin = $donnee->getTheValue($this->dbh, $tableau_debut['0']['horodatage'], $tableau_debut['0']['cycle'], $dateFin, $idLocalisation, $idPFlammeBif, $condPFlammeBif, $valPFlammeBif, $valPFlamme2Bif, 'init');
			}
	    	if (! empty($tableau_fin)) {
	    	    // Recherche du nombre d'occurences du module d'Acquittement entre le début et la fin de la période
	    	    $nbRearmements = $this->occurencesSurPeriode($tableau_debut['0']['horodatage'], $tableau_debut['0']['cycle'], $tableau_fin['0']['horodatage'], $tableau_fin['0']['cycle'], $idLocalisation, $idRearmement, $condRearmement, $valRearmement, $val2Rearmement);
		    	// La fin de la période précédente devient le début de la nouvelle période d'analyse
		    	$pointeur = $tableau_fin['0']['horodatage'];
		    	$cycleA = $tableau_fin['0']['cycle'];
                // Si le nombre d'occurences est le nombre max rencontré jusqu'à présent : Définition des nouvelles données du tableau de réarmement
		    	if ($nbRearmements > $nbMaxRearmement) {
					$nbMaxRearmement = $nbRearmements;
					$tabRearmement['debut'] = $tableau_debut['0']['horodatage'];
					$tabRearmement['fin'] = $tableau_fin['0']['horodatage'];
					$tabRearmement['maxRearmement']	= $nbMaxRearmement;
		    	}
	    	} else {
		    	// Si pas de présence flamme rencontré : La fin de la période correspond à la fin de la période de recherche
		    	$nbRearmements	= $this->occurencesSurPeriode($tableau_debut['0']['horodatage'], $tableau_debut['0']['cycle'], $dateFin, 0, $idLocalisation, $idRearmement, $condRearmement, $valRearmement, $val2Rearmement);
                if ($nbRearmements > $nbMaxRearmement) {
                    $nbMaxRearmement = $nbRearmements;
                    $tabRearmement['debut'] = $tableau_debut['0']['horodatage'];
                    $tabRearmement['fin'] = $dateFin;
                    $tabRearmement['maxRearmement'] = $nbMaxRearmement;
                }
		    	$pointeur = $dateFin;
		    	$cycleA = 0;
	    	}
			$nombre_acquittement += $nbRearmements;
	    } else {
			// Si pas de message d'arrêt de flamme rencontré : Fin des recherches
			$pointeur = $dateFin;
			$cycle = 0;
	    }
	}
	$tabRearmement['moyenne'] = round($nombre_acquittement / $nombre_de_periode, 2);
	return($tabRearmement);
}


private function transformeCondition($condition) {
	switch (strtolower($condition)) {
	    case 'int':
			return('<>');
			break;
	    case 'inf':
			return('<');
	    	break;
	    case 'sup':
			return('>');
			break;
	}
	return($condition);
}
    


// Fonction qui vérifie qu'une valeur est dans la condition définie
protected function isInCondition($valeur, $condA, $valCondition, $val2Condition) {
	if ($this->debug == true) {
		echo "Analyse de condition : $valeur $condA $valCondition ($val2Condition)\n";
	}
	switch ($condA) {
	    case '=':
	        $retour = ($valeur == $valCondition)?true:false;
			break;
	    case '>':
			$retour = ($valeur > $valCondition)?true:false;
            break;
        case '<':
            $retour = ($valeur < $valCondition)?true:false;
            break;
	    case '>=':
            $retour = ($valeur >= $valCondition)?true:false;
            break;
        case '<=':
            $retour = ($valeur <= $valCondition)?true:false;
            break;
	    case '<>':
			$retour = ($valeur >= $valCondition && $valeur <= $val2Condition)?true:false;
            break;
	}
	return $retour;
}


//  Définition
//     Retourne un objet DateInterval : La plus grande période parmis les différentes périodes satisfaisant la condition sur la période définie
//	Ecart max entre 2 périodes
//  Paramètres
//      dateDeb : Date de début de période
//      cycleB  : Cycle à la date de début de période
//      dateFin : Date de fin de période
//      idLB    : Identifiant de la localisation du module
//      idB     : Identifiant du module devant satisfaire la condition
//      condB   : Condition de restriction de recherche
//      valB    : Valeur de la condition de restriction si elle est différente de null
//      valB2   : Valeur2 de la condition de restriction si elle est <>
public function getMaxDuration($dateDeb, $cycleB, $dateFin, $idLB, $idB, $condB, $valB, $valB2) {
	switch ($condB) {
	    case 'Int':
			$condB = '<>';
			break;
	    case 'Inf':
			$condB = '<';
			break;
	    case 'Sup':
			$condB = '>';
			break;
	}
    if ($this->debug == true) {
        echo "Fonction getMaxDuration : \n";
		echo "$dateDeb, $cycleB, $dateFin, $idLB, $idB, $condB, $valB, $valB2\n";
    }
	return($this->analysePeriode('dureeEntreCondition', 'none', $idLB, $idB, $cycleB, $dateDeb, $dateFin, $condB, $valB, $valB2)); 
}

// Fonction privée		
// Définition
//		Fonction retournant la synthèse du tableau des périodes passée en paramètre
//	Paramètres
//		dateDeb 	: Date de début de période		
//		dateFin 	: Date de fin de période
//		tabloATraiter	: Tableau des données à analyser
//		typeComparaison	: Type de comparaison demandée (compare ou comptage)
//		Addition des temps + Calcul des pourcentages pour le service [comparaisonDeModules]
protected function getTraitementTabPeriode($dateDeb, $dateFin, $tabloATraiter, $typeComparaison, $debug) {
	$messageModuleComptage = null;
	$uniteModuleComptage = null;
    $tempsAnalyse = null;
    $tmpTempsAnalyseDate1 = new \Datetime($dateDeb);
   	$tmpTempsAnalyseDate2 = new \Datetime($dateFin);
    $tempsTotalA = null;
    $tempsTotalB = null;
	$tempsTotalCompare = null;
    $pourcentageBA = null;
	$sommeSeconds = 0;
	$sommeValeurTotale = 0;
	$valeurDebA	= null;
	$valeurFinA	= null;
	$valeurDebB	= null;
	$valeurFinB	= null;
    $tabRetour = array();
	$nombreDeColonnes = count($tabloATraiter) - 1;
	$typeComparaison= strtolower($typeComparaison);
	// Parcours de chaque période du tableau d'analyse
    foreach ($tabloATraiter as $colonne => $parametre) {
	    // Addition des durées des périodes du module1
        // Si un des paramètres  : $parametre['temps'] ou $parametre['dureeModuleB'] est null c'est qu'aucune donnée n'est trouvée
        if ($parametre['temps'] != null) {
			$tempsTotalA = $this->addDuree($tempsTotalA, $parametre['tempsTimestamp']);
			if ($colonne == 0) {
		    	$valeurDebA	= $parametre['valeurDeb'];
			} elseif ($colonne == $nombreDeColonnes) {
		    	$valeurFinA	= $parametre['valeurFin'];
			}
        }
		/*
		if ($debug == true) {
			echo "\nTemps AA :  ajout de \n";
			print_r($parametre['temps']);
			echo "Nouvelle durée de AA : $tempsTotalA\n";
		}
		*/
	    // Si le type de comparaison est compare ou All : Addition des durées des périodes du module2
	    if (($typeComparaison == 'compare') || ($typeComparaison == 'all')) {
          	if ($parametre['dureeModuleB'] != null) {
				$tempsTotalB = $this->addDuree($tempsTotalB, $parametre['dureeModuleB']);
		 		if ($debug == true) {
                	echo "\nModification tempsTotalB  ajout de : ".$parametre['dureeModuleB']."\n";;
					echo "Nouvelle durée B : $tempsTotalB\n";
				}
            }
	    }
	    // Si le type de comparaison est comptage ou All : 
	    // Calcul de la somme des périodes d'analyse / de la sommes de valeurs / des moyennes horaires et journalières
	    // Inclue -> Comptage et Both
	    if (($typeComparaison == 'comptage') || ($typeComparaison == 'all')) {
			// Valeur de départ
			if ($colonne == 0) {
		    	$valeurDebB = $parametre['comptageModuleB']['comptageValeurDeb']; 
		    	$messageModuleComptage = $parametre['comptageModuleB']['comptageMessage'];
		    	$uniteModuleComptage = $parametre['comptageModuleB']['comptageUnite'];
			}
			if ($colonne == $nombreDeColonnes) {
		    	$valeurFinB	= $parametre['comptageModuleB']['comptageValeurFin'];
			}
			$sommeSeconds = $sommeSeconds + $parametre['comptageModuleB']['comptageSecondsPeriode'];
			$sommeValeurTotale = $sommeValeurTotale + $parametre['comptageModuleB']['comptageTotalValeur'];
			$tempsTotalCompare = $this->addDuree($tempsTotalCompare, $parametre['comptageModuleB']['comptageTempsPeriode']);
	    }
    }

	$tempsTotalPeriode = $tmpTempsAnalyseDate2->getTimestamp() - $tmpTempsAnalyseDate1->getTimestamp();
	$tabRetour['valeurDebut'] = $valeurDebA;
	$tabRetour['valeurFin']	= $valeurFinA;
	$pourcentageA = $tempsTotalA * 100 / $tempsTotalPeriode;
	if (($typeComparaison == 'compare') || ($typeComparaison == 'all')) {
		$pourcentageB = $tempsTotalB * 100 / $tempsTotalPeriode;
		$pourcentageBA = ($tempsTotalA != 0)?$tempsTotalB* 100 / $tempsTotalA:0;
	    $tabRetour['tempsTotalB'] = $tempsTotalB;
	    $tabRetour['pourcentageB'] = $pourcentageB;
        $tabRetour['pourcentageBA'] = $pourcentageBA;
		$tabRetour['secondsB'] = $tempsTotalB;
	}
    if (($typeComparaison == 'comptage') || ($typeComparaison == 'all')) {
	    $tabRetour['compareTotal'] = $sommeValeurTotale;
	    $tabRetour['compareDebut'] = $valeurDebB;
	    $tabRetour['compareFin'] = $valeurFinB;
	    // Comptage des compteurs (ex compteur d'eau)
	    $tabRetour['compareComptage'] = $valeurFinB - $valeurDebB;
	    // Moyenne par heure et par jour en fonction de la période d'analyse
		$tabRetour['compareMoyenneHeureComptage']= ($tempsTotalPeriode != 0)?$tabRetour['compareComptage'] * 3600 / $tempsTotalPeriode:0;
        $tabRetour['compareMoyenneJourComptage']= ($tempsTotalPeriode != 0)?$tabRetour['compareComptage'] * 86400 / $tempsTotalPeriode:0;
	    // Moyenne par heure et par jour en fonction de la période du module 1
		$tabRetour['compareMoyenneHeureComptageAB']=($tempsTotalA != 0)?$tabRetour['compareComptage'] * 3600 / $tempsTotalA:0;
        $tabRetour['compareMoyenneJourComptageAB']= ($tempsTotalA != 0)?$tabRetour['compareComptage'] * 86400 / $tempsTotalA:0;
	    $tabRetour['messageModuleComptage'] = $messageModuleComptage;
	    $tabRetour['uniteModuleComptage'] = $uniteModuleComptage;
	    $tabRetour['compareTempsTotal']	= $tempsTotalCompare;
	    $tabRetour['compareSeconds'] = $tempsTotalCompare;
        // Moyenne par heure
        $sommeMoyenneHeure = ($secondsTotalCompare != 0)?$sommeValeurTotale * 3600 / $secondsTotalCompare:0;
        $sommeMoyenneJour = ($secondsTotalCompare != 0)?$sommeValeurTotale * 86400 / $secondsTotalCompare:0;
        $tabRetour['compareMoyenneHeure'] = $sommeMoyenneHeure;
        $tabRetour['compareMoyenneJour'] = $sommeMoyenneJour;
    }

    $tabRetour['tempsPeriode'] = $tempsTotalPeriode;
	$tabRetour['secondsPeriode'] = $tempsTotalPeriode;
    $tabRetour['tempsTotalA'] = $tempsTotalA;
	$tabRetour['secondsA'] = $tempsTotalA;
    $tabRetour['pourcentageA'] = $pourcentageA;
    return($tabRetour);
}


//  FONCTION C
//	@tab : 	Retourne un tableau contenant
//		comptageMessage 	: L'intitulé du message du module
//		comptageUnite		: L'unité du message du module
//		comptageValeurDeb	: La valeur de début de période
//		comptageTempsPeriode	: La durée de la période d'analyse au format DateInterval
//		comptageSecondsPeriode	: La durée de la période d'analyse au format Secondes
//		comptageTotalValeur	: La somme des valeurs sur la période
//		comptageMoyenneHeure	: Le calcul de la moyenne par heure sur la période
//		comptageMoyenneJour	: Le calcul de la moyenne par jour sur la période
//		comptageValeurFin	: La valeur de fin de période
//	Paramètres:
//		dateDeb : Date de début de période
//		dateFin : Date de fin de période
//		idLA	: Identifiant de la localisation du module
//		idA		: Identifiant du module
//      condA   : Condition de restriction de recherche
//      valA    : Valeur de la condition de restriction si elle est différente de null
//      valA2   : Valeur2 de la condition de restriction si elle est <>
public function analyseModuleCompteur($dateDeb, $dateFin, $idLA, $idA, $condA, $valA, $valA2) {
	// Récupération de la valeur de départ
    // Recherche de la date minimum autorisée pour les recherches
	$tabRetour = array();
	// Message du module
	$module = $this->em->getRepository('IpcProgBundle:Module')->find($idA);
    $tabRetour['comptageMessage'] = $module->getMessage();
    $tabRetour['comptageUnite'] = $module->getUnite();
	$donnee = new Donnee();
    // Recherche de la valeur à la date de début de période pour le module 
    // Recherche de la date après laquelle une donnée du module est trouvée
    $tmp_datedebut = $this->serviceConfiguration->rechercheLastValue($idA, $idLA, $dateDeb, $this->limitFirstDate);
	// Récupération de la valeur
	// Mis en commentaire le 04-12-2017
    //$valeurdebA =  $donnee->sqlLastGetValeur($this->dbh, $this->limitFirstDate, $dateDeb, $idA, $idLA);
	$valeurdebA =  $donnee->sqlLastGetValeur($this->dbh, $tmp_datedebut, $this->addADay($tmp_datedebut), $idA, $idLA);
    // Si aucune valeur n'est trouvée, la valeur est définie à 0
	$valeurdebA	= ($valeurdebA != '')?$valeurdebA:0;
	$tabRetour['comptageValeurDeb']	= $valeurdebA;
	// Récupération du temps de la période d'analyse
    $tmpTempsPeriodeDate1 = new \Datetime($dateDeb);
    $tmpTempsPeriodeDate2 = new \Datetime($dateFin);
    $tempsPeriode = $tmpTempsPeriodeDate1->diff($tmpTempsPeriodeDate2);
	$secondsPeriode	= date_create('@0')->add($tempsPeriode)->getTimestamp();
	$tabRetour['comptageTempsPeriode'] = $tempsPeriode;
	$tabRetour['comptageSecondsPeriode'] = $secondsPeriode;
	// Comptage sur la période
	$calculSurPeriode = $this->comptageSurPeriode($dateDeb, $dateFin, 'somme', $idLA, $idA, $condA, $valA, $valA2);
	$tabRetour['comptageTotalValeur'] = $calculSurPeriode;
	// Moyenne par heure
	$moyenneHeure = $calculSurPeriode * 3600 / $secondsPeriode;
	$moyenneJour = $calculSurPeriode * 86400 / $secondsPeriode;
	$tabRetour['comptageMoyenneHeure'] = $moyenneHeure;
	$tabRetour['comptageMoyenneJour'] = $moyenneJour;
	// Calcul de la valeur de fin
	$valeurfinA	= $donnee->sqlLastGetValeur($this->dbh, $this->limitFirstDate, $dateFin, $idA, $idLA);
	$valeurfinA	= ($valeurfinA != '')?$valeurfinA:0;
	$tabRetour['comptageValeurFin']	= $valeurfinA;
    // Calcul des moyennes sur la période du comptage
    $tabRetour['comptageDiffValeur'] = $valeurfinA - $valeurdebA;
    $tabRetour['comptageDiffMoyenneHeure'] = ($secondsPeriode != 0)?$tabRetour['comptageDiffValeur'] * 3600 / $secondsPeriode:0;
    $tabRetour['comptageDiffMoyenneJour'] = ($secondsPeriode != 0)?$tabRetour['comptageDiffValeur'] * 86400 / $secondsPeriode:0;
	return($tabRetour);
}



public function analyseModuleTest($dateDeb, $dateFin, $idLA, $idA, $condA, $valA, $valA2) {
    $tabRetour = array();
    // Message du module
    $module = $this->em->getRepository('IpcProgBundle:Module')->find($idA);
    $tabRetour['testMessage'] = $module->getMessage();
	$tabRetour['testNombre'] = $this->occurencesSurPeriode($dateDeb, 0, $dateFin, 0, $idLA, $idA, $condA, $valA, $valA2);
	return($tabRetour);
}



public function analyseModuleForcage($dateDeb, $dateFin, $idLA, $idA, $condA, $valA, $valA2) {
    $tabRetour = array();
    // Message du module
    $module = $this->em->getRepository('IpcProgBundle:Module')->find($idA);
    $tabRetour['forcageMessage'] = $module->getMessage();
    $tabRetour['forcageNombre'] = $this->occurencesSurPeriode($dateDeb, 0, $dateFin, 0, $idLA, $idA, $condA, $valA, $valA2);
	return($tabRetour);
}


//	FONCTION D 
//	Définition 
//		@int : Retourne le calcul fait sur la valeur1 d'un module pour une période donnée
//	Paramètres
//		dateDeb : Date de début de période
//		dateFin : Date de fin de période 
//  	calcul	: Calcul parmi somme / moyenne / maximum / minimum
//		idLA	: Identifiant de la localisation
//		idA		: Identifiant du module
//  	condA	: Condition de recherche sur la valeur (>,<,=,!=,<>,>=,<=)
//		valA	: Valeur de la condition de restriction
//		valA2	: Valeur2 de la condition de restriction (utilisée lorsque la condition est <>)
public function comptageSurPeriode($dateDeb, $dateFin, $calcul, $idLA, $idA, $condA, $valA, $valA2) {
	$donnee   = new Donnee();
	$comptage = $donnee->sqlCalculValues1($this->dbh, $dateDeb, $dateFin, $calcul, $idLA, $idA, $condA, $valA, $valA2);
	if ($comptage == '') {
	    $comptage = 0;
	}
	return($comptage);
}


//	FONCTION E
//  Définition
//     @int : Retourne le nombre d'occurences du module dont la valeur1 satisfait la condition 
//  Paramètres
//      dateDeb : Date de début de période
//      dateFin : Date de fin de période
//      idLA    : Identifiant de la localisation
//      idA     : Identifiant du module
//      condA   : Condition de recherche sur la valeur (>,<,=,!=,<>,>=,<=)
//      valA    : Valeur de la condition de restriction
//      valA2   : Valeur2 de la condition de restriction (utilisée lorsque la condition est <>)
public function occurencesSurPeriode($dateDeb, $cycleDeb, $dateFin, $cycleFin, $idLA, $idA, $condA, $valA, $valA2) {
    $donnee = new Donnee();
    $comptage = $donnee->sqlOccurences($this->dbh, $dateDeb, $cycleDeb, $dateFin, $cycleFin, $idLA, $idA, $condA, $valA, $valA2);
    if ($comptage == '') {
        $comptage = 0;
    }
    return($comptage);
}



// Définition
// 		@int : Retourne le calcul fait sur la valeur1 d'un module pour une période donnée
// Paramètres
// 		dateDeb : Date de début de période
// 		dateFin : Date de fin de période
// 		calcul 	: 
// 		idLA	: Identifiant de la localisation
// 		idA 	: Identifiant du module
// 		condA 	: Condition de recherche sur la valeur (>,<,=,!=,<>,>=,<=)
// 		valA 	: Valeur de la condition de restriction
// 		valA2 	: Valeur2 de la condition de restriction (utilisée lorsque la condition est <>)
public function intCalculSurPeriode($dateDeb, $dateFin, $calcul, $idLA, $idA, $condA, $valA, $valA2) {
	if ($calcul == 'moyenne') {
	    $nbPoints = $this->occurencesSurPeriode($dateDeb, 0, $dateFin, 0, $idLA, $idA, $condA, $valA, $valA2);
 	    $totalValeur = $this->comptageSurPeriode($dateDeb, $dateFin, 'somme', $idLA, $idA, $condA, $valA, $valA2);
	    $strRetour = $totalValeur/$nbPoints;
	} else {
	    $strRetour = $this->comptageSurPeriode($dateDeb, $dateFin, $calcul, $idLA, $idA, $condA, $valA, $valA2);
	}
	return($strRetour);
}


// Ajoute une durée à une date : fonctionne pour une durée < 29 jours
protected function addDateTime($timeInit, $timeToAdd) {
	$now = new \DateTime();
	$ref = clone $now;
	if ($timeInit != null) {
	    $now->add($timeInit);
	}
	$now->add($timeToAdd);
	$resultat = $ref->diff($now);
	return($resultat);
}
// Nouvelle version : retourne la durée en secondes
protected function addDateTimestamp($timestampInit, $timestampToAdd) {
	if ($timestampInit != null){
    	return($timestampInit + $timestampToAdd);
	} 
	return $timestampToAdd;
}


// Ajoute une durée à une durée
protected function addDuree($dureeInit, $dureeToAdd) {
	if ($dureeInit == null) {
		$timestampInit = 0;
	} else {
		$timestampInit = $dureeInit;
	}
	$resultat	 = $timestampInit + $dureeToAdd;
    return($resultat);
}


// Fonction qui créée un fichier contenant les calculs sur les valeurs (somme, moyenne, minimum, maximum)
public function getTabCalculPar($periode, $dateDeb, $dateFin, $calcul, $idLA, $idA, $fichier) {
    // Ecriture des valeur du tableau dans le fichier
    $fp = fopen($fichier, 'w');
    // Récupération de la valeur de départ
    // Recherche de la date minimum autorisée pour les recherches
    $tabRetour = array();
    // Message du module
    $module = $this->em->getRepository('IpcProgBundle:Module')->find($idA);
	$titre = ucfirst($calcul)." par $periode;".$module->getMessage()."\n";
	fwrite($fp, $titre);
	$periodeDeRecherche = "Période de recherche;Du $dateDeb au $dateFin\n";
	fwrite($fp, $periodeDeRecherche);
	$moduleDeRecherche = "Unité;".$module->getUnite()."\n";
	fwrite($fp, $moduleDeRecherche);
	$localisationDeRecherche = "Localisation;$idLA\n";
	fwrite($fp, $localisationDeRecherche);
    $entityDonnee = new Donnee();
	$tabDesDonnees = $this->calculPar($periode, $dateDeb, $dateFin, $calcul, $idLA, $idA);
	$dateDeDebut = new \Datetime($dateDeb);
	$dateDeFin = new \Datetime($dateFin);
	$pointeur = $dateDeDebut;
	$key = 0;
	while ($pointeur < $dateDeFin) {
	    // La tableau contient 2 lignes de données pour éviter de saturer la mémoire (Les données sont inscrites dans les fichiers au fur et à mesure)
	    if ($key > 1) {
			array_shift($tabRetour);
			$key = 1;
	    }
	    $donneeExiste = false;
	    // Si la valeur Jour + condition existe dans le tableau => recopie dans le tableau de retour
	    // Sinon recherche de la valeur en base 
	    switch ($periode) {
           	case 'jour':
            	foreach ($tabDesDonnees as $donnee) {
					if ($pointeur->format('Y-m-d') == $donnee['jour']) {
			    		$donneeExiste = true;
			    		break;
					}
		    	}
		    	if ($donneeExiste == true) {
			    	$tabRetour[$key] = array();
			    	$tabRetour[$key]['date'] = $donnee['jour'].' 00:00:00';
			    	$tabRetour[$key]['valeur'] = $donnee['valeur1'];
			    	// Indique que la valeur provient d'un calcul sur les valeurs ( somme / moyenne / maximum / minimum )
			    	// Permet de restreindre le nombre de requêtes en base de données
			    	$tabRetour[$key]['type'] = 'calcul';
        	    } else {
			    	$tabRetour[$key] = array();
			    	$tabRetour[$key]['date'] = $pointeur->format('Y/m/d').' 00:00:00';
			    	// Récupération de la dernière valeur pour la clé 0 du tableau et pour les colonnes dont le type de la colonne-1 est calcul
			    	if ($key == 0 || $tabRetour[$key - 1]['type'] == 'calcul') {
			    		$valeur =  $entityDonnee->sqlLastGetValeur($this->dbh, $this->limitFirstDate, $pointeur->format('Y-m-d H:i:s'), $idA, $idLA);
			    	} else {
						$valeur =  $tabRetour[$key - 1]['valeur'];
			    	}
			    	// Si la valeur est null -> mise à 0 de la valeur
			    	$valeur = ($valeur == '')?0:$valeur;
			    	$tabRetour[$key]['valeur'] = $valeur;
					// Indique que la valeur provient de la recherche de la dernière valeur connue
			    	$tabRetour[$key]['type'] = 'valeur';
            	}
                // Ecriture dans le fichier des valeurs calculées ou recherchées
                $message = $tabRetour[$key]['date'].';'.$tabRetour[$key]['valeur']."\n";
                fwrite($fp, $message);
            	// Ajout de 1 jour
            	$pointeur->add(new \DateInterval('P1D'));
            	break;
          	case 'heure':
		    	foreach ($tabDesDonnees as $donnee) {
					if ($pointeur->format('Y-m-d') == $donnee['jour'] && $pointeur->format('H') == $donnee['heure']) {
			    		$donneeExiste = true;
                        break;
					}
		    	}
		    	if ($donneeExiste == true) {
			    	$tabRetour[$key] = array();
			    	$tabRetour[$key]['date'] = $donnee['jour'].' '.$donnee['heure'].':00:00';
			    	$tabRetour[$key]['valeur'] = $donnee['valeur1'];
			    	$tabRetour[$key]['type'] = 'calcul';
                } else {
                    $tabRetour[$key] = array();
			    	$tabRetour[$key]['date'] = $pointeur->format('Y/m/d H:00:00');
			    	// Récupération de la dernière valeur pour la clé 0 du tableau et pour les colonnes dont le type de la colonne-1 est calcul
                    if ($key == 0 || $tabRetour[$key - 1]['type'] == 'calcul') {
                        $valeur =  $entityDonnee->sqlLastGetValeur($this->dbh, $this->limitFirstDate, $pointeur->format('Y-m-d H:i:s'), $idA, $idLA);
			    	} else {
                        $valeur =  $tabRetour[$key - 1]['valeur'];
                    }
			    	// Si la valeur est null -> mise à 0 de la valeur
                    $valeur = ($valeur == '')?0:$valeur;
                    $tabRetour[$key]['valeur'] = $valeur;
                    $tabRetour[$key]['type'] = 'valeur';    
                }
		    	// Ecriture dans le fichier des valeurs calculées ou recherchées
		    	$message = $tabRetour[$key]['date'].';'.$tabRetour[$key]['valeur']."\n";
		    	fwrite($fp, $message);
		    	// Ajout de 1 heure
		    	$pointeur->add(new \DateInterval('PT1H'));
               	break;
          	case 'minute':
		    	foreach ($tabDesDonnees as $donnee) {
					if ($pointeur->format('Y-m-d') == $donnee['jour'] && $pointeur->format('H') == $donnee['heure'] && $pointeur->format('i') == $donnee['minute']) {
                        $donneeExiste = true;
                        break;
					}
		    	}
		    	if ($donneeExiste == true) {
                    $tabRetour[$key] = array();
			    	$tabRetour[$key]['date'] = $donnee['jour'].' '.$donnee['heure'].':'.$donnee['minute'].':00';
                    $tabRetour[$key]['valeur'] = $donnee['valeur1'];
                    $tabRetour[$key]['type'] = 'calcul';
                } else {
                    $tabRetour[$key] = array();
			    	$tabRetour[$key]['date'] = $pointeur->format('Y/m/d H:i:00');
			    	// Récupération de la dernière valeur pour la clé 0 du tableau et pour les colonnes dont le type de la colonne-1 est calcul
                    if ($key == 0 || $tabRetour[$key-1]['type'] == 'calcul') {
                        $valeur =  $entityDonnee->sqlLastGetValeur($this->dbh,$this->limitFirstDate,$pointeur->format('Y-m-d H:i:s'),$idA,$idLA);
                    } else {
                        $valeur =  $tabRetour[$key-1]['valeur'];
                    }
			    	// Si la valeur est null -> mise à 0 de la valeur
                    $valeur = ($valeur == '')?0:$valeur;
                    $tabRetour[$key]['valeur'] = $valeur;
                    $tabRetour[$key]['type'] = 'valeur';
                }
                // Ecriture dans le fichier des valeurs calculées ou recherchées
                $message = $tabRetour[$key]['date'].';'.$tabRetour[$key]['valeur']."\n";
                fwrite($fp, $message);
		    	// Ajout de 1 minute
		    	$pointeur->add(new \DateInterval('PT1M'));
               	break;
        }
	    // Incrémentation de la clé du tableau final 
	    $key ++;
	}
	// Fermeture du fichier
	fclose($fp);
	return(0);
}

// Fonction qui effectue la différence de deux moyennes
public function calculDifferenceMoyenne($periode, $dateDeb, $dateFin, $idLA, $idA, $idLB, $idB, $fichierA, $fichierB, $fichierDiff) {
	// Création du fichier du calcul pour le module A
	$this->getTabCalculPar($periode, $dateDeb, $dateFin, 'moyenne', $idLA, $idA, $fichierA);
	// Création du fichier du calcul pour le module B	
	$this->getTabCalculPar($periode, $dateDeb, $dateFin, 'moyenne', $idLB, $idB, $fichierB);
	// Pour chaque ligne des fichiers calcul de la différence
	$fp1 = fopen($fichierA, 'r');
	$fp2 = fopen($fichierB, 'r');
	$fpDiff = fopen($fichierDiff, 'w');
	$ligne = 0;
	while (($buffer = fgets($fp1, 4096)) != false) {
	    $tabValeur = explode(';', $buffer);
	    $buffer2 = fgets($fp2, 4096);
	    $tabValeur2	= explode(';', $buffer2);	
	    if ($ligne == 0) {
            $titre = "Différence A-B des moyennes des modules;".trim($tabValeur[1])." et ".trim($tabValeur2[1])."\n";
			fwrite($fpDiff, $titre);
        } elseif ($ligne == 1) {
			$periode = "Période;".$tabValeur[1];
			fwrite($fpDiff, $periode);
	   	} else {
	        $difference = $tabValeur[1]-$tabValeur2[1];
	        $messageDifference = $tabValeur[0].';'.$difference."\n";
	        fwrite($fpDiff, $messageDifference);
	    }
	    $ligne ++;
	}
	fclose($fp1);
	fclose($fp2);
	fclose($fpDiff);
	return(0);
}




// Retournent des tableaux contenant les points moyens / max / min par jour / heure / minute ou seconde
public function calculPar($periode, $dateDeb, $dateFin, $calcul, $idLA, $idA) {
	$donnee = new Donnee();
	switch ($periode) {
	    case "jour":
			return($donnee->getCalculParJour($this->dbh, $dateDeb, $dateFin, $calcul, $idLA, $idA));
			break;
	    case "heure":
			return($donnee->getCalculParHeure($this->dbh, $dateDeb, $dateFin, $calcul, $idLA, $idA));
			break;
	    case "minute":
			return($donnee->getCalculParMinute($this->dbh, $dateDeb, $dateFin, $calcul, $idLA, $idA));
			break;	
	}
}

public function calculParJour($dateDeb, $dateFin, $calcul, $idLA, $idA) {
	$donnee = new Donnee();
	return($donnee->getCalculParJour($this->dbh, $dateDeb, $dateFin, $calcul, $idLA, $idA));
}

public function calculParHeure($dateDeb, $dateFin, $calcul, $idLA, $idA) {
    $donnee = new Donnee();
    return($donnee->getCalcuParHeure($this->dbh, $dateDeb, $dateFin, $calcul, $idLA, $idA));
}


public function calculParMinute($dateDeb, $dateFin, $calcul, $idLA, $idA) {
    $donnee = new Donnee();
    return($donnee->getCalculParMinute($this->dbh, $dateDeb, $dateFin, $calcul, $idLA, $idA));
}



// Fonction de gestion de l'Etat : Analyse de Marche
public function EtatAnalyseDeMarche($action, $entityEtat) {
	// Si l'etat est inactif : Pas d'analyse
	if ($entityEtat->getActive() == false) {
	    return(1);
	}
	$termes_modules_de_test = array();
	$termes_modules_de_test[] = 'testé';
	$termes_modules_forcage	= array();
	$termes_modules_forcage[] = 'forçage';
	// Si l'état doit être calculé sur une période définie : récupération des dates de début et de fin de période définies
	if (preg_match('/^Du/', $entityEtat->getPeriode())) {
	    $tabDate = explode(';', $entityEtat->getPeriode());
	    $dateDeb = $tabDate[1];
	    $dateFin = $tabDate[3]; 
	} else {
	    // Sinon si l'etat doit être calculé selon une fréquence : récupération des dates de début et de fin
	    $tab_periode = explode(';', $entityEtat->getPeriode());
	    // L'analyse ne se fait que si la période d'attente est écoulée : Comparaison de la date du jour avec la date de début de période calulé selon la période
	    // Récupération de la fréquence d'analyse
	    $tab_frequence	= explode('_',$tab_periode[0]);
	    $duree_frequence = $tab_frequence[1];
        $type_frequence	= $tab_frequence[2];
        $en_type_frequence = null;
	    // Récupération du terme anglais pour la fonction php d'incrémentation de date
        switch ($type_frequence) {
        	case 'jour':
        	    $en_type_frequence = 'days';
        	    break;
        	case 'semaine':
        	    $en_type_frequence = 'week';
        	    break;
        	case 'mois':
        	    $en_type_frequence = 'month';
        	    break;
        }
        // Récupération de la période d'analyse
        $tab_periode = explode('_', $tab_periode[1]);
        $duree_periode = $tab_periode[1];
        $type_periode = $tab_periode[2];      //      jour, semaine ou mois
        $en_type_periode = null;
        // Récupération du terme anglais pour la fonction php d'incrémentation de date
        switch ($type_periode) {
            case 'jour':
                $en_type_periode = 'days';
                break;
            case 'semaine':
                $en_type_periode = 'week';
                break;
           	case 'mois':
            	$en_type_periode = 'month';
            	break;
        }
    	// Recherche de la date de début d'analyse
    	// Si il n'y a pas de précédente analyse : On effectue l'analyse depuis la date courante
    	$dateFin = date('Y/m/d H:i:s', strtotime("now"));
    	$dateJour_timestamp = strtotime($dateFin);
    	if ($entityEtat->getDateActivation() == null) {
			$dateFin_timestamp = strtotime($dateFin);
    	} else {
			// Si il y a eu des analyses précédentes : Recherche à partir de la prochaine période
           	// La date de fin d'analyse correspond à la date d'activation + la durée de la fréquence
			$date_fin = $entityEtat->getDateActivationStr();
           	$date_fin_timestamp = strtotime($date_fin);
           	$dateFin = date('Y/m/d H:i:s', strtotime('+'.$duree_frequence.' '.$en_type_frequence, $date_fin_timestamp));
    		// Récupération de la date du jour au format timestamp
    		$dateJour_timestamp	= strtotime("now");
    		$dateFin_timestamp = strtotime($dateFin);
    		// Si la date de début d'analyse est > à la date du jour -> Pas d'analyse
    		if ($dateFin_timestamp > $dateJour_timestamp) {
	    		return(1);
    		}
    	}
    	// La date de fin d'analyse correspond à la date de début + la période
    	$dateDeb = date('Y/m/d H:i:s', strtotime('-'.$duree_periode.' '.$en_type_periode, $dateFin_timestamp));
	}
	// On créé un dossier Etat par date. 
	$dossier_etat = null;
	if (preg_match('/^(.+?)\/(.+?)\/(.+?)$/', substr($dateDeb, 0, 10), $date_dossier_etat) != false) {
		if (preg_match('/^(.+?)\/(.+?)\/(.+?)$/', substr($dateFin, 0, 10), $date_fin_dossier_etat) != false) {
			$dossier_etat = $this->getEtatsDir().'etat_'.$entityEtat->getId().'/'.$date_dossier_etat[1].$date_dossier_etat[2].$date_dossier_etat[3].'_'.$date_fin_dossier_etat[1].$date_fin_dossier_etat[2].$date_fin_dossier_etat[3];
		}	
	} else {
		echo "Erreur dans le service ServiceEtatQueries";
		return new Response();
	}
	// Création du dossier Etat si il n'existe pas
	if (! is_dir($this->getEtatsDir().'etat_'.$entityEtat->getId())) {
		if (! mkdir($this->getEtatsDir().'etat_'.$entityEtat->getId())) {
			echo " - Création du dossier ".$this->getEtatsDir().'etat_'.$entityEtat->getId()." impossible\n";
			return(1);
		}
	}
	echo " - Dossier Etat : $dossier_etat\n";
	if (! is_dir($dossier_etat)) {
	   	if (! mkdir($dossier_etat)) {
			// Impossibilité de créer le dossier
			echo " - Création du dossier ".$dossier_etat." impossible\n";
			return(1);
	   	} else {
			echo " - Création du dossier : $dossier_etat\n";
		}
	}
	$fichier_detat_resume = $dossier_etat.'/resume.csv';
    $fp = @fopen($fichier_detat_resume, "w");
	// Si l'ouverture du fichier n'est pas réussi retour d'un message d'erreur 
	if ($fp == false) {
	   	return(1);
	}
	//	Recherche des modules, des localisations et du mode d'analyse
	$tabModules = explode(';',$entityEtat->getListeModules());
	$foyer = $tabModules[0];
	$tabModuleA	= explode('_',$tabModules[1]);
	$modeA = $tabModuleA[1];
	$this->entity_mode_module1 = $this->em->getRepository('IpcProgBundle:Mode')->find($modeA);
	$idLA = $tabModuleA[2];
	$idA = $tabModuleA[3]; 
	$conditionA	= $tabModuleA[4];
	$tabModuleB	= explode('_',$tabModules[2]);
	$modeB = $tabModuleB[1];
    $idLB = $tabModuleB[2];
    $idB = $tabModuleB[3];
	$conditionB	= $tabModuleB[4];
    // Ecriture du Titre dans le fichier de l'Etat
    $messageFp = 'Titre;'.$entityEtat->getIntitule().';'.$foyer."\n";
    fwrite($fp, $messageFp);
    // Ecriture de la date dans le fichier de l'Etat
    $messageFp = 'Période;'.$dateDeb.';'.$dateFin."\n";
    fwrite($fp, $messageFp);
	if ($foyer == 'bifoyer') {
	   	$tabModuleC = explode('_',$tabModules[3]);
        $modeC = $tabModuleC[1];
        $idLC = $tabModuleC[2];
        $idC = $tabModuleC[3];
        $conditionC = $tabModuleC[4];
	} else {
	  	$idLC = null;
        $idC = null;
        $conditionC = null;
	  	$valeurC1 = null;
	  	$valeurC2 = null;
	   	$condC = null;
	   	$messageC = null;
	}
	$pattern_condition = '/^(\d?)(.+)(\d)$/';
	if (preg_match($pattern_condition, $conditionA, $tabConditionA)) {
	   	$valeurA2 = $tabConditionA[1];
	   	$condA = $tabConditionA[2];
	   	$valeurA1 = $tabConditionA[3];
	} else {
	   	$valeurA2 = null;
        $condA = null;
        $valeurA1 = null;
	}
    if (preg_match($pattern_condition, $conditionB, $tabConditionB)) {
        $valeurB2 = $tabConditionB[1];
        $condB = $tabConditionB[2];
        $valeurB1 = $tabConditionB[3];
    } else {
	  	$valeurB2 = null;
            $condB = null;
            $valeurB1 = null;
	}
	if ($foyer == 'bifoyer') {
    	if (preg_match($pattern_condition, $conditionC, $tabConditionC)) {
           	$valeurC2 = $tabConditionC[1];
           	$condC = $tabConditionC[2];
           	$valeurC1 = $tabConditionC[3];
        } else {
			$valeurC2 = null;
            $condC = null;
            $valeurC1 = null;
    	}
	}
    if ($action == 'creation') {
       /*
              Calculer sur la période :
                -       Temps total
                -       Temps chaudière en marche  + % sur la période
                -       Temps avec présence flamme + % sur la période + % chaudière en marche
                -       Nombre de défauts (ayant provoqué un réarmement) 
                -       Nombre maximum de réarmements successifs avant redémarrage bruleur
				//	Pour cet Etat les modules à comparer sont activés lorsque leur valeur1 vaut 1
        */
    	// Pour la création du rapport Analyse de marche chaudière : 
		// le module Phase d'exploitation de la chaudière doit être > 3
		// le module Came numérique - Présence Flamme doit être = 1
    	$tableauComparaison = array();	
		// Comparaison Chaudière en marche & bruleur 1
    	$tableauComparaison = $this->comparaisonDeModules($dateDeb, $dateFin, $idLA, $idA, $condA, $valeurA1, $valeurA2, $idLB, $idB, $condB, $valeurB1, $valeurB2, 'compare', $this->debug);
    	if ($foyer == 'bifoyer') {
			// Comparaison Chaudière en marche & bruleur 2
			$tableauComparaisonBiFoyer = $this->comparaisonDeModules($dateDeb, $dateFin, $idLA, $idA, $condA, $valeurA1, $valeurA2, $idLC, $idC, $condC, $valeurC1, $valeurC2, 'compare', $this->debug);
			$messageC = $tableauComparaisonBiFoyer['messageModuleB'];
			// Comparaison des temps de fonctionnement commun bruleur 1 & Bruleurs 2
			$tableauComparaisonBruleurBiFoyer = $this->comparaisonDeModules($dateDeb, $dateFin, $idLB, $idB, $condB, $valeurB1, $valeurB2, $idLC, $idC, $condC, $valeurC1, $valeurC2, 'compare', $this->debug); 
    	}
    	$messageA = $tableauComparaison['messageModuleA'];
    	$messageB = $tableauComparaison['messageModuleB'];
    	$messageFp = "Module1 : ".$messageA."\n";
    	$messageFp .= "Module2 : ".$messageB."\n";
    	if ($foyer == 'bifoyer') {
			$messageFp		.= "Module3 : ".$messageC."\n";
    	}
    	fwrite($fp, $messageFp);
    	// Ecriture des temps de fonctionnement dans le fichier de l'Etat
    	$messageFp = "Durée de la période d'analyse;".$this->getPeriodeString($tableauComparaison['tempsPeriode']).';'.$tableauComparaison['secondsPeriode']."\n";
    	fwrite($fp, $messageFp);
    	if ($foyer == 'monofoyer') {
         	$messageFp = "Modules Comparés;".$messageA.';'.$messageB."\n";
    	} else {
	 		$messageFp = "Modules Comparés;".$messageA.';'.$messageB.';'.$messageC."\n";
    	}
    	if (isset($tableauComparaison['tempsTotalA'])) {
			$messageFp = "Durée de fonctionnement du module A;".$this->getPeriodeString($tableauComparaison['tempsTotalA']).';'.round($tableauComparaison['pourcentageA'], 2).';'.$tableauComparaison['secondsA']."\n";
			if (isset($tableauComparaison['tempsTotalB'])) {
	    		$messageFp .= "Durée de fonctionnement du module B;".$this->getPeriodeString($tableauComparaison['tempsTotalB']).';'.round($tableauComparaison['pourcentageB'], 2).';'.round($tableauComparaison['pourcentageBA'], 2).';'.$tableauComparaison['secondsB']."\n";
			} else {
	    		$messageFp .= "Durée de fonctionnement du module B;0;0;0;0\n";
			}
			if ($foyer == 'bifoyer') {
	    		if (isset($tableauComparaisonBiFoyer['tempsTotalB'])) {
                   	$messageFp .= "Durée de fonctionnement du module C;".$this->getPeriodeString($tableauComparaisonBiFoyer['tempsTotalB']).';'.round($tableauComparaisonBiFoyer['pourcentageB'], 2).';'.round($tableauComparaisonBiFoyer['pourcentageBA'], 2).';'.$tableauComparaisonBiFoyer['secondsB']."\n";
                } else {
                   	$messageFp .= "Durée de fonctionnement du module C;0;0;0;0\n";
                }
	    		if (isset($tableauComparaisonBruleurBiFoyer['tempsTotalB'])) {
					$messageFp .= "Durée de fonctionnement commun des bruleurs;".$this->getPeriodeString($tableauComparaisonBruleurBiFoyer['tempsTotalB']).';'.round($tableauComparaisonBruleurBiFoyer['pourcentageB'], 2).';'.round($tableauComparaisonBruleurBiFoyer['pourcentageBA'], 2).';'.$tableauComparaisonBruleurBiFoyer['secondsB']."\n"; 
	    		} else {
					$messageFp .= "Durée de fonctionnement commun des bruleurs;0;0;0;0\n";
	    		}
			}
    	} else {
			$messageFp = "Durée de fonctionnement du module A;0;0;0\n";
			$messageFp .= "Durée de fonctionnement du module B;0;0;0;0\n";	
   			if ($foyer == 'bifoyer') {
	    		$messageFp .= "Durée de fonctionnement du module C;0;0;0;0\n";
	    		$messageFp .= "Durée de fonctionnement commun des bruleurs;0;0;0;0\n";
			}
    	}
        // Récupération de la liste des modules à mettre en titre
        $tab_tmp_exclusion_defauts = explode(';', $entityEtat->getOptionExclusion1());
        $tab_exclusion_defauts = array();
        // Récupération des types d'équipement à exclure
        $idChaudiere1 = null;
        $idChaudiere2 = null;
        $idBruleur1 = null;
        $idBruleur2 = null;
        foreach ($tab_tmp_exclusion_defauts as $defaut_exclu) {
            $typeEquipement = substr($defaut_exclu, 0, 2);
            $tab_exclusion_defauts[]= substr($defaut_exclu, 2);
            switch ($typeEquipement) {
                case 'C1':
                    $idChaudiere1 = substr($defaut_exclu, 2);
                    break;
                case 'C2':
                    $idChaudiere2 = substr($defaut_exclu, 2);
                    break;
                case 'B1':
                    $idBruleur1   = substr($defaut_exclu, 2);
                    break;
                case 'B2':
                    $idBruleur2   = substr($defaut_exclu, 2);
                    break;
            }
        }
    	// Analyse des réarmements
    	$listeModulesAcquittement = $entityEtat->getOption4();
    	if ($listeModulesAcquittement != null) {
    		$tabModulesAcquittement = explode(';', $listeModulesAcquittement);
    		$tabModuleRearmement = explode('_', $tabModulesAcquittement[0]);
    		$idModuleRearmement	= $tabModuleRearmement[0];
    		$condModuleRearmement = $tabModuleRearmement[1];
    		$val1ModuleRearmement = $tabModuleRearmement[2];
    		$val12ModuleRearmement = $tabModuleRearmement[3];
    		if ($idModuleRearmement != null) {
	    		// Réarmement pour les défauts bruleur1
                $maxRearmement = array();
	    		$maxRearmement = $this->searchNbRearmements($dateDeb, $dateFin, $idLA, $idModuleRearmement, $condModuleRearmement, $val1ModuleRearmement, $val12ModuleRearmement, $idChaudiere1, $idChaudiere2, $idBruleur1, $idBruleur2, $idB, $condB, $valeurB1, $valeurB2, $idC, $condC, $valeurC1, $valeurC2);
    	    	$messageFp .= "Nombre maximum de réarmements successifs avant $messageB ou $messageC;".$maxRearmement['maxRearmement'].";".$maxRearmement['debut'].";".$maxRearmement['fin'].';'.$maxRearmement['moyenne']."\n";
	        }
	    }
		if ($this->debug) {
			echo "Ecriture du message: $messageFp\n";
		}
	    fwrite($fp, $messageFp);


	    /*
		Pour chaque valeur de type « compteur » indiquer
		-	Nom de la valeur
		-	Unité
		-	Valeur départ période
		-	Valeur fin de période
		-	Comptage sur la période
		-	Comptage moyen par heure sur la période
		-	Comptage moyen par jour sur la période
		-	Comptage moyen par heure de marche bruleur sur la période
		-	Comptage moyen par jour de marche bruleur sur la période
	    */
	    //	Récupération de la liste des compteurs à analyser : ils sont placés dans l'option1
        // Vide le fichier d'informations sur les compteurs
        if ($this->debug) {
            echo "Debut du calcul des compteurs\n";
        }
		$fichier_detat_compteur = $dossier_etat.'/resumeCompteur.csv';
        $fp_resumeCompteur = fopen($fichier_detat_compteur, 'w');
        fclose($fp_resumeCompteur);
	    if ($entityEtat->getOption1() != false) {
	    	$tabCompteur = explode(';', $entityEtat->getOption1());
	    	foreach ($tabCompteur as $infoModule) {
                // Récupération des informations du module à rechercher
                $tabInfo = explode('_', $infoModule);
                $idModule = $tabInfo[0];
                $condValeur = $tabInfo[1];
                $valeur1 = $tabInfo[2];
                $valeur2 = $tabInfo[3];
	    	    $tabIdModuleA = $this->analyseModuleCompteur($dateDeb, $dateFin, $idLA, $idModule, $condValeur, $valeur1, $valeur2);
		    	// Calcul de la moyenne selon la période du module A
		    	$tabIdModuleA['comptageDiffMoyenneHeureAB']	= ($tableauComparaison['secondsA']!=0)?$tabIdModuleA['comptageDiffValeur'] * 3600 / $tableauComparaison['secondsA']:0;
		    	$tabIdModuleA['comptageDiffMoyenneJourAB'] 	= ($tableauComparaison['secondsA']!=0)?$tabIdModuleA['comptageDiffValeur'] * 86400 / $tableauComparaison['secondsA']:0;
	    	    $fp_resumeCompteur = fopen($fichier_detat_compteur,'a');
	    	    $messageCompteur = $idModule.';'.$this->supprimeCharAfter($this->transformeMessage($tabIdModuleA['comptageMessage']), '=').';'.$tabIdModuleA['comptageUnite'].';'.$tabIdModuleA['comptageValeurDeb'].';'.$tabIdModuleA['comptageValeurFin'].';'.$tabIdModuleA['comptageDiffValeur'].';'.round($tabIdModuleA['comptageDiffMoyenneHeure'], 2).';'.round($tabIdModuleA['comptageDiffMoyenneJour'], 2).';'.round($tabIdModuleA['comptageDiffMoyenneHeureAB'], 2).';'.round($tabIdModuleA['comptageDiffMoyenneJourAB'], 2)."\n";
		    	fwrite($fp_resumeCompteur, $messageCompteur);
	            fclose($fp_resumeCompteur);
		    	/*
				CREATION DE FICHIER POUR LES COURBES
		    	$fichier_detat_heure = $this->getEtatsDir().'/etat_'.$entityEtat->getId().'/graphique_'.$idModule.'_heure.csv';
		    	$fichier_detat_jour = $this->getEtatsDir().'/etat_'.$entityEtat->getId().'/graphique_'.$idModule.'_jour.csv';
	    	    $this->getTabCalculPar('heure',$dateDeb,$dateFin,'moyenne',$idLA,$idModule,$fichier_detat_heure);
	    	    $this->getTabCalculPar('jour',$dateDeb,$dateFin,'moyenne',$idLA,$idModule,$fichier_detat_jour);
		    	*/
	    	}
	    }
        /*
            Pour chaque valeur de type « test » indiquer
            -       Nom du test
            -       Comptage du nombre sur la période
			- 	Périodicité moyenne par rapport au temps Chaudière en marche
        */
        // Récupération de la liste des tests à analyser : ils sont placés dans l'option2
	    // La liste des tests est présentée comme suit : idModule_ConditionRecherche_Valeur1DeLaCondition_Valeur2DeLaCondition;idModule2_ConditionRecherche2_Valeur1DeLaCondition2_Valeur2DeLaCondition2 etc.
        // Vide le fichier d'informations sur les compteurs
        if ($this->debug) {
            echo "Debut du calcul des tests\n";
        }
		$fichier_detat_test = $dossier_etat.'/resumeTest.csv';
        $fp_resumeTest = fopen($fichier_detat_test, 'w');
        fclose($fp_resumeTest);
        if ($entityEtat->getOption2() != false) {
            $tabTest = explode(';', $entityEtat->getOption2());
            foreach ($tabTest as $infoModule) {
		    	// Récupération des informations du module à rechercher
		    	$tabInfo = explode('_', $infoModule);
                if ($this->debug == true) {
                    echo "Test : \n";
                    print_r($tabInfo);
                }
		    	$idModule = $tabInfo[0];
		    	$condValeur	= $tabInfo[1];
		    	$valeur1 = $tabInfo[2];
		    	$valeur2 = $tabInfo[3]; 
				// Récupère le nombre d'occurences sur la période
		    	$tabModuleTest = $this->analyseModuleTest($dateDeb, $dateFin, $idLA, $idModule, $condValeur, $valeur1, $valeur2);
                $fp_resumeTest = fopen($fichier_detat_test, 'a');
				if ($tabModuleTest['testNombre'] > 1) {
		    		$dureePeriodeMax = $this->getMaxDuration($dateDeb, 0, $dateFin, $idLA, $idModule, $condValeur, $valeur1, $valeur2);
				} else {
					$dureePeriodeMax = '-';
				}
		    	if ($dureePeriodeMax != null) {
				if ($dureePeriodeMax == '-') {
                                        $messageTest = $idModule.';'.$this->transformeMessage($tabModuleTest['testMessage']).';'.$tabModuleTest['testNombre'].";-\n";
                                } else {
		        	$messageTest = $idModule.';'.$this->transformeMessage($tabModuleTest['testMessage']).';'.$tabModuleTest['testNombre'].';'.$dureePeriodeMax->format('%m mois %d jour(s) %h heure(s) %i minute(s) %s seconde(s)')."\n";
				}
		    	} else {
			 		$messageTest = $idModule.';'.$this->transformeMessage($tabModuleTest['testMessage']).';'.$tabModuleTest['testNombre'].';Aucun test trouvé sur la période'."\n";
		    	}
                fwrite($fp_resumeTest, $messageTest);
                fclose($fp_resumeTest);
            }
        }
        /*
            Pour chaque valeur de type « forçage » indiquer
            -       Nom du test
            -       Comptage du nombre sur la période
        */
        // Récupération de la liste des forçages à analyser : ils sont placés dans l'option3
        // Vide le fichier d'informations sur les compteurs
        if ($this->debug) {
            echo "Debut du calcul des forcages\n";
        }
		$fichier_detat_forcage = $dossier_etat.'/resumeForcage.csv';
        $fp_resumeForcage = fopen($fichier_detat_forcage, 'w');
        fclose($fp_resumeForcage);
        if ($entityEtat->getOption3() != false) {
            $tabForcage = explode(';', $entityEtat->getOption3());
            foreach ($tabForcage as $infoModule) {
                // Récupération des informations du module à rechercher
                $tabInfo = explode('_', $infoModule);
                $idModule = $tabInfo[0];
                $condValeur = $tabInfo[1];
                $valeur1 = $tabInfo[2];
                $valeur2 = $tabInfo[3];
                $tabModuleForcage = $this->analyseModuleForcage($dateDeb, $dateFin, $idLA, $idModule, null, null, null);
                $fp_resumeForcage = fopen($fichier_detat_forcage, 'a');
                $messageForcage = $idModule.';'.$this->transformeMessage($tabModuleForcage['forcageMessage']).';'.$tabModuleForcage['forcageNombre']."\n";
                fwrite($fp_resumeForcage, $messageForcage);
                fclose($fp_resumeForcage);
            }
        }

        /*
            Pour chaque valeur de type « combustible » indiquer
            -       Durée de fonctionnement du bruleur 1 ( et du bruleur 2 pour les chaudières bi foyer)
            -       Durée de fonctionnement du combustible pour chaque période de fonctionnement du/des bruleur(s)
        */
        // Récupération de la liste des modules de combustible à analyser : ils sont placés dans l'option5 et l'option6 pour les bifoyer
		// Analyse des utilisations de combustibles du brûleur 2
        if ($this->debug) {
            echo "Debut du calcul des combustibles\n";
        }
		$fichier_detat_combustible = $dossier_etat.'/resumeCombustibleB1.csv';
        $fp_resumeCombustible = fopen($fichier_detat_combustible, 'w');
		$messageFc = "Période de recherche;Du ".$this->reverseDate($dateDeb, 'php').' au '.$this->reverseDate($dateFin, 'php')."\n";
		fwrite($fp_resumeCombustible, $messageFc);
        fclose($fp_resumeCombustible);
        if ($entityEtat->getOption5() != false) {
			$compteur = 0;
            $tabCombustible = explode(';', $entityEtat->getOption5());
            foreach ($tabCombustible as $infoModule) {
				$compteur ++;
				$fp_resumeCombustible = fopen($fichier_detat_combustible, 'a');
                // Récupération des informations du module à rechercher
                $tabInfo = explode('_', $infoModule);
                $idModule = $tabInfo[0];
                $condValeur = $tabInfo[1];
                $valeur1 = $tabInfo[2];
                $valeur2 = $tabInfo[3];
				$tableauComparaisonCombustibleB1 = $this->comparaisonDeModules($dateDeb, $dateFin, $idLB, $idB, $condB, $valeurB1, $valeurB2, $idLA, $idModule, '=', '1', null, 'compare', false);
				if ($compteur == 1) {
            		$messageFp = "ModuleBruleur1;".$tableauComparaisonCombustibleB1['messageModuleA']."\n";
			 		$messageFp .= "Durée de la période d'analyse B1;".$this->getPeriodeString($tableauComparaisonCombustibleB1['tempsPeriode']).';'.$tableauComparaisonCombustibleB1['secondsPeriode']."\n";
					if (isset($tableauComparaisonCombustibleB1['tempsTotalA'])) {
						$messageFp .= "Durée de fonctionnement du bruleur 1;".$this->getPeriodeString($tableauComparaisonCombustibleB1['tempsTotalA']).';'.round($tableauComparaisonCombustibleB1['pourcentageA'], 2).';'.$tableauComparaisonCombustibleB1['secondsA']."\n";
					}
					$messageFp .= "Nouveau combustible\n";
				} else {
					$messageFp = "Nouveau combustible\n";
				}
        		$messageFp .= "ModuleCombustible;".$this->transformeMessage($tableauComparaisonCombustibleB1['messageModuleB'], 'dollar', '1')."\n";
				// Si le bruleur B1 a fonctionné sur la période: Analyse du temps de fonctionnement du combustible.
				if (isset($tableauComparaisonCombustibleB1['tempsTotalB'])) {
					$messageFp .= "Durée de fonctionnement du module combustible sur période bruleur 1;".$this->getPeriodeString($tableauComparaisonCombustibleB1['tempsTotalB']).';'.round($tableauComparaisonCombustibleB1['pourcentageB'], 2).';'.round($tableauComparaisonCombustibleB1['pourcentageBA'], 2).';'.$tableauComparaisonCombustibleB1['secondsB']."\n";
       		} else {
        	    $messageFp .= "Durée de fonctionnement du module combustible sur période bruleur 1;0;0;0;0;0\n";
        	}
			fwrite($fp_resumeCombustible, $messageFp);
        	fclose($fp_resumeCombustible);
        	}
    	}

	// Analyse des utilisations de combustibles du brûleur 2
	$fichier_detat_combustible = $dossier_etat.'/resumeCombustibleB2.csv';

    $fp_resumeCombustible = fopen($fichier_detat_combustible, 'w');
    $messageFc = "Période de recherche;Du ".$this->reverseDate($dateDeb, 'php').' au '.$this->reverseDate($dateFin, 'php')."\n";
    fwrite($fp_resumeCombustible, $messageFc);
    fclose($fp_resumeCombustible);
    if ($entityEtat->getOption6() != false) {
		$compteur = 0;
        $tabCombustible = explode(';', $entityEtat->getOption6());
        foreach ($tabCombustible as $infoModule) {
			$compteur ++;
            $fp_resumeCombustible = fopen($fichier_detat_combustible, 'a');
            // Récupération des informations du module à rechercher
            $tabInfo = explode('_', $infoModule);
            $idModule = $tabInfo[0];
            $condValeur = $tabInfo[1];
            $valeur1 = $tabInfo[2];
            $valeur2 = $tabInfo[3];
            $tableauComparaisonCombustibleB2 = $this->comparaisonDeModules($dateDeb, $dateFin, $idLC, $idC, $condC, $valeurC1, $valeurC2, $idLA, $idModule, '=', '1', null, 'compare', false);
			if ($compteur == 1){
                $messageFp = "ModuleBruleur2;".$tableauComparaisonCombustibleB2['messageModuleA']."\n";
				$messageFp .= "Durée de la période d'analyse;".$this->getPeriodeString($tableauComparaisonCombustibleB2['tempsPeriode']).';'.$tableauComparaisonCombustibleB2['secondsPeriode']."\n";
               	if (isset($tableauComparaisonCombustibleB2['tempsTotalA'])) {
					$messageFp .= "Durée de fonctionnement du bruleur 2;".$this->getPeriodeString($tableauComparaisonCombustibleB2['tempsTotalA']).';'.round($tableauComparaisonCombustibleB2['pourcentageA'], 2).';'.$tableauComparaisonCombustibleB2['secondsA']."\n";
				}
				$messageFp .= "Nouveau combustible\n";
			} else {
				$messageFp = "Nouveau combustible\n";
			}
            $messageFp .= "ModuleCombustible;".$this->transformeMessage($tableauComparaisonCombustibleB2['messageModuleB'], 'dollar', '1')."\n";
            // Si le bruleur B2 a fonctionné sur la période: Analyse du temps de fonctionnement du combustible.
            if (isset($tableauComparaisonCombustibleB2['tempsTotalB'])) {
                $messageFp .= "Durée de fonctionnement du module combustible sur période bruleur 2;".$this->getPeriodeString($tableauComparaisonCombustibleB2['tempsTotalB']).';'.round($tableauComparaisonCombustibleB2['pourcentageB'], 2).';'.round($tableauComparaisonCombustibleB2['pourcentageBA'], 2).';'.$tableauComparaisonCombustibleB2['secondsB']."\n";
            } else {
                $messageFp .= "Durée de fonctionnement du module combustible sur période bruleur 2;0;0;0;0;0\n";
            }
            fwrite($fp_resumeCombustible, $messageFp);
            fclose($fp_resumeCombustible);
        }
    }
    $nb_defaut1_cause_chaudiere = 0;  	
    $nb_defaut2_cause_chaudiere = 0;
    $donnee = new Donnee();
    //	Récupération du nombre de défauts1 dûs à un défaut chaudière : cad lorsque le bruleur et la chaudière sont en erreur au même cycle de la même heure
    if ($idChaudiere1 && $idBruleur1) {
        $nb_defaut1_cause_chaudiere += intVal($donnee->sqlCountSameHorodatage($this->dbh, $dateDeb, $dateFin, "$idChaudiere1,$idBruleur1", $idLA, '=', 1, null, 'valeur1'));
    }
    if ($idChaudiere2 && $idBruleur1) {
        $nb_defaut1_cause_chaudiere += intVal($donnee->sqlCountSameHorodatage($this->dbh, $dateDeb, $dateFin, "$idChaudiere2,$idBruleur1", $idLA, '=', 1, null, 'valeur1'));
    }
    // Récupération du nombre de défauts2 dûs à un défaut chaudière
    if ($idChaudiere1 && $idBruleur2) {
        $nb_defaut2_cause_chaudiere += intVal($donnee->sqlCountSameHorodatage($this->dbh, $dateDeb, $dateFin, "$idChaudiere1,$idBruleur2", $idLA, '=', 1, null, 'valeur1'));
    }
    if ($idChaudiere2 && $idBruleur2) {
        $nb_defaut2_cause_chaudiere += intVal($donnee->sqlCountSameHorodatage($this->dbh, $dateDeb, $dateFin, "$idChaudiere2,$idBruleur2", $idLA, '=', 1, null, 'valeur1'));
    }


    /*
	Classer les 14 défauts revenus le plus souvent sur la période	( 10 défauts + 4 que l'on rencontre toujours )
	- Nom de la valeur
	- Comptage sur la période
    */
    // Nombre de message de type défaut :
    if ($this->debug) {
        echo "Debut du calcul des défauts\n";
    }
    $nb_messages_de_defaut = 0;
    $tabMostDefaut = array();
    $entityGenreDefaut = $this->em->getRepository('IpcProgBundle:Genre')->findOneByIntituleGenre('Défaut');
    $tempo_nombreMessages = 14;	//	10 + 4 messages potentiellement récurrents
    // Récupération des 14 plus fréquents messages de type défaut
    $tabMostDefaut = $this->getMostMessages($dateDeb, $dateFin, $idLA, 'genre', $entityGenreDefaut->getId(), null, $tempo_nombreMessages, '=', 1, null, null, null, null);
    // Récupération des modules de type défaut de la Came numérique 1
    // Suppression car non explication : $tabCameNumerique = $this->getMostMessagesCame($dateDeb, $dateFin, $idLA, $tempo_nombreMessages, '=', 1, null, '=', 1, null, 1);
    // Le nombre de message de défaut correspond au nombre de message de défaut récupéré - le nombre de messages du type titre (récurrents)
    $nb_messages_de_defaut += $tabMostDefaut['nbOccurences'];
    // Suppression car non explication : $nb_messages_de_defaut += $tabCameNumerique['nbOccurences'];
    $tab_defauts_titre = array();
    $tab_defauts_classe = array();
    $tab_tri = array();
    $numDefaut = 0;
    foreach ($tabMostDefaut['messages'] as $key => $message) {
        if (in_array($message['id'],$tab_exclusion_defauts)) {
	    	// Soustraction du nombre de défauts causés par un défaut chaudière aux messages de type 'défaut bruleur'
	    	switch ($message['id']) {
				case $idBruleur1 :
		    		$nbDeMessageDefautRecurrent = intVal($message['nbMessages']) - $nb_defaut1_cause_chaudiere; 
		    		break;
	  			case $idBruleur2 :
		    		$nbDeMessageDefautRecurrent = intVal($message['nbMessages']) - $nb_defaut2_cause_chaudiere;
                    break;
				default:
		    		$nbDeMessageDefautRecurrent = intVal($message['nbMessages']);
		    		break;
	    	}
	    	$nb_messages_de_defaut -= intVal($message['nbMessages']);
            $tab_defauts_titre[] = 'T;'.$message['message'].';'.$nbDeMessageDefautRecurrent;
        } else {
            $tab_defauts_classe[$numDefaut] = array();
	    	$tab_defauts_classe[$numDefaut]['message'] = $message['message'];
	    	$tab_defauts_classe[$numDefaut]['nbMessages'] = $message['nbMessages'];
        }
	    $numDefaut ++;
    }
	//print_r($tab_defauts_classe);
	// Suppression car non explication : 
	/*
    foreach ($tabCameNumerique['messages'] as $key => $message) {
		$tab_defauts_classe[$numDefaut] = array();
        $tab_defauts_classe[$numDefaut]['message'] = $message['message'];
        $tab_defauts_classe[$numDefaut]['nbMessages'] = $message['nbMessages'];
		$numDefaut ++;
    }
    if ($foyer == 'bifoyer') {
		// Récupération des modules de type défaut de la Came numérique 2
       	$tabCameNumerique2 = $this->getMostMessagesCame($dateDeb, $dateFin, $idLA, $tempo_nombreMessages, '=', 1, null, '=', 2, null, 2);
      	$nb_messages_de_defaut += $tabCameNumerique2['nbOccurences'];
		foreach ($tabCameNumerique2['messages'] as $key => $message) {
            $tab_defauts_classe[$numDefaut] = array();
            $tab_defauts_classe[$numDefaut]['message'] = $message['message'];
            $tab_defauts_classe[$numDefaut]['nbMessages'] = $message['nbMessages'];
            $numDefaut ++;
        }
    } 
	*/
    // Tri du tableau selon le nombre d'occurences
        $tabOccurences = array();
        foreach ($tab_defauts_classe as $key => $row) {
            $tabOccurences[$key] = $row['nbMessages'];
        }
        array_multisort($tabOccurences, SORT_DESC, $tab_defauts_classe);
	    $tab_defauts_message = array();
        foreach ($tab_defauts_classe as $key => $message) {
            $tab_defauts_message[] = $message['message'].';'.$message['nbMessages'];
            if (count($tab_defauts_message) == 10) {
                break;
            }
        }
	    // Nombre de messages récurrents : 
	    $nb_messages_titre = count($tab_defauts_titre);
	    // Nombre de messages n'étant pas récurrents
	    $nb_message_defauts	= $tabMostDefaut['nombre'] + $nb_messages_titre;
		$fichier_detat_mostdefaut = $dossier_etat.'/mostDefauts.csv';
        $fpdefauts = fopen($fichier_detat_mostdefaut, "w");
	    $nombreDeMessages = $tabMostDefaut['nombre'] - $nb_messages_titre;
	    $messageFpDefaut = "titre;10 messages de genre ".$tabMostDefaut['intituleType']." les plus courants;".$nb_messages_de_defaut."\n";
	    fwrite($fpdefauts, $messageFpDefaut);
	    $tab_defauts = array_merge($tab_defauts_titre, $tab_defauts_message);
	    foreach ($tab_defauts as $key => $message_defaut) {
			$messageFpDefaut = $message_defaut."\n";
			fwrite($fpdefauts,$messageFpDefaut);
			if ($key == $nb_message_defauts) {
		    	break;
			}
	    }
	    fclose($fpdefauts);

        if ($this->debug) {
            echo "Debut du calcul des alarmes\n";
        }

	    /*
		Classer les 12 alarmes revenues le plus souvent sur la période
		- Nom de la valeur
		- Comptage sur la période
	    */
	    $tab_exclusion_alarmes = explode(';', $entityEtat->getOptionExclusion2());
	    $tabMostAlarme = array();
	    $entityGenreAlarme = $this->em->getRepository('IpcProgBundle:Genre')->findOneByIntituleGenre('Alarme');
	    $tempo_nombreMessages = 12;
	    $tabMostAlarme = $this->getMostMessages($dateDeb, $dateFin, $idLA, 'genre', $entityGenreAlarme->getId(), null, $tempo_nombreMessages, '=', 1, null, null, null, null);
	    // Le nombre d'alarmes corresponds au nombre de message de type Alarme - le nombre de message alarme récurrents
	    $nb_messages_dalarme = intVal($tabMostAlarme['nbOccurences']);
	    $tab_alarmes_titre = array();
	    $tab_alarmes_classe	= array();
	    foreach ($tabMostAlarme['messages'] as $key => $message) {
            if (in_array($message['id'], $tab_exclusion_alarmes)) {
		    	// Les message de type type sont les message récurrents : donc les plus féquents: donc en début de tableau. Non affectés par le 'break' ci-dessous
				// On retire ces messages du nombre d'alarmes
                $tab_alarmes_titre[] = 'T;'.$message['message'].';'.$message['nbMessages'];
		    	$nb_messages_dalarme -= intVal($message['nbMessages']);
            } else {
                $tab_alarmes_classe[] = $message['message'].';'.$message['nbMessages'];
                if (count($tab_alarmes_classe) == 10) {
                    break;
                }
            }
	    }
	    // Nombre de messages récurrents :
	    $nb_messages_titre = count($tab_alarmes_titre);
	    // Nombre de messages à afficher n'étant pas récurrents
	    $nb_message_alarmes	= $tabMostAlarme['nombre'] + $nb_messages_titre;
		$fichier_detat_mostalarmes = $dossier_etat.'/mostAlarmes.csv';
        $fpalarmes = fopen($fichier_detat_mostalarmes, "w");
	    $nombreDeMessages = $tabMostAlarme['nombre'] - $nb_messages_titre;
	    $messageFpAlarme = "titre;10 messages de genre ".$tabMostAlarme['intituleType']." les plus courants;".$nb_messages_dalarme."\n";
        fwrite($fpalarmes, $messageFpAlarme);
	    $tab_alarmes = array_merge($tab_alarmes_titre, $tab_alarmes_classe);
        foreach ($tab_alarmes as $key => $message_alarme) {
            $messageFpAlarme = $message_alarme."\n";
            fwrite($fpalarmes, $messageFpAlarme);
            if ($key == $nb_message_alarmes) {
                break;
            }
        }
        fclose($fpalarmes);

        /*
        Classer les 10 évenements revenus le plus souvent sur la période
        - Nom de la valeur
        - Comptage sur la période
        */
        if ($this->debug) {
            echo "Debut du calcul des évenements\n";
        }
        $tabMostAnomaliesRegulation = array();
        $tempo_nombreMessages = 10;
        $tabMostAnomaliesRegulation = $this->getMostMessages($dateDeb, $dateFin, $idLA, 'codeModuleAR', 'GE;20', 'GE2001;GE2002;GE2003;GE2004;GE2005;GE2006;GE2007;GE2008;GE2009', $tempo_nombreMessages, null, null, null, null, null, null);
        $nb_messages_danomaliesR = intVal($tabMostAnomaliesRegulation['nbOccurences']);
        $tab_anomaliesR_classe = array();
        foreach ($tabMostAnomaliesRegulation['messages'] as $key => $message) {
			$messageAnomalie = $message['message'].';'.$message['nbMessages'];
			$tab_anomaliesR_classe[] = $messageAnomalie;
            if (count($tab_anomaliesR_classe) == 10) {
                break;
            }
        }
		$nb_messages_anomaliesR = $tabMostAnomaliesRegulation['nombre']; 
        // Messages à afficher
		$fichier_detat_mostAnomaliesR = $dossier_etat.'/mostAnomaliesR.csv';
        $fpAnomaliesR = fopen($fichier_detat_mostAnomaliesR, "w");
        $messageFpAnomaliesR = "titre;10 messages d'anomalies de régulation les plus courants;".$nb_messages_danomaliesR."\n";
        fwrite($fpAnomaliesR, $messageFpAnomaliesR);
        foreach ($tab_anomaliesR_classe as $key => $message_anomalie) {
            $messageFpAnomaliesR = $message_anomalie."\n";
            fwrite($fpAnomaliesR, $messageFpAnomaliesR);
            if ($key == $nb_messages_anomaliesR) {
                break;
            }
        }
        fclose($fpAnomaliesR);
	}
	fclose($fp);
	// Mise à jour de la date d'activation
	// La date d'activation correspond à la date du jour dans le cas d'une recherche unique
	// correspond à la date de fin de période dans le cas d'une recherche périodique
	if (preg_match('/^Du/', $entityEtat->getPeriode())) {
	    // Mise à jour de la date d'activation
        $dateActivation = new \Datetime();
	} else {
	    $dateActivation = new \Datetime($dateFin);
	}
    $entityEtat->setDateActivation($dateActivation);
	// Si la recherche n'est pas périodique : Désactivation de l'Etat
	if (preg_match('/^Du/', $entityEtat->getPeriode())) {
	    $entityEtat->setActive(false);
	}
	$this->em->flush();
	return(0);
}


// Fonction qui recoit une date en entrée et inverse l'année et le jour : ex -> (entrée) 2014-05-10 12:23:34 <- (sortie) 10-05-2014 12:23:34
private function reverseDate($dateToBeTransformed, $format) {
    $dateTransformed = null;
    if ($format == 'mySql') {
        $pattern = '/^(\d{2})[-\/](\d{2})[-\/](\d{4}) (\d{2}:\d{2}:\d{2})$/';
    } else {
        $pattern = '/^(\d{4})[-\/](\d{2})[-\/](\d{2}) (\d{2}:\d{2}:\d{2})$/';
    }
    if (preg_match($pattern, $dateToBeTransformed, $tabDate)) {
        $dateTransformed = $tabDate[3].'/'.$tabDate[2].'/'.$tabDate[1].' '.$tabDate[4];
    }
    return($dateTransformed);
}


private function rechercheValeurLivre($dateDeb, $dateFin, $idLA, $idModule){
	$entityDonnee = new Donnee();
	return ($entityDonnee->sqlGetValeurLivre($this->dbh, $dateDeb, $dateFin, $idLA, $idModule));
}

//Fonction qui supprimer tous les charactères après le caractère passé en paramètre et retour la ligne passée en paramètre
private function supprimeCharAfter($ligne, $caractere) {
	$pattern_car = "/^(.+?)$caractere/";	
	if (preg_match($pattern_car, $ligne, $tabRetour)) {
		return $tabRetour[1];
	}
	return $ligne;
}

// Fonction qui transforme le message passé en paramètre et retourne sa valeur sans les $ ou les £ superflus
private function transformeMessage($message, $type='all', $valeur=null) {
    switch ($type) {
        case 'all':
            $message = preg_replace('/(\$|£)/', '', $message);
            break;
        case 'livre':
            $message = preg_replace('/£/', $valeur, $message);
            break;
        case 'dollar':
            $message = preg_replace('/\$/', $valeur, $message);
            break;
    }
    return($message);
}


private function transformeCameMessage($message, $valeur) {
    $pattern_came = array();
    $pattern_came[0] = '/£/';
    $pattern_came[1] = '/\$/';
    $replacement = array();
    $replacement[0] = $valeur;
    $replacement[1] = '';
    $message = preg_replace($pattern_came, $replacement, $message);
    return($message);
}



private function getPeriodeString($periodeTimestamp) {
	$jours = intval($periodeTimestamp / 86400);
	$joursStr = ($jours > 1)?"$jours jours ":"$jours jour ";

	$heures = intval(($periodeTimestamp - ($jours * 86400)) / 3600);
	$heuresStr = ($heures > 1)?"$heures heures ":"$heures heure ";

	$minutes = intval(($periodeTimestamp - ($jours * 86400) - ($heures * 3600)) / 60);
    $minutesStr = ($minutes > 1)?"$minutes minutes ":"$minutes minute ";

	$secondes = intval($periodeTimestamp - ($jours * 86400) - ($heures * 3600) - ($minutes * 60));
    $secondesStr = ($secondes > 1)?"$secondes secondes ":"$secondes seconde";

	return ($joursStr.$heuresStr.$minutesStr.$secondesStr);
}

private function addADay($dateAIncrementer) {
	$dateTmp = new \Datetime($dateAIncrementer);
	$dateTmp->add(new \DateInterval('P1D'));
	return($dateTmp->format('Y-m-d H:i:s'));
}

}
