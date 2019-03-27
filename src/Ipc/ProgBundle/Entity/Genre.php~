<?php

namespace Ipc\ProgBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Genre
 *
 * @ORM\Table(name="t_genre")
 * @ORM\Entity(repositoryClass="Ipc\ProgBundle\Entity\GenreRepository")
 */
class Genre
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
     * @ORM\Column(name="numero_genre", type="smallint", unique=true)
    */
    protected $numeroGenre;

    /**
     * @var string
     *
     * @ORM\Column(name="intitule_genre", type="string", length=50, unique=true)
    */
    protected $intituleGenre;

    /**
     * @var string
     * @ORM\column(name="couleur", type="string", length=20, nullable=true)
    */
    protected $couleur;


    /**
     * @ORM\OneToMany(targetEntity="Ipc\ProgBundle\Entity\Module", mappedBy="genre")
    */
    protected $modules;


    public function __construct()
    {
		$this->modules = new ArrayCollection();
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
     * @return Id
     */
    public function setId($id)
    {
        $this->id = $id;
    }


    /**
     * Add modules
     *
     * @param \Ipc\ProgBundle\Entity\Module $modules
     * @return Genre
     */
    public function addModule(\Ipc\ProgBundle\Entity\Module $modules)
    {
        $this->modules[] = $modules;

        return $this;
    }

    /**
     * Remove modules
     *
     * @param \Ipc\ProgBundle\Entity\Module $modules
     */
    public function removeModule(\Ipc\ProgBundle\Entity\Module $modules)
    {
        $this->modules->removeElement($modules);
    }

    /**
     * Get modules
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getModules()
    {
        return $this->modules;
    }

    /**
     * Set numeroGenre
     *
     * @param integer $numeroGenre
     * @return Genre
     */
    public function setNumeroGenre($numeroGenre)
    {
        $this->numeroGenre = $numeroGenre;

        return $this;
    }

    /**
     * Get numeroGenre
     *
     * @return integer 
     */
    public function getNumeroGenre()
    {
        return $this->numeroGenre;
    }

    /**
     * Set intituleGenre
     *
     * @param string $intituleGenre
     * @return Genre
     */
    public function setIntituleGenre($intituleGenre)
    {
        $this->intituleGenre = $intituleGenre;

        return $this;
    }

    /**
     * Get intituleGenre
     *
     * @return string 
     */
    public function getIntituleGenre()
    {
        return $this->intituleGenre;
    }


    //  *********************************************************************************************************************************************************************************************************************
    //                            F o n c t i o n s    U t i l i s é e s    par les services        [ ServiceImportIpc.php - ServiceRattrapImportBin.php - ServiceRequeteType.php - ServiceRapports.php ]
    //  *********************************************************************************************************************************************************************************************************************
    public function SqlGetAllGenre($dbh)
    {
        $donnees = null;
        $requete = "SELECT g.id AS id, g.intitule_genre AS intitule_genre, g.numero_genre as numero_genre FROM t_genre g";
        if (($reponse = $dbh->query($requete)) != false) {
            $donnees = $reponse->fetchAll();
            $reponse->closeCursor();
        }
        return($donnees);
    }

    // Retourne le numéro du genre associè à l'id
    public function SqlGetId($dbh)
    {
		$donnees = null;
        $requete = "SELECT tg.id AS id FROM t_genre tg WHERE tg.numero_genre = '$this->numeroGenre' LIMIT 1";
        if (($reponse = $dbh->query($requete)) != false) {
	    	$donnees = $reponse->fetchColumn();
	    	$reponse->closeCursor();
       	}
		return($donnees);
    }

    /**
     * Set couleur
     *
     * @param string $couleur
     * @return Genre
     */
    public function setCouleur($couleur)
    {
        $this->couleur = $couleur;

        return $this;
    }

    /**
     * Get couleur
     *
     * @return string 
     */
    public function getCouleur()
    {
        return $this->couleur;
    }
}
