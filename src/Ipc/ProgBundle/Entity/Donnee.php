<?php

namespace Ipc\ProgBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use \PDO;
use \PDOException;


// Plusieurs données peuvent pointer 	sur un module
//					sur un fichier
//					sur une localisation

/**
 * Donnee
 *
 * @ORM\Table(name="t_donnee",
 * uniqueConstraints={@ORM\UniqueConstraint(name="UK_search", columns={"horodatage", "cycle", "valeur1", "valeur2", "module_id", "localisation_id"})},
 * indexes={
 * @ORM\Index(name="MK_search", columns={"module_id","horodatage"}),
 * @ORM\Index(name="MK_search2", columns={"localisation_id","module_id","horodatage"}),
 * @ORM\Index(name="MK_search3", columns={"module_id","localisation_id","horodatage"})})
 * @ORM\Entity(repositoryClass="Ipc\ProgBundle\Entity\DonneeRepository")
 */


class Donnee
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="horodatage", type="datetime")
     */
    protected $horodatage;

    /**
     * @var integer
     *
     * @ORM\Column(name="cycle", type="smallint")
     */
    protected $cycle;

    /**
     * @var float
     *
     * @ORM\Column(name="valeur1", type="float")
     */
    protected $valeur1;

    /**
     * @var float
     *
     * @ORM\Column(name="valeur2", type="float")
     */
    protected $valeur2;

    /**
     * @var integer
     *
     * @ORM\Column(name="module_id", type="integer")
    */
    protected $module_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="fichier_id", type="integer")
    */
    protected $fichier_id;


    /**
     * @var integer
     *
     * @ORM\Column(name="localisation_id", type="integer")
    */
    protected $localisation_id;


    public function __construct()
    {
	$this->horodatage = new \Datetime();
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set horodatage
     *
     * @param \DateTime $horodatage
     * @return Donnee
     */
    public function setHorodatage($horodatage)
    {
        $this->horodatage = $horodatage;

        return $this;
    }

    /**
     * Get horodatage
     *
     * @return \DateTime 
     */
    public function getHorodatage()
    {
        return $this->horodatage;
    }

   /**
    * Get horodatageStr
    *
    * @return string
   */
    public function getHorodatageStr()
    {
        return $this->horodatage->format('YmdHis');
    }

    /**
     * Set cycle
     *
     * @param integer $cycle
     * @return Donnee
     */
    public function setCycle($cycle)
    {
        $this->cycle = $cycle;

        return $this;
    }

    /**
     * Get cycle
     *
     * @return integer 
     */
    public function getCycle()
    {
        return $this->cycle;
    }

    /**
     * Set valeur1
     *
     * @param float $valeur1
     * @return Donnee
     */
    public function setValeur1($valeur1)
    {
        $this->valeur1 = $valeur1;

        return $this;
    }

    /**
     * Get valeur1
     *
     * @return float 
     */
    public function getValeur1()
    {
        return $this->valeur1;
    }

    /**
     * Set valeur2
     *
     * @param float $valeur2
     * @return Donnee
     */
    public function setValeur2($valeur2)
    {
        $this->valeur2 = $valeur2;

        return $this;
    }

    /**
     * Get valeur2
     *
     * @return float 
     */
    public function getValeur2()
    {
        return $this->valeur2;
    }

    /**
     * Set module_id
     *
     * @param integer $moduleId
     * @return Donnee
     */
    public function setModuleId($moduleId)
    {
        $this->module_id = $moduleId;

        return $this;
    }

    /**
     * Get module_id
     *
     * @return integer
     */
    public function getModuleId()
    {
        return $this->module_id;
    }

    /**
     * Set fichier_id
     *
     * @param integer $fichierId
     * @return Donnee
     */
    public function setFichierId($fichierId)
    {
        $this->fichier_id = $fichierId;

        return $this;
    }

    /**
     * Get fichier_id
     *
     * @return integer
     */
    public function getFichierId()
    {
        return $this->fichier_id;
    }

    /**
     * Set localisation_id
     *
     * @param integer $localisationId
     * @return Donnee
     */
    public function setLocalisationId($localisationId)
    {
        $this->localisation_id = $localisationId;

        return $this;
    }

    /**
     * Get localisation_id
     *
     * @return integer
     */
    public function getLocalisationId()
    {
        return $this->localisation_id;
    }



    //	Permet d'ajouter les paramètres de recherches concernant la localisation et les modules
    protected function addParamSearch($requete, $liste_id_localisations, $paramAdditionnel, $type) {
		if (($type == 0) || ($type == 1)) {
	    	switch ($type){
				case 0 :
					$motJointure = '';
					break;
				case 1 :
					$motJointure = 'WHERE';
					break;
			}
	    	if (($liste_id_localisations != "'all'") && ($liste_id_localisations != "all")) {
				$requete .= " $motJointure d.localisation_id IN ( $liste_id_localisations )";
				$motJointure = 'AND';
	    	}
            if (($paramAdditionnel != "'all'") && ($paramAdditionnel != "all")) {
				$pattern = "/^'\d+'$/";
				if (preg_match($pattern, $paramAdditionnel)) {
		    		$requete .= " $motJointure d.module_id = $paramAdditionnel ";
				} else {
		    		$requete .= " $motJointure d.module_id IN ( $paramAdditionnel )";
				}
	    	}
		} elseif ($type == 2) {
	    	$requete .= " INNER JOIN t_module m ON m.id = d.module_id ";
	    	$motJointure = 'WHERE';
	    	if (($liste_id_localisations != "'all'") && ($liste_id_localisations != "all")) {
                $requete .= "$motJointure d.localisation_id IN ( $liste_id_localisations ) ";
                $motJointure = 'AND';
            }
	    	$requete .= "$motJointure m.genre_id IN ( $paramAdditionnel )";
		}
        return($requete);
    }

    //  Permet d'ajouter les paramètres de recherches concernant les valeurs 1 et 2
    protected function addSearchValues($requete, $codeVal1, $val1min, $val1max, $codeVal2, $val2min, $val2max) {
        switch ($codeVal1) {
            case 'Eq' :
                $requete .= " AND d.valeur1 = '$val1min'";
            break;
            case 'Inf' :
                $requete .= " AND d.valeur1 > '$val1min'";
            break;
            case 'Sup' :
                $requete .= " AND d.valeur1 < '$val1min'";
            break;
            case 'Int' :
                if ($val1min == $val1max) {
                    $requete .= " AND d.valeur1 = '$val1min' ";
                } else {
                    $requete .= " AND d.valeur1 >= '$val1min' AND d.valeur1 <= '$val1max' ";
                }
            break;
        }
        switch ($codeVal2) {
            case 'Eq':
                $requete .= " AND d.valeur2 = '$val2min'";
            break;
            case 'Inf' :
                $requete .= " AND d.valeur2 > '$val2min'";
            break;
            case 'Sup' :
                $requete .= " AND d.valeur2 < '$val2min'";
            break;
            case 'Int' :
                if($val2min == $val2max) {
                    $requete .= " AND d.valeur2 = '$val2min' ";
                } else {
                    $requete .= " AND d.valeur2 >= '$val2min' AND d.valeur2 <= '$val2max' ";
                }
            break;
        }
        return($requete);
    }

    //  Fonction permettant d'ajouter l'information concernant la condtion de recherche sur la valeur1 ou valeur2 (selon la variable numValeur)
    protected function addCondition($requete, $type, $condition, $valeur, $valeur2, $numValeur) {
        if ($condition != null) {
            if ($type == 'inverse') {
                switch ($condition) {
                    case '=':
                        $condition = '!=';
                        break;
                    case '>':
                        $condition = '<=';
                        break;
                    case '<':
                        $condition = '>=';
                        break;
                    case '>=':
                        $condition = '<';
                        break;
                    case '<=':
                        $condition = '>';
                        break;
                    case '!=':
                        $condition = '=';
                        break;
                    case '<>':
                        $condition = 'NOT BETWEEN';
                        break;
                }
            } else {
                if ($condition == '<>') {
                    $condition = 'BETWEEN';
                }
            }
            $pattern = '/BETWEEN/';
            if (preg_match($pattern, $condition)) {
                $requete = $requete." AND $numValeur $condition $valeur AND $valeur2";
            } else {
                $requete = $requete." AND $numValeur $condition $valeur";
            }
        }
        return($requete);
    }


    //  *********************************************************************************************************************************************************************************************************************
    //									F o n c t i o n s    U t i l i s é e s    pour les recherches      G R A P H I Q U E S 
    //  *********************************************************************************************************************************************************************************************************************
    public function SqlGetCountForGraphique($dbh, $datedebut, $datefin, $liste_id_localisations, $id_module, $codeVal1, $val1min, $val1max, $codeVal2, $val2min, $val2max) {
		$id_module 	= "'$id_module'";
        $donnees = null;
        $requete = "SELECT SQL_NO_CACHE COUNT(*) FROM t_donnee d";
		$requete = $this->addParamSearch($requete, $liste_id_localisations, $id_module, 1);
		if (($liste_id_localisations == "'all'") && ($id_module == "'all'")) {
            $liaison = ' WHERE ';
        } else {
            $liaison = ' AND ';
        }
		$requete .= "$liaison d.horodatage >= '$datedebut' AND d.horodatage <= '$datefin' ";
		$requete = $this->addSearchValues($requete, $codeVal1, $val1min, $val1max, $codeVal2, $val2min, $val2max);
		if (($reponse = $dbh->query($requete)) != false) {
            $donnees = $reponse->fetchColumn();
	    	$reponse->closeCursor();
        }
        return($donnees);
    }

    //      Indique le nombre de points récupérés pour la recherche graphique
    public function SqlGetCountForGraphiqueWP($dbh, $datedebut, $datefin, $liste_id_localisations, $id_module, $codeVal1, $val1min, $val1max, $codeVal2, $val2min, $val2max, $choixRecherche, $precision, $limite) {
        $id_module = "'$id_module'";
        $liaison = ' WHERE ';
        $donnees = null;
        $format_date = null;
        switch ($precision) {
            case 'Seconde' :
                $format_date = '%Y-%m-%d %H:%i:%s';
            break;
            case 'Minute' :
                $format_date = '%Y-%m-%d %H:%i';
            break;
            case 'Heure' :
                $format_date = '%Y-%m-%d %H';
            break;
            case 'Jour' :
                $format_date = '%Y-%m-%d';
            break;
            case 'Mois' :
                $format_date = '%Y-%m';
            break;
        }
		if ($limite != -1) {
			switch ($choixRecherche) {
                case 'all' :
                    $requete = "SELECT SQL_NO_CACHE 1 FROM t_donnee d";
                    $requete = $this->addParamSearch($requete,$liste_id_localisations,$id_module,1);
                    if (($liste_id_localisations == "'all'") && ($id_module == "'all'")) {
                        $liaison = ' WHERE ';
                    } else {
                        $liaison = ' AND ';
                    }
                    $requete .= "$liaison d.horodatage >= '$datedebut' AND d.horodatage <= '$datefin' LIMIT $limite ";
                break;
                default :
                    $requete = "SELECT SQL_NO_CACHE 1 FROM ( SELECT horodatage, valeur1, valeur2, module_id, localisation_id FROM t_donnee d";
                    $requete = $this->addParamSearch($requete,$liste_id_localisations,$id_module,1);
                    if (($liste_id_localisations == "'all'") && ($id_module == "'all'")) {
                        $liaison = ' WHERE ';
                    } else {
                         $liaison = ' AND ';
                    }
                    $requete .= " $liaison d.horodatage >= '$datedebut' AND d.horodatage <= '$datefin' ";
                    $requete .= "GROUP BY DATE_FORMAT(horodatage,'$format_date') ORDER BY null LIMIT $limite  ) AS T1";
                break;
            }
            $requete = $this->addSearchValues($requete, $codeVal1, $val1min, $val1max, $codeVal2, $val2min, $val2max);
            if (($reponse = $dbh->query($requete)) != false) {
                $donnees = $reponse->fetchColumn();
                $reponse->closeCursor();
            }
			$requete = "SELECT found_rows()";
            if (($reponse = $dbh->query($requete)) != false) {
                $donnees = $reponse->fetchColumn();
                $reponse->closeCursor();
            }
            return($donnees + 1);
		} else {
        	switch ($choixRecherche) {
        	    case 'all' :
        	        $requete = "SELECT SQL_NO_CACHE COUNT(*) FROM t_donnee d";
        	        $requete = $this->addParamSearch($requete,$liste_id_localisations,$id_module,1);
        	        if (($liste_id_localisations == "'all'") && ($id_module == "'all'")) {
        	            $liaison = ' WHERE ';
        	        } else {
        	            $liaison = ' AND ';
        	        }
        	        $requete .= "$liaison d.horodatage >= '$datedebut' AND d.horodatage <= '$datefin' ";
       	     	break;
            	default :
            	    $requete = "SELECT SQL_NO_CACHE COUNT(*) FROM ( SELECT horodatage, valeur1, valeur2, module_id, localisation_id FROM t_donnee d";
            	    $requete = $this->addParamSearch($requete,$liste_id_localisations,$id_module,1);
            	    if (($liste_id_localisations == "'all'") && ($id_module == "'all'")) {
            	        $liaison = ' WHERE ';
            	    } else {
            	         $liaison = ' AND ';
            	    }
            	    $requete .= " $liaison d.horodatage >= '$datedebut' AND d.horodatage <= '$datefin' ";
            	    $requete .= "GROUP BY DATE_FORMAT(horodatage,'$format_date') ORDER BY null ) AS T1";
            	break;
        	}
        	$requete = $this->addSearchValues($requete, $codeVal1, $val1min, $val1max, $codeVal2, $val2min, $val2max);
        	if (($reponse = $dbh->query($requete)) != false) {
        	    $donnees = $reponse->fetchColumn();
        	    $reponse->closeCursor();
        	}
        	return($donnees);
		}
    }


    // Récupération du dernier point avant la date donnée
    public function SqlGetLastPoint($dbh, $datedebut, $datefin, $liste_id_localisations, $id_module, $codeVal1, $val1min, $val1max, $codeVal2, $val2min, $val2max) {
        $requete = "SELECT SQL_NO_CACHE d.horodatage, d.cycle, d.valeur1, d.valeur2 FROM t_donnee d";
        $requete = $this->addParamSearch($requete, $liste_id_localisations, $id_module, 1);
        if ((($liste_id_localisations == "'all'") && ($id_module == "'all'")) || (($liste_id_localisations == "all") && ($id_module == "all"))) {
            $liaison = ' WHERE ';
        } else {
            $liaison = ' AND ';
        }
        $requete .= " $liaison d.horodatage >= '$datedebut' AND d.horodatage <= '$datefin' ";
        $requete = $this->addSearchValues($requete,$codeVal1,$val1min,$val1max,$codeVal2,$val2min,$val2max);
        $requete .= " ORDER BY d.horodatage DESC, d.cycle DESC LIMIT 1";
        if (($reponse = $dbh->query($requete)) != false) {
            $donnees = $reponse->fetchAll();
            $reponse->closeCursor();
        }
        return($donnees);
    }


	//	Utilisée dans GraphiqueController
    public function SqlGetForGraphique($dbh, $datedebut, $datefin, $liste_id_localisations, $id_module, $codeVal1, $val1min, $val1max, $codeVal2, $val2min, $val2max, $limit, $offset, $choixRecherche, $precision) {
		// On utilise les moyennes pondérées plutot que les moyennes du logiciel HighChart
		if (($choixRecherche == 'average') && ($precision != 'Mois')) {
			$choixRecherche = 'all';
		}
        $id_module = "'$id_module'";
        $liaison = ' WHERE ';
        $donnees = null;
        $format_date = null;
        switch ($precision) {
            case 'Seconde' :
                $format_date = '%Y-%m-%d %H:%i:%s';
                break;
            case 'Minute' :
                $format_date = '%Y-%m-%d %H:%i';
                break;
            case 'Heure' :
                $format_date = '%Y-%m-%d %H';
                break;
            case 'Jour' :
                $format_date = '%Y-%m-%d';
                break;
            case 'Mois' :
                $format_date = '%Y-%m';
                break;
        }
        switch ($choixRecherche) {
            case 'all' :
                $requete = "SELECT SQL_NO_CACHE d.horodatage, d.cycle, d.valeur1, d.valeur2 FROM t_donnee d";
                $requete = $this->addParamSearch($requete, $liste_id_localisations, $id_module, 1);
                if (($liste_id_localisations == "'all'") && ($id_module == "'all'")) {
                    $liaison = ' WHERE ';
                } else {
                    $liaison = ' AND ';
                }
                $requete .= " $liaison d.horodatage >= '$datedebut' AND d.horodatage <= '$datefin' ";
                break;
            case 'average' :
                $requete = "SELECT SQL_NO_CACHE T1.horodatage, AVG(T1.valeur1) AS valeur1, AVG(T1.valeur2) AS valeur2 FROM ( SELECT horodatage, valeur1, valeur2, module_id, localisation_id FROM t_donnee d";
                $requete = $this->addParamSearch($requete, $liste_id_localisations, $id_module, 1);
                if (($liste_id_localisations == "'all'") && ($id_module == "'all'")) {
                    $liaison = ' WHERE ';
                } else {
                    $liaison = ' AND ';
                }
                $requete .= " $liaison d.horodatage >= '$datedebut' AND d.horodatage <= '$datefin' ";
                $requete .= ") AS T1 GROUP BY DATE_FORMAT(horodatage,'$format_date')";// ORDER BY null";
                break;
            case 'high' :
                $requete = "SELECT SQL_NO_CACHE T1.horodatage, MAX(T1.valeur1) AS valeur1, MAX(T1.valeur2) AS valeur2 FROM ( SELECT horodatage, valeur1, valeur2, module_id, localisation_id FROM t_donnee d";
                $requete = $this->addParamSearch($requete, $liste_id_localisations, $id_module, 1);
                if (($liste_id_localisations == "'all'") && ($id_module == "'all'")) {
                    $liaison = ' WHERE ';
                } else {
                    $liaison = ' AND ';
                }
                $requete .= " $liaison d.horodatage >= '$datedebut' AND d.horodatage <= '$datefin' ";
                $requete .= ") AS T1 GROUP BY DATE_FORMAT(horodatage,'$format_date')";// ORDER BY null";
                break;
            case 'low' :
                $requete = "SELECT SQL_NO_CACHE T1.horodatage, MIN(T1.valeur1) AS valeur1, MIN(T1.valeur2) AS valeur2 FROM ( SELECT horodatage, valeur1, valeur2, module_id, localisation_id FROM t_donnee d";
                $requete = $this->addParamSearch($requete, $liste_id_localisations, $id_module, 1);
                if (($liste_id_localisations == "'all'") && ($id_module == "'all'")) {
                    $liaison = ' WHERE ';
                } else {
                    $liaison = ' AND ';
                }
                $requete .= " $liaison d.horodatage >= '$datedebut' AND d.horodatage <= '$datefin' ";
               $requete .= ") AS T1 GROUP BY DATE_FORMAT(horodatage,'$format_date')";// ORDER BY null";
                break;
        }
        $requete = $this->addSearchValues($requete, $codeVal1, $val1min, $val1max, $codeVal2, $val2min, $val2max);
        if ($choixRecherche == 'all') {
            $requete .= " ORDER BY horodatage, cycle ";
        }
        if ($limit != 'nolimit') {
            $requete .= " LIMIT $limit OFFSET $offset";
        }
        if (($reponse = $dbh->query($requete)) != false) {
            $donnees = $reponse->fetchAll();
            $reponse->closeCursor();
        }
        return($donnees);
    }



    //  *********************************************************************************************************************************************************************************************************************
    //								F o n c t i o n s    U t i l i s é e s    pour les recherches des      L I S T I N G S 
    //  *********************************************************************************************************************************************************************************************************************

    // Création des requêtes count
/*
    public function sqlParametresCount($liste_id_localisations, $liste_id_modules, $codeVal1, $val1min, $val1max, $codeVal2, $val2min, $val2max) {
        $donnees = null;
        $requete = '';
        $requete = $this->addParamSearch($requete, $liste_id_localisations, $liste_id_modules, 0);
        $requete = $this->addSearchValues($requete, $codeVal1, $val1min, $val1max, $codeVal2, $val2min, $val2max);
        return($requete);
    }
*/


    //	Execution des requêtes count et retour de la somme des counts
    public function sqlCountListing($dbh, $datedebut, $datefin, $champ, $requeteToBeSummed, $limite) {
		$donnees = null;

		if ($limite != -1) {
			$requete = "SELECT SQL_NO_CACHE 1 FROM t_donnee d WHERE ($requeteToBeSummed) AND d.horodatage >= '$datedebut' AND d.horodatage <= '$datefin' LIMIT $limite ";
			if (($reponse = $dbh->query($requete)) != false) {
        	    $donnees = $reponse->fetchColumn();
        	    $reponse->closeCursor();
        	}
			$requete = "SELECT found_rows()";
			if (($reponse = $dbh->query($requete)) != false) {
        	    $donnees = $reponse->fetchColumn();
        	    $reponse->closeCursor();
        	}
        	return($donnees + 1);
		} else {
			$requete = "SELECT SQL_NO_CACHE COUNT(*) FROM t_donnee d WHERE ($requeteToBeSummed) AND d.horodatage >= '$datedebut' AND d.horodatage <= '$datefin'";
			if (($reponse = $dbh->query($requete)) != false) {
                $donnees = $reponse->fetchColumn();
                $reponse->closeCursor();
            }
			return($donnees);
		}
    }


    // 	Création du sql d'une requête : Pour les requêtes à imbriquer
    public function sqlParametresListing($liste_id_localisations, $liste_id_modules, $codeVal1, $val1min, $val1max, $codeVal2, $val2min, $val2max) {
        $donnees = null;
        $requete = '';
        $requete = $this->addParamSearch($requete, $liste_id_localisations, $liste_id_modules, 0);
        $requete = $this->addSearchValues($requete, $codeVal1, $val1min, $val1max, $codeVal2, $val2min, $val2max);
        return($requete);
    }

    // Execution des requêtes ( Regroupées par un Union )
    public function sqlAllLimitedOrdered($dbh, $datedebut, $datefin, $requeteToBeSearch, $limit, $offset) {
        $donnees = null;
        $requete = "SELECT SQL_NO_CACHE d.horodatage, d.cycle, d.valeur1, d.valeur2, d.module_id, d.localisation_id FROM t_donnee d ";
		$requete .= "WHERE ($requeteToBeSearch) AND d.horodatage >= '$datedebut' AND d.horodatage <= '$datefin'  ORDER BY d.horodatage, d.cycle LIMIT $limit OFFSET $offset";
        if (($reponse = $dbh->query($requete)) != false) {
            $donnees = $reponse->fetchAll();
            $reponse->closeCursor();
        }
        return($donnees);
    }


/*
    public function sqlParametresListing($datedebut, $datefin, $liste_id_localisations, $liste_id_modules, $codeVal1, $val1min, $val1max, $codeVal2, $val2min, $val2max) {
        $donnees = null;
        $requete = "SELECT  d.horodatage, d.cycle, d.valeur1, d.valeur2, d.module_id, d.localisation_id FROM t_donnee d";
        $requete = $this->addParamSearch($requete, $liste_id_localisations, $liste_id_modules, 1);
        if (($liste_id_localisations == "'all'")&&($liste_id_modules == "'all'")) {
            $liaison = ' WHERE ';
        } else {
            $liaison = ' AND ';
        }
        $requete .= "$liaison d.horodatage >= '$datedebut' AND d.horodatage <= '$datefin' ";
        $requete = $this->addSearchValues($requete, $codeVal1, $val1min, $val1max, $codeVal2, $val2min, $val2max);
        return($requete);
    }

    // Execution des requêtes ( Regroupées par un Union )
    public function sqlAllLimitedOrdered($dbh, $requeteToBeSearch, $limit, $offset) {
		$donnees = null;
		$requete = "SELECT SQL_NO_CACHE * FROM ($requeteToBeSearch) AS T1 ORDER BY horodatage, cycle LIMIT $limit OFFSET $offset";
		if (($reponse = $dbh->query($requete)) != false) {
            $donnees = $reponse->fetchAll();
            $reponse->closeCursor();
        }
        return($donnees);
    }
*/


    //  *********************************************************************************************************************************************************************************************************************
    //  								F o n c t i o n s    U t i l i s é e s    par le module      [ S U P E R V I S I O N ]        
    //  *********************************************************************************************************************************************************************************************************************

    public function sqlGetForGraphiqueLive($dbh, $debut, $fin, $id_module, $id_localisations, $nombreMessages) {
        $donnees = null;
        $liaison = null;
        $requete = "SELECT SQL_NO_CACHE horodatage, cycle, valeur1 FROM t_donnee d ";
        $requete = $this->addParamSearch($requete, $id_localisations, $id_module, 1);
        $requete .= " AND d.horodatage >= '$debut' AND d.horodatage <= '$fin' ORDER BY d.horodatage DESC LIMIT $nombreMessages";
        if (($reponse = $dbh->query($requete)) != false) {
            $donnees = $reponse->fetchAll();
            $reponse->closeCursor();
        }
        return($donnees);
    }


    //  *********************************************************************************************************************************************************************************************************************
    //  								F o n c t i o n s    U t i l i s é e s    par le service      [ C O N F I G U R A T I O N ] 
    //  *********************************************************************************************************************************************************************************************************************

    // Récupération du dernier point avant la date donnée
    public function SqlGetNbLast($dbh, $datedebut, $datefin, $id_module, $liste_id_localisations) {
		$donnees = null;
        $requete = "SELECT SQL_NO_CACHE COUNT(*) FROM t_donnee d";
        $requete = $this->addParamSearch($requete, $liste_id_localisations, $id_module, 1);
        if ((($liste_id_localisations == "'all'")&&($id_module == "'all'")) || (($liste_id_localisations == "all")&&($id_module == "all"))) {
           	$liaison = ' WHERE ';
        } else {
           	$liaison = ' AND ';
        }
        $requete .= " $liaison d.horodatage >= '$datedebut' AND d.horodatage <= '$datefin' ";
        if (($reponse = $dbh->query($requete)) != false) {
           	$donnees = $reponse->fetchColumn();
           	$reponse->closeCursor();
        }
        return($donnees);
    }

	//	Récupération du dernier message de la base de donnée sur une période définie
    public function sqlGetLast($dbh, $debut, $fin, $id_module, $liste_id_localisations) {
        $donnees = null;
        $liaison = null;
        $requete = "SELECT SQL_NO_CACHE * FROM t_donnee d ";
        $requete = $this->addParamSearch($requete, $liste_id_localisations, $id_module, 1);
        if ((($liste_id_localisations == "'all'") && ($id_module == "'all'")) || (($liste_id_localisations == "all") && ($id_module == "all"))) {
            $liaison = ' WHERE ';
        } else {
            $liaison = ' AND ';
        }
        $requete .= " $liaison d.horodatage >= '$debut' AND d.horodatage <= '$fin' ORDER BY d.horodatage DESC LIMIT 1";
        if (($reponse = $dbh->query($requete)) != false) {
            $donnees = $reponse->fetchAll();
            $reponse->closeCursor();
        }
        return($donnees);
    }

	// Récupère les n derniers messages depuis la date donnée sur une période donnée
	// Utilisée par ServiceEtatQueries pour SupervisionBundle
	public function sqlGetXLast($dbh, $debut, $fin, $id_module, $id_localisations, $nombreMessages, $conditionValeur) {
		$donnees = null;
		$liaison = null;
		$requete = "SELECT SQL_NO_CACHE * FROM t_donnee d ";
		$requete = $this->addParamSearch($requete,$id_localisations,$id_module,1);
		if ($conditionValeur != null) {
			$requete .= " AND d.horodatage >= '$debut' AND d.horodatage <= '$fin' AND $conditionValeur ORDER BY d.horodatage DESC, d.cycle DESC LIMIT $nombreMessages";
		} else {
			$requete .= " AND d.horodatage >= '$debut' AND d.horodatage <= '$fin' ORDER BY d.horodatage DESC, d.cycle DESC LIMIT $nombreMessages";
		}
		if (($reponse = $dbh->query($requete)) != false) {
			$donnees = $reponse->fetchAll();
			$reponse->closeCursor();
		}
		return($donnees);
	}


    //  *********************************************************************************************************************************************************************************************************************
    //  								F o n c t i o n s    U t i l i s é e s    par les services 	[ ServiceImportBin.php et ServiceRattrapImportBin.php ] 
    //  *********************************************************************************************************************************************************************************************************************
    //      Requêtes utilisées pour l'insertion des données en base de donnée
    // Insertion des données : Si un doublon est detecté, l'insertion est ignorée
    public function myInsert($dbh, $liste_donnees) {
        $retour = null;
        $requete = "INSERT INTO t_donnee ( horodatage, cycle, valeur1, valeur2, module_id, fichier_id, localisation_id ) VALUES $liste_donnees";
        $retour = $dbh->exec($requete);
        $errorCode = $dbh->errorCode();
        if ($errorCode != 0) {
        	$retour 		= null;
        }
        return($retour);
    }

    // Requêtes utilisées pour l'insertion des données en base de donnée
    public function checkDoublon($dbh, $module_id, $localisation_id) {
		$donnees = null;
		$horodatageStr = $this->getHorodatageStr();
		$requete = "
			SELECT SQL_NO_CACHE id AS id
			FROM t_donnee td
			WHERE td.horodatage = '$horodatageStr'
			AND td.cycle = '$this->cycle' 
			AND td.valeur1 = '$this->valeur1' 
			AND td.valeur2 = '$this->valeur2' 
			AND td.module_id = '$module_id'  
			AND td.localisation_id 	= '$localisation_id'
			LIMIT 1";
       	if (($reponse = $dbh->query($requete)) != false) {
	   		$donnees = $reponse->fetchColumn();
	   		$reponse->closeCursor();
       	}
		return($donnees);
    }


    //  *********************************************************************************************************************************************************************************************************************
    //  								F o n c t i o n s    U t i l i s é e s    par le service 	[ ServiceRapports.php ]	
    //  *********************************************************************************************************************************************************************************************************************
    // Fonction appelée par le rapport d'analyse
    public function sqlGetNbModules($dbh, $debut, $fin, $localisation_id) {
        $donnees = null;
        $requete = "SELECT SQL_NO_CACHE module_id as id,count(*) as nb FROM t_donnee WHERE horodatage >= '$debut' AND horodatage <= '$fin' AND localisation_id = '$localisation_id' GROUP BY module_id;";
        if (($reponse = $dbh->query($requete)) != false) {
            $donnees = $reponse->fetchAll();
            $reponse->closeCursor();
        }
        return($donnees);
    }
	



    //  *********************************************************************************************************************************************************************************************************************
    //  								F o n c t i o n s    U t i l i s é e s    par le service        [ ServiceEtatQueries.php ] 
    //  *********************************************************************************************************************************************************************************************************************
    public function sqlLastGetValeur($dbh, $debut, $fin, $id_module, $liste_id_localisations) {
        $donnees = null;
        $liaison = null;
		$requete = "SELECT SQL_NO_CACHE valeur1 FROM t_donnee d ";
        $requete = $this->addParamSearch($requete, $liste_id_localisations, $id_module, 1);
        if ((($liste_id_localisations == "'all'")&&($id_module == "'all'")) || (($liste_id_localisations == "all")&&($id_module == "all"))) {
            $liaison = ' WHERE ';
        } else {
            $liaison = ' AND ';
        }
		$requete .= " $liaison d.horodatage >= '$debut' AND d.horodatage <= '$fin' ORDER BY d.horodatage DESC, d.cycle DESC LIMIT 1";
        if (($reponse = $dbh->query($requete)) != false) {
            $donnees = $reponse->fetchColumn();
            $reponse->closeCursor();
        }
        return($donnees);
    }

    public function sqlLastGetValeurStrict($dbh, $debut, $fin, $id_module, $liste_id_localisations) {
        $donnees = null;
        $liaison = null;
        $requete = "SELECT SQL_NO_CACHE valeur1 FROM t_donnee d ";
        $requete = $this->addParamSearch($requete, $liste_id_localisations, $id_module, 1);
        	if ((($liste_id_localisations == "'all'")&&($id_module == "'all'")) || (($liste_id_localisations == "all")&&($id_module == "all"))) {
            	$liaison = ' WHERE ';
        	} else {
            	$liaison = ' AND ';
        	}
        	$requete .= " $liaison d.horodatage >= '$debut' AND d.horodatage < '$fin' ORDER BY d.horodatage DESC, d.cycle DESC LIMIT 1";
        	if (($reponse = $dbh->query($requete)) != false) {
            	$donnees = $reponse->fetchColumn();
            	$reponse->closeCursor();
        	}
        return($donnees);
    }

	// Fonction utilisée pour récupérer la liste des identifiants de modules dont le genre ou l'intitulé du module sont passés en paramètre.
	// Utilisée dans le service ServiceEtatQueries pour récupérer les x messages les plus courants
    public function getIdModule($dbh, $type, $idType) {
		$donnees = null;
		// Recherche des id de message dont le type est genre
        if ($type == 'genre') {
            $requete_id_messages = 'SELECT id FROM t_module WHERE genre_id = '.$idType;
        } elseif($type == 'module') {
            $requete_id_messages = "SELECT id FROM t_module WHERE intitule_module like '".$idType."';";
        } else {
            $requete_id_messages = null;
        }
		if ($requete_id_messages != null) {
	    	if (($reponse = $dbh->query($requete_id_messages)) != false) {
            	$donnees = $reponse->fetchAll();
            	$reponse->closeCursor();
            }
	    	// Retour du tableau sous forme d'une chaine de caractères
	    	$str_donnees = null;
	    	if ($donnees != null) {
				$str_donnees = '';
				foreach ($donnees as $donnee) {
		    		$str_donnees .= $donnee['id'].','; 
				}
				$str_donnees = substr($str_donnees, 0, -1);
	    	}
	    	return($str_donnees);
		}
        return(null);
    }

    // Retourne un tableau indiquant les ids et le nombre de messages parmis les x plus courants dont la valeur est = 1
    public function getMostMessages($dbh, $dateDeb, $dateFin, $idLocalisation, $listeMessages, $nbMessages, $condition, $valeur, $valeur2, $conditionB, $valeurB, $valeurB2) {
		$donnees = array();
        $requete = "SELECT SQL_NO_CACHE COUNT(*) as nbMessages, module_id FROM t_donnee WHERE horodatage >= '$dateDeb' AND horodatage <= '$dateFin' AND localisation_id = '$idLocalisation'";
		$requete = $this->addCondition($requete, 'init', $condition, $valeur, $valeur2, 'valeur1');
		$requete = $this->addCondition($requete, 'init', $conditionB, $valeurB, $valeurB2, 'valeur2');
        if ($listeMessages != null) {
            $requete .= ' AND module_id IN('.$listeMessages.')';
        }
        $requete .= " GROUP BY module_id ORDER BY nbMessages DESC  LIMIT $nbMessages OFFSET 0";
		if (($reponse = $dbh->query($requete)) != false) {
            $donnees = $reponse->fetchAll();
            $reponse->closeCursor();
        }
        return($donnees);
    }

	// Spécial pour la recherche des Anomalies de régulation les plus courantes/
	// La recherche se fait en fonction du £ => cad Valeur 2
    // Retourne un tableau indiquant les ids et le nombre de messages parmis les x plus courants dont la valeur est = 1
    public function getMostMessagesAR($dbh, $dateDeb, $dateFin, $idLocalisation, $tabMessages, $nbMessages, $condition, $valeur, $valeur2, $conditionB, $valeurB, $valeurB2) {
        $donnees = null;
		$requete = "SELECT SQL_NO_CACHE COUNT(*) as nbMessages, module_id, valeur2 FROM t_donnee WHERE horodatage >= '$dateDeb' AND horodatage <= '$dateFin' AND localisation_id = '$idLocalisation'";
        $requete = $this->addCondition($requete, 'init', $condition, $valeur, $valeur2, 'valeur1');
        $requete = $this->addCondition($requete, 'init', $conditionB, $valeurB, $valeurB2, 'valeur2');
        if (! empty($tabMessages)) {
			$liaison = ' AND (';
			foreach($tabMessages as $idModule => $tabDesLivres) {
				if ($tabDesLivres != null) {
					foreach ($tabDesLivres as $key => $valeurLivre) {
            			$requete .= $liaison." (module_id ='".$idModule."' AND valeur1 = '1' AND valeur2 = '".$valeurLivre."')";
					}
				} else {
					$requete .= $liaison." (module_id ='".$idModule."' AND valeur1 = '1')";
				}
				$liaison = ' OR ';
			}
			$requete .= ")";
        }
        $requete .= " GROUP BY module_id, valeur2  ORDER BY nbMessages DESC  LIMIT $nbMessages OFFSET 0";
        if (($reponse = $dbh->query($requete)) != false) {
            $donnees = $reponse->fetchAll();
            $reponse->closeCursor();
        }
        return($donnees);
    }


    // Retourne le nombre de messages
    public function getNbOccurences($dbh, $dateDeb, $dateFin, $idLocalisation, $listeMessages, $nbMessages, $condition, $valeur, $valeur2, $conditionB, $valeurB, $valeurB2) {
        $donnees = null;
        $requete = "SELECT SQL_NO_CACHE COUNT(*) as nbOccurences 
				   	FROM t_donnee 
				   	WHERE horodatage >= '$dateDeb' AND horodatage <= '$dateFin' 
					AND localisation_id = '$idLocalisation'";
        $requete = $this->addCondition($requete, 'init', $condition, $valeur, $valeur2, 'valeur1');
		$requete = $this->addCondition($requete, 'init', $conditionB, $valeurB, $valeurB2, 'valeur2');
        if ($listeMessages != null) {
            $requete .= ' AND module_id IN('.$listeMessages.')';
        }
        if (($reponse = $dbh->query($requete)) != false) {
            $donnees = $reponse->fetchColumn();
            $reponse->closeCursor();
        }
        return($donnees);
    }


    public function getTheValue($dbh, $dateDeb, $cycleDeb, $dateFin, $idLocalisation, $idModule, $condition, $valeur, $valeur2, $type) {
		$donnees = null;
		$requete = "SELECT SQL_NO_CACHE * 
				   	FROM t_donnee 
				   	WHERE ( ( horodatage >= '$dateDeb' AND cycle > $cycleDeb ) OR (horodatage > '$dateDeb' ) )
					AND horodatage <= '$dateFin' 
					AND module_id = '$idModule' 
					AND localisation_id = '$idLocalisation'";
		$requete = $this->addCondition($requete, $type, $condition, $valeur, $valeur2, 'valeur1');
		$requete .= " 	ORDER BY horodatage 
						LIMIT 1 
						OFFSET 0";

		if (($reponse = $dbh->query($requete)) != false) {
            $donnees = $reponse->fetchAll();
            $reponse->closeCursor();
        }
        return($donnees);
    }


    // Identique à la fonction précédente mais accepte plusieurs modules en entrée : Utilisé pour rechercher les arrêts bruleurs et chaudières
    public function getTheValueBiModules($dbh, $dateDeb, $cycleDeb, $dateFin, $idLocalisation, $idModule1, $condition, $valeur, $valeur12, $idModule2, $condition2, $valeur2, $valeur22, $type) {
        $donnees = null;
		if ($idModule2 == null) {
        	$requete = "SELECT SQL_NO_CACHE * 
				   		FROM t_donnee 
				   		WHERE ( ( horodatage >= '$dateDeb' 
						AND cycle > $cycleDeb ) 
						OR (horodatage > '$dateDeb' ) ) 
						AND horodatage <= '$dateFin' 
						AND module_id = '$idModule1' 
						AND localisation_id = '$idLocalisation'";
        	$requete = $this->addCondition($requete, $type, $condition, $valeur, $valeur12, 'valeur1');
		} else {
			$requete = "SELECT SQL_NO_CACHE * 
				   		FROM t_donnee 
   				   		WHERE ( ( horodatage >= '$dateDeb' 
						AND cycle > $cycleDeb ) 
						OR (horodatage > '$dateDeb' ) ) 
						AND horodatage <= '$dateFin' 
						AND localisation_id = '$idLocalisation' 
						AND ( (module_id = '$idModule1'";
			$requete = $this->addCondition($requete, $type, $condition, $valeur, $valeur12, 'valeur1');
			$requete .= ") OR (module_id = '$idModule2'";
            $requete = $this->addCondition($requete, $type, $condition2, $valeur2, $valeur22, 'valeur1');
            $requete .= "))";
		}
		$requete .= " 	ORDER BY horodatage 
						LIMIT 1 
						OFFSET 0";
       	if (($reponse = $dbh->query($requete)) != false) {
            $donnees = $reponse->fetchAll();
            $reponse->closeCursor();
        }
        return($donnees);
    }


    public function getTheValueDebutRearmement($dbh, $dateDeb, $cycleDeb, $dateFin, $idLocalisation, $idChaudiere1, $idChaudiere2, $idBruleur1, $idBruleur2) {
		$donnees = null;
		$liste_idModules = '';
		$nombre_de_modules = 0;
		if ($idChaudiere1 != null) {
	    	$liste_idModules .= $idChaudiere1.',';
	    	$nombre_de_modules ++;
		}
		if ($idChaudiere2 != null) {
            $liste_idModules .= $idChaudiere2.',';
	    	$nombre_de_modules ++;
        }
		if ($idBruleur1 != null) {
            $liste_idModules .= $idBruleur1.',';
	    	$nombre_de_modules ++;
        }
		if ($idBruleur2 != null) {
            $liste_idModules .= $idBruleur2.',';
	    	$nombre_de_modules ++;
        }
		$liste_idModules = substr($liste_idModules, 0, -1);
		$requete = "SELECT SQL_NO_CACHE * 
				   	FROM t_donnee 
				   	WHERE ( ( horodatage >= '$dateDeb' AND cycle > $cycleDeb ) 
					OR (horodatage > '$dateDeb' ) ) 
				   	AND horodatage <= '$dateFin' 
					AND module_id in ($liste_idModules) 
					AND localisation_id = $idLocalisation 
					AND valeur1 = 1  
					ORDER BY horodatage 
					LIMIT $nombre_de_modules  
					OFFSET 0";
        if (($reponse = $dbh->query($requete)) != false) {
            $donnees = $reponse->fetchAll();
            $reponse->closeCursor();
        }
		if ($donnees != null) {
	    	//	On retourne les informations concernant les chaudières si des données de chaudières sont récupérées parmis les plus anciennes horodatées
	    	$tabARetourner 	= array();
	    	$tabARetourner[0] 	= $donnees[0];
	    	if ($idChaudiere1 != null || $idChaudiere2 != null) {
	    		// Si donnée1 != idChaudière1 && != idChaudière2
	    		// Parcours des données récupérées. 
	    		//	Si l'horodatage n+1 = horodatage n et que cycle n+1 = cycle n
	    		//		si donnée n+1 = idChaudière1 || idChaudière2 
	    		//			retour donnée n+1
	    		//  Sinon retour donnée1
	    		if ($donnees[0]['module_id'] == $idChaudiere1 || $donnees[0]['module_id'] == $idChaudiere2) {
	    		    return($tabARetourner);
	    		}
	    	    foreach ($donnees as $key => $tabRetour) {
	    		    if ($tabRetour[$key]['horodatage'] == $tabARetourner[0]['horodatage'] && $tabRetour[$key]['cycle'] == $tabARetourner[0]['cycle']) {
			    		if ($tabRetour[$key]['module_id'] == $idChaudiere1 || $tabRetour[$key]['module_id'] == $idChaudiere2) {
			    	    	$tabARetourner[0] = $tabRetour[$key];
			    	    	break;
			        	}
	    		    } else {
			    		//	Si l'horodatage et le cycle ne sont pas égal à ceux de la premiere données c'est que la données est plus récent. Fin de boucle
			    		break;
	    		    }
	    		}
	    	}
	    	return($tabARetourner);
   		}
		return(null);
    }


    //  Calcul sur une période -> calcul parmis somme,maximum,minimum
    public function sqlCalculValues1($dbh, $dateDeb, $dateFin, $calcul, $idLocalisation, $idModule, $condition, $valeur, $valeur2) {
        $donnees = null;
		$conditionCalcul = $this->getCalculCondition($calcul);
        $requete = "SELECT SQL_NO_CACHE $conditionCalcul 
					FROM t_donnee WHERE horodatage >= '$dateDeb' AND horodatage <= '$dateFin' 
					AND module_id = '$idModule' 
					AND localisation_id = '$idLocalisation'";
		$requete = $this->addCondition($requete,'init',$condition,$valeur,$valeur2,'valeur1');
        if (($reponse = $dbh->query($requete)) != false) {
            $donnees = $reponse->fetchColumn();
            $reponse->closeCursor();
        }
        return($donnees);
    }

    public function sqlOccurences($dbh, $dateDeb, $cycleDeb, $dateFin, $cycleFin, $idLocalisation, $idModule, $condition, $valeur, $valeur2) {
        $donnees = null;
        $requete = "SELECT SQL_NO_CACHE COUNT(*) 
					FROM t_donnee 
					WHERE ( ( horodatage = '$dateDeb' AND cycle > '$cycleDeb' ) OR ( horodatage > '$dateDeb' ) )
					AND ( ( horodatage = '$dateFin' AND cycle <= $cycleFin ) OR ( horodatage < '$dateFin' ) )
					AND module_id = '$idModule' 
					AND localisation_id = '$idLocalisation'";
		$requete = $this->addCondition($requete, 'init', $condition, $valeur, $valeur2, 'valeur1');
        if (($reponse = $dbh->query($requete)) != false) {
            $donnees = $reponse->fetchColumn();
            $reponse->closeCursor();
        }
        return($donnees);
    }


    protected function getCalculCondition($calcul) {
		$condition 		= null;
		switch ($calcul) {
	    	case 'somme':
				$condition 	= "SUM(valeur1) as valeur1";
			break;
	    	case 'moyenne':
				$condition 	= "AVG(valeur1) as valeur1";
			break;	
	    	case 'maximum':
				$condition 	= "MAX(valeur1) as valeur1";
			break;
	    	case 'minimum':
				$condition 	= "MIN(valeur1) as valeur1";
			break;
		}
		return($condition);
    }
    
    public function getCalculParJour($dbh, $dateDeb, $dateFin, $calcul, $idLocalisation, $idModule) {
		$donnees = null;
		$conditionCalcul = $this->getCalculCondition($calcul);
		$requete = "SELECT CAST(horodatage as date) as jour, $conditionCalcul 
				 	FROM t_donnee 
					WHERE horodatage >= '$dateDeb' AND horodatage <= '$dateFin' 
					AND module_id = '$idModule' 
					AND localisation_id = '$idLocalisation'";
		$requete .= " GROUP BY CAST(horodatage as date) ORDER BY CAST(horodatage as date)";
		if (($reponse = $dbh->query($requete)) != false) {
    	    $donnees	 	= $reponse->fetchAll();
    	    $reponse->closeCursor();
    	}
    	return($donnees);
    }


    public function getCalculParHeure($dbh, $dateDeb, $dateFin, $calcul, $idLocalisation, $idModule) {
        $donnees = null;
		$conditionCalcul = $this->getCalculCondition($calcul);
        $requete = "SELECT CAST(horodatage as date) as jour, HOUR(horodatage) as heure, $conditionCalcul 
				   	FROM t_donnee 
				   	WHERE horodatage >= '$dateDeb' AND horodatage <= '$dateFin' 
					AND module_id = '$idModule' 
					AND localisation_id = '$idLocalisation'";
        $requete .= " GROUP BY CAST(horodatage as date), HOUR(horodatage) ORDER BY CAST(horodatage as date), HOUR(horodatage)";
        if (($reponse = $dbh->query($requete)) != false) {
            $donnees = $reponse->fetchAll();
            $reponse->closeCursor();
        }
        return($donnees);
    }


    public function getCalculParMinute($dbh, $dateDeb, $dateFin, $calcul, $idLocalisation, $idModule) {
        $donnees = null;
		$conditionCalcul = $this->getCalculCondition($calcul);
        $requete = "SELECT CAST(horodatage as date) as jour, HOUR(horodatage) as heure, MINUTE(horodatage) as minute, $conditionCalcul 
				   	FROM t_donnee 
				   	WHERE horodatage >= '$dateDeb' AND horodatage <= '$dateFin' 
					AND module_id = '$idModule' 
					AND localisation_id = '$idLocalisation'";
		$requete .= " GROUP BY CAST(horodatage as date), HOUR(horodatage), MINUTE(horodatage) ORDER BY CAST(horodatage as date), HOUR(horodatage), MINUTE(horodatage)";
        if (($reponse = $dbh->query($requete)) != false) {
            $donnees 		= $reponse->fetchAll();
            $reponse->closeCursor();
        }
        return($donnees);
    }

    public function getCalculParSeconde($dbh, $dateDeb, $dateFin, $calcul, $idLocalisation, $idModule) {
        $donnees = null;
		$conditionCalcul = $this->getCalculCondition($calcul);
        $requete = "SELECT CAST(horodatage as date) as jour, HOUR(horodatage) as heure, MINUTE(horodatage) as minute, SECOND(horodatage) as seconde, $conditionCalcul 
				   	FROM t_donnee 
				   	WHERE horodatage >= '$dateDeb' AND horodatage <= '$dateFin' 
					AND module_id = '$idModule' 
					AND localisation_id = '$idLocalisation'";
		$requete .= " GROUP BY CAST(horodatage as date), HOUR(horodatage), MINUTE(horodatage), SECOND(horodatage) ORDER BY CAST(horodatage as date), HOUR(horodatage), MINUTE(horodatage), SECOND(horodatage)";
        if (($reponse = $dbh->query($requete)) != false) {
            $donnees = $reponse->fetchAll();
            $reponse->closeCursor();
        }
        return($donnees);
    }


    //	Retourne le nombre d'occurences trouvé lorsque les modules indiqués ont une valeur à la même heure et au même cycle
    //	Permet de rechercher la liste des premiers modules ( afin de distinguer les modules de défauts chaudières des modules de défauts bruleurs
    public function sqlCountSameHorodatage($dbh, $debut, $fin, $liste_modules, $id_localisations, $condition, $valeur, $valeur2, $nomValeur) {
        // Nombre d'identifiants devant avoir le même horodatage / même cycle
        $nombreId = count(explode(',', $liste_modules));
        $donnees = null;
        $liaison = null;
        $requete = "SELECT SQL_NO_CACHE COUNT(*) FROM ( ";
		$requete .= "SELECT COUNT(*) as count FROM t_donnee d ";
        $requete = $this->addParamSearch($requete, $id_localisations, $liste_modules, 1);
		$requete = $this->addCondition($requete, 'init', $condition, $valeur, $valeur2, $nomValeur);
		$requete .= " AND d.horodatage >= '$debut' AND d.horodatage <= '$fin'";
        $requete .= " GROUP BY d.horodatage,d.cycle ORDER BY d.horodatage,d.cycle";
		$requete .= ") as T1 WHERE T1.count = $nombreId";
        if(($reponse = $dbh->query($requete)) != false)
        {
            $donnees = $reponse->fetchColumn();
            $reponse->closeCursor();
        }
		// Recherche du nombre de fois ou les x modules ont le même horodatage
        return($donnees);
    }

	public function sqlGetValeurLivre($dbh, $dateDeb, $dateFin, $idLA, $idModule){
		$requete = "SELECT DISTINCT valeur2 FROM t_donnee d WHERE d.localisation_id = $idLA AND d.module_id = $idModule AND d.horodatage >= '$dateDeb' AND d.horodatage <= '$dateFin'";

        if(($reponse = $dbh->query($requete)) != false)
        {
            $donnees = $reponse->fetchAll();
            $reponse->closeCursor();
        }
        // Recherche du nombre de fois ou les x modules ont le même horodatage
        return($donnees);
	}

}
