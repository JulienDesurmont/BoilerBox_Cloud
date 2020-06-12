<?php
namespace Ipc\ProgBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

// Un Site peut pointer sur            		plusieurs localisations
//                                              plusieurs données


/** 
 * Site
 *
 * @ORM\Table(name="t_site")
 * @ORM\Entity(repositoryClass="Ipc\ProgBundle\Entity\SiteRepository")
 */
class Site
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
     * @var string
     *
     * @ORM\Column(name="intitule", type="string", length=50)
    */
    protected $intitule;

    /**
     * @var integer
     *
     * @ORM\Column(name="nb_automates", type="integer", options={"default":1})
    */
    protected $nbautomates;

    /**
     * @var string
     *
     * @ORM\Column(name="affaire", type="string", length=10, unique=true)
    */
    protected $affaire;

    /**
     * @var boolean
     * @ORM\Column(name="site_courant", type="boolean", options={"default":false})
    */
    protected $siteCourant;
    

    /**
     * @ORM\OneToMany(targetEntity="Ipc\ProgBundle\Entity\Localisation", mappedBy="site", cascade={"persist", "remove"})
    */
    protected $localisations;


    /**
     * @ORM\OneToMany(targetEntity="Ipc\ProgBundle\Entity\Rapport", mappedBy="site", cascade={"persist"})
    */
    protected $rapport;


   /**
    * @var \DateTime
    *
    * @ORM\Column(name="debut_exploitation", type="datetime", nullable=true)
    *
   */
    protected $debutExploitation;


   /**
    * @var \DateTime
    *
    * @ORM\Column(name="fin_exploitation", type="datetime", nullable=true)
    *
   */
    protected $finExploitation;

    public function __construct()
    {
		$this->localisations = new ArrayCollection();
		$this->debutExploitation = new \DateTime();
    }


    /**
     * Get debutExploitation
     *
     * @return \DateTime
    */
    public function getDebutExploitation()
    {
		return $this->debutExploitation;
    }

    /**
     * Set debutExploitation
     *
     * @param \DateTime $debutExploitation
     * @return Site
    */
    public function setDebutExploitation($debutExploitation)
    {
		$this->debutExploitation = $debutExploitation;	
		return $this;
    }


    /**
     * Get debutExploitationStr
     *
     * @return string
    */
    public function getDebutExploitationStr()
    {
		return $this->debutExploitation->format('Ymdhis');
    }


    /**
      * Get finExploitation
      *
      * @return \DateTime
    */
    public function getFinExploitation()
    {
		return $this->finExploitation;
    }


    /**
     * Set finExploitation
     *
     * @param \DateTime finExploitation
     * @return Site
    */
    public function setFinExploitation($finExploitation)
    {
		$this->finExploitation = $finExploitation;
		return $this;
    }


    /**
     * Get finExploitationStr
     *
     * @return string
    */
    public function getFinExploitationStr()
    {
		return $this->finExploitation->format('Ymdhis');
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
     * Set id
     *
     * @param integer $id
     * @return Site
     */
    public function setId($id)
    {
       	$this->id = $id;
    }


    /**
     * Set intitule
     *
     * @param string $intitule
     * @return Site
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
     * Set affaire
     *
     * @param string $affaire
     * @return Site
     */
    public function setAffaire($affaire)
    {
        $this->affaire = $affaire;

        return $this;
    }

    /**
     * Get affaire
     *
     * @return string 
     */
    public function getAffaire()
    {
        return $this->affaire;
    }

    /**
     * Add localisations
     *
     * @param \Ipc\ProgBundle\Entity\Localisation $localisations
     * @return Site
     */
    public function addLocalisation(\Ipc\ProgBundle\Entity\Localisation $localisations)
    {
        $this->localisations[] = $localisations;
        // On lie la localisation au site
        $localisations->setSite($this);
        return $this;
    }

    /**
     * Remove localisations
     *
     * @param \Ipc\ProgBundle\Entity\Localisation $localisations
     */
    public function removeLocalisation(\Ipc\ProgBundle\Entity\Localisation $localisations)
    {
        $this->localisations->removeElement($localisations);
    }

    /**
     * Get localisations
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getLocalisations()
    {
        return $this->localisations;
    }

    /**
     * Set nbautomates
     *
     * @param integer $nbautomates
     * @return Site
     */
    public function setNbautomates($nbautomates)
    {
        $this->nbautomates = $nbautomates;

        return $this;
    }

    /**
     * Get nbautomates
     *
     * @return integer 
     */
    public function getNbautomates()
    {
        return $this->nbautomates;
    }


    /**
     * Set siteCourant
     *
     * @param boolean $siteCourant
     * @return Site
     */
    public function setSiteCourant($siteCourant)
    {
        $this->siteCourant = $siteCourant;

        return $this;
    }

    /**
     * Get siteCourant
     *
     * @return boolean 
     */
    public function getSiteCourant()
    {
        return $this->siteCourant;
    }

    // Recherche et retour de l'id d'un site en fonction de l'affaire
	// Utilisé par ServiceImportBin
    public function SqlGetId($dbh)
    {
		$donnees = null;
        $requete = "SELECT ts.id AS id FROM t_site ts WHERE ts.affaire = '$this->affaire' LIMIT 1";
        if (($reponse = $dbh->query($requete)) != false) {
			$donnees = $reponse->fetchColumn();
			$reponse->closeCursor();
        }
			return($donnees);
    }

	// Recherche et retour de l'id d'un site en fonction du site courant
	// Utilisé par les controller : ListingController et GraphiqueController
	public function SqlGetIdCourant($dbh) {
		$donnees = null;
		$requete = "SELECT s.id AS id FROM t_site s WHERE s.site_courant = '1' LIMIT 1";
		if (($reponse = $dbh->query($requete)) != false) {
			$donnees = $reponse->fetchColumn();
			$reponse->closeCursor();
		}
		return($donnees);
	}

	public function SqlGetCourant($dbh, $param)
	{
		$donnees = null;
        $requete = "SELECT $param FROM t_site WHERE site_courant = '1' LIMIT 1";
		if (($reponse = $dbh->query($requete)) != false) {
        	$donnees = $reponse->fetchColumn();
            $reponse->closeCursor();
        }
        return($donnees);
	}


    // Récupération d'un id en fonction de l'affaire du site
    public function SqlGetIdAffaire($dbh, $affaire)
    {
    	$donnees = null;
    	$requete = "SELECT id FROM t_site WHERE affaire = '$affaire' LIMIT 1";
    	if (($reponse = $dbh->query($requete)) != false) {
        	$donnees = $reponse->fetchColumn();
            $reponse->closeCursor();
        }
        return($donnees);
    }


	//	Passe à false le boolean SiteCourant
	public function SqlUncheck($dbh, $id, $finExploitation)
	{
		// true = 1 / false = 0
		$requete = "UPDATE t_site SET site_courant = '0', fin_exploitation = '$finExploitation' WHERE id='".$id."';";
		$retour = $dbh->exec($requete);
        return($retour);
	}


	public function SqlActive($dbh)
	{
		$requete = "UPDATE t_site SET site_courant = '1' WHERE id='".$this->getId()."';";
		$retour = $dbh->exec($requete);
        return($retour);
	}


    public function SqlUpdate($dbh)
	{
		$requete = "UPDATE t_site SET intitule = '".$this->intitule."', affaire = '".$this->affaire."',site_courant = '".$this->siteCourant."',login_ftp = '".$this->loginFtp."',password_ftp = '".$this->passwordFtp."'";
		if ($this->debutExploitation) {
			$requete .= ", debut_exploitation = '".$this->getDebutExploitationStr()."'";
		} else {
			$requete .= ", debut_exploitation = null";
		}
		if ($this->finExploitation) {
        	$requete .= ", fin_exploitation = '".$this->getFinExploitationStr()."'";
		} else {
			$requete .= ", fin_exploitation = null";
		}
		$requete .= " WHERE id = '".$this->id."';";
        $retour = $dbh->exec($requete);
        return($retour);
    }


    public function SqlUpdateNoFtp($dbh)
    {
        $requete = "UPDATE t_site SET intitule = '".$this->intitule."', affaire = '".$this->affaire."',site_courant = '".$this->siteCourant."'";
        if ($this->debutExploitation) {
            $requete .= ", debut_exploitation = '".$this->getDebutExploitationStr()."'";
        } else {
            $requete .= ", debut_exploitation = null";
        }
        if ($this->finExploitation) {
            $requete .= ", fin_exploitation = '".$this->getFinExploitationStr()."'";
        } else {
            $requete .= ", fin_exploitation = null";
        }
        $requete .= " WHERE id = '".$this->id."';";
        $retour = $dbh->exec($requete);
        return($retour);
    }


    /**
     * Add rapport
     *
     * @param \Ipc\ProgBundle\Entity\Rapport $rapport
     * @return Site
     */
    public function addRapport(\Ipc\ProgBundle\Entity\Rapport $rapport)
    {
        $this->rapport[] = $rapport;

        return $this;
    }

    /**
     * Remove rapport
     *
     * @param \Ipc\ProgBundle\Entity\Rapport $rapport
     */
    public function removeRapport(\Ipc\ProgBundle\Entity\Rapport $rapport)
    {
        $this->rapport->removeElement($rapport);
    }

    /**
     * Get rapport
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getRapport()
    {
        return $this->rapport;
    }
}
