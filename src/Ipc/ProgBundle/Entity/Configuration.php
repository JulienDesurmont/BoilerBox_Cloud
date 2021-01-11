<?php

namespace Ipc\ProgBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/** 
 * Configuration
 *
 * @ORM\Table(name="t_configuration")
 * @ORM\Entity(repositoryClass="Ipc\ProgBundle\Entity\ConfigurationRepository")
 */

class Configuration
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
    */
    protected $id;

    /**
     * @var Parametre
     *
     * @ORM\Column(name="parametre", type="string", unique=true, nullable=false)
    */
    protected $parametre;

    /**
     * @var Valeur
     * 
     * @ORM\Column(name="valeur", type="text", nullable=false)
    */
    protected $valeur;


    /**
     * @var Designation
     *
     * @ORM\Column(name="designation", type="string", nullable=false)
    */
    protected $designation;

	/**
	 * @var parametreAdmin
	 *
	 * @ORM\Column(name="parametre_admin", type="boolean", options={"default":false}, nullable=false)
    */
    protected $parametreAdmin;

    /**
     * @var parametreTechnicien
     *
     * @ORM\Column(name="parametre_technicien", type="boolean", options={"default":false}, nullable=true)
    */
    protected $parametreTechnicien;





    public function __construct()
    {
		$this->dateMES = new \Datetime();
		$this->parametreAdmin = true;
        $this->parametreTechnicien = false;
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
     * @param $integer $id
     *
     * @return Configuration
    */
    public function setId($id)
    {
		$this->id = $id;
		return $this;
    }

    /**
     * Set parametreAdmin
     *
     * @param boolean $parametreAdmin
     * @return Configuration
     */
    public function setParametreAdmin($parametreAdmin)
    {
        $this->parametreAdmin = $parametreAdmin;
        return $this;
    }

    /**
     * Get parametreAdmin
     *
     * @return string
     */
    public function getParametreAdmin()
    {
        return $this->parametreAdmin;
    }



    /**
     * Set parametreTechnicien
     *
     * @param boolean $parametreTechnicien
     * @return Configuration
     */
    public function setParametreTechnicien($parametreTechnicien)
    {
        $this->parametreTechnicien = $parametreTechnicien;
        return $this;
    }

    /**
     * Get parametreTechnicien
     *
     * @return string
     */
    public function getParametreTechnicien()
    {
        return $this->parametreTechnicien;
    }





    /**
     * Set parametre
     *
     * @param string $parametre
     * @return Configuration
     */
    public function setParametre($parametre)
    {
        $this->parametre = $parametre;
        return $this;
    }

    /**
     * Get parametre
     *
     * @return string 
     */
    public function getParametre()
    {
        return $this->parametre;
    }

    /**
     * Set valeur
     *
     * @param string $valeur
     * @return Configuration
     */
    public function setValeur($valeur)
    {
        $this->valeur = $valeur;

        return $this;
    }

    /**
     * Get valeur
     *
     * @return string 
     */
    public function getValeur()
    {
        return $this->valeur;
    }

    /**
     * Set designation
     *
     * @param string $designation
     * @return Configuration
     */
    public function setDesignation($designation)
    {
        $this->designation = $designation;

        return $this;
    }

    /**
     * Get designation
     *
     * @return string 
     */
    public function getDesignation()
    {
        return $this->designation;
    }


    // *********************************************************************************************************************************************************************************************************************
    //                                                                  F o n c t i o n s    U t i l i s é e s    par le module       [ C O N F I G U R A T I O N ]
    // *********************************************************************************************************************************************************************************************************************
    public function SqlInsert($dbh) {
        $donnees = '("'.$this->parametre.'","'.$this->designation.'","'.$this->valeur.'","'.$this->parametreAdmin.'","'.$this->parametreTechnicien.'")';
        $requete = "INSERT INTO t_configuration (parametre, designation, valeur, parametre_admin, parametre_technicien ) VALUES $donnees;";
        $retour = $dbh->exec($requete);
        return($retour);
    }


    public function SqlUpdateValue($dbh) {
		$requete = 'UPDATE t_configuration SET valeur="'.$this->valeur.'" WHERE id="'.$this->id.'";';
		$retour = $dbh->exec($requete);
		return($retour);
    }

    public function SqlUpdate($dbh) {
		$requete = 'UPDATE t_configuration SET parametre="'.$this->parametre.'",designation="'.$this->designation.'",valeur="'.$this->valeur.'",parametre_admin="'.$this->parametreAdmin.'",parametre_technicien="'.$this->parametreTechnicien.'" WHERE id="'.$this->id.'";';
		$retour = $dbh->exec($requete);
		return($retour);
    }

    public function SqlDelete($dbh) {
		$requete = "DELETE FROM t_configuration WHERE id='".$this->id."';";
        $retour = $dbh->exec($requete);
        return($retour);
    }

    // Récupération d'un parametre dont l'intitulé est passé en parametre de la fonction
    public function SqlGetId($dbh, $intitule) {
        $donnees = null;
        $requete = "SELECT id FROM t_configuration WHERE parametre = '$intitule';";
        if (($reponse = $dbh->query($requete)) != false) {
            $donnees = $reponse->fetchColumn();
            $reponse->closeCursor();
        }
        return($donnees);
    }

    // *********************************************************************************************************************************************************************************************************************
    //                                                                  F o n c t i o n s    U t i l i s é e s    par tous les modules et les services
    // *********************************************************************************************************************************************************************************************************************
    // Récupération d'un parametre dont l'intitulé est passé en parametre de la fonction
	public function SqlGetParam($dbh, $intitule) {
		$donnees = null;
		$requete = "SELECT valeur FROM t_configuration WHERE parametre = '$intitule';";
		if (($reponse = $dbh->query($requete)) != false) {
			$donnees = $reponse->fetchColumn();
			$reponse->closeCursor();
		}
		return($donnees);
	}
}
