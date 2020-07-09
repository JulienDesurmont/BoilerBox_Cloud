<?php
namespace Ipc\ProgBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;


/** 
 * RequeteType
 *
 * @ORM\Table(name="t_requeteType", uniqueConstraints={@ORM\UniqueConstraint(name="UK_search", columns={"numero","type_requete"})})
 * @ORM\Entity(repositoryClass="Ipc\ProgBundle\Entity\RequeteTypeRepository")
 */
class RequeteType
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
     * @var integer
     *
     * @ORM\Column(name="numero", type="integer", options={"default":1})
    */
    protected $numero;

    /**
     * @var string
     *
     * @ORM\Column(name="requete", type="text")
    */
    protected $requete;

    /**
     * @var string
     *
     * @ORM\Column(name="intitule", type="string", length=255)
    */
    protected $intitule;

    /**
     * @var string
     *
     * @ORM\Column(name="type_requete", type="string", length=50)
    */
    protected $typeRequete;

    /**
     * @var string
     *
     * @ORM\Column(name="periode", type="string", length=10)
    */
    protected $periode;

    /**
     * @var integer
     *
     * @ORM\Column(name="nb_periode", type="integer", options={"default":1})
    */
    protected $nbPeriode;

    /**
     * @var string
     *
     * @ORM\Column(name="periodique", type="string", length=10)
    */
    protected $periodique;

    /**
     * @var integer
     *
     * @ORM\Column(name="nb_periodique", type="integer", options={"default":1})
    */
    protected $nbPeriodique;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_debut_rapport", type="datetime")
     */
    protected $dateDebutRapport;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_fin_rapport", type="datetime")
     */
    protected $dateFinRapport;

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
     * Set numero
     *
     * @param integer $numero
     * @return RequeteType
     */
    public function setNumero($numero)
    {
        $this->numero = $numero;

        return $this;
    }

    /**
     * Get numero
     *
     * @return integer 
     */
    public function getNumero()
    {
        return $this->numero;
    }

    /**
     * Set requete
     *
     * @param string $requete
     * @return RequeteType
     */
    public function setRequete($requete)
    {
        $this->requete = $requete;

        return $this;
    }

    /**
     * Get requete
     *
     * @return string 
     */
    public function getRequete()
    {
        return $this->requete;
    }

    /**
     * Set typeRequete
     *
     * @param string $typeRequete
     * @return RequeteType
     */
    public function setTypeRequete($typeRequete)
    {
        $this->typeRequete = $typeRequete;

        return $this;
    }

    /**
     * Get typeRequete
     *
     * @return string 
     */
    public function getTypeRequete()
    {
        return $this->typeRequete;
    }

    /**
     * Set periode
     *
     * @param string $periode
     * @return RequeteType
     */
    public function setPeriode($periode)
    {
        $this->periode = $periode;

        return $this;
    }

    /**
     * Get periode
     *
     * @return string 
     */
    public function getPeriode()
    {
        return $this->periode;
    }

    /**
     * Set nbPeriode
     *
     * @param integer $nbPeriode
     * @return RequeteType
     */
    public function setNbPeriode($nbPeriode)
    {
        $this->nbPeriode = $nbPeriode;

        return $this;
    }

    /**
     * Get nbPeriode
     *
     * @return integer 
     */
    public function getNbPeriode()
    {
        return $this->nbPeriode;
    }

    /**
     * Set dateDebutRapport
     *
     * @param \DateTime $dateDebutRapport
     * @return RequeteType
     */
    public function setDateDebutRapport($dateDebutRapport)
    {
        $this->dateDebutRapport = $dateDebutRapport;

        return $this;
    }

    /**
     * Get dateDebutRapport
     *
     * @return \DateTime 
     */
    public function getDateDebutRapport()
    {
        return $this->dateDebutRapport;
    }

    /**
     * Set dateFinRapport
     *
     * @param \DateTime $dateFinRapport
     * @return RequeteType
     */
    public function setDateFinRapport($dateFinRapport)
    {
        $this->dateFinRapport = $dateFinRapport;

        return $this;
    }

    /**
     * Get dateFinRapport
     *
     * @return \DateTime 
     */
    public function getDateFinRapport()
    {
        return $this->dateFinRapport;
    }


   /**
    * Get dateRapportStr
    *
    * @return string
   */
    public function getDateRapportStr($typeRapport)
    {
        $dateFormat = null;
        switch($typeRapport)
        {
                case 'debut':
                        $dateFormat = $this->dateDebutRapport->format('YmdHis');
                        break;
                case 'fin':
                        $dateFormat = $this->dateFinRapport->format('YmdHis');
                        break;
        }
        return $dateFormat;
    }


   /**
    * Get dateRapportStrFormat
    *
    * @return string
   */
    public function getDateRapportStrFormat($typeRapport)
    {
        $dateStrFormat = null;
        switch($typeRapport)
        {
                case 'debut':
                        $dateStrFormat = $this->dateDebutRapport->format('Y/m/d H:i:s');
                        //$dateStrFormat .= ' 00:00:00';
                        break;
                case 'fin':
                        $dateStrFormat = $this->dateFinRapport->format('Y/m/d H:i:s');
                        //$dateStrFormat .= ' 00:00:00';
                        break;
        }
        return $dateStrFormat;
    }




    /**
     * Set intitule
     *
     * @param string $intitule
     * @return RequeteType
     */
    public function setIntitule($intitule)
    {
        $this->intitule = $intitule;

        return $this;
    }

    /**
     * Get intitule
     *
     * @return string 
     */
    public function getIntitule()
    {
        return $this->intitule;
    }

    /**
     * Set periodique
     *
     * @param string $periodique
     * @return RequeteType
     */
    public function setPeriodique($periodique)
    {
        $this->periodique = $periodique;

        return $this;
    }

    /**
     * Get periodique
     *
     * @return string 
     */
    public function getPeriodique()
    {
        return $this->periodique;
    }

    /**
     * Set nbPeriodique
     *
     * @param integer $nbPeriodique
     * @return RequeteType
     */
    public function setNbPeriodique($nbPeriodique)
    {
        $this->nbPeriodique = $nbPeriodique;

        return $this;
    }

    /**
     * Get nbPeriodique
     *
     * @return integer 
     */
    public function getNbPeriodique()
    {
        return $this->nbPeriodique;
    }


        //      Insertion du nouvel Etat
        public function sqlInsert($dbh)
        {
                $donnees = '("'.$this->numero.'","'.$this->requete.'","'.$this->intitule.'","'.$this->typeRequete.'","'.$this->periode.'","'.$this->nbPeriode.'","'.$this->periodique.'","'.$this->nbPeriodique.'","'.$this->getDateRapportStr('debut').'","'.$this->getDateRapportStr('fin').'")';
                $requete = "INSERT INTO t_requeteType (numero,requete,intitule,type_requete,periode,nb_periode,periodique,nb_periodique,date_debut_rapport,date_fin_rapport) VALUES $donnees;";
                $retour = $dbh->exec($requete);
                return($retour);
        }


        public function sqlGetAll($dbh)
        {
                $donnees = null;
                $requete = 'SELECT SQL_NO_CACHE * FROM t_requeteType';
                if(($reponse = $dbh->query($requete)) != false)
                {
                        $donnees = $reponse->fetchAll();
                        $reponse->closeCursor();
                }
                return($donnees);
        }

        public function sqlGetAllByType($dbh,$typeRequete)
        {
                $donnees = null;
                $requete = "SELECT SQL_NO_CACHE * FROM t_requeteType WHERE type_requete = '$typeRequete'";
                if(($reponse = $dbh->query($requete)) != false)
                {
                        $donnees = $reponse->fetchAll();
                        $reponse->closeCursor();
                }
                return($donnees);
        }


	public function sqlGetByTypeIntitule($dbh)
	{
		$donnees = null;
		$requete = "SELECT SQL_NO_CACHE * FROM t_requeteType WHERE type_requete = '".$this->typeRequete."' AND intitule = '".$this->intitule."';";
		if(($reponse = $dbh->query($requete)) != false)
                {
                        $donnees = $reponse->fetchAll();
                        $reponse->closeCursor();
                }
                return($donnees);
	}


	public function sqlDelete($dbh)
	{
		$requete = "DELETE FROM t_requeteType WHERE numero = '".$this->getNumero()."' AND type_requete = '".$this->getTypeRequete()."';";
		$retour = $dbh->exec($requete);
                return($retour);
	}

        public function sqlUpdateNJAR($dbh)
        {
                $donnees = null;
                $requete = "UPDATE t_requeteType SET date_debut_rapport = '".$this->getDateRapportStr('debut')."', date_fin_rapport = '".$this->getDateRapportStr('fin')."' WHERE type_requete = '".$this->typeRequete."' AND numero = '".$this->numero."'";
		//echo "Update : $requete";
                $retour = $dbh->exec($requete);
                return($retour);
        }



}
