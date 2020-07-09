<?php

namespace Ipc\ProgBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * Module
 *
 * @ORM\Table(name="t_module",
 * uniqueConstraints={@ORM\UniqueConstraint(name="UK_search", columns={"categorie","numero_module","numero_message","mode_id"})})
 * @ORM\Entity( repositoryClass="Ipc\ProgBundle\Entity\ModuleRepository" )
 */
class Module
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
     * @ORM\Column(name="intitule_module", type="string", length=150)
     */
    protected $intituleModule;

    /**
     * @var string
     *
     * @ORM\Column(name="categorie", type="string", length=2)
    */
    protected $categorie;

    /**
     * @var integer
     *
     * @ORM\Column(name="numero_module", type="smallint")
     */
    protected $numeroModule;

    /**
     * @var integer
     *
     * @ORM\Column(name="numero_message", type="smallint")
    */
    protected $numeroMessage;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="string", length=255)
    */
    protected $message;


    /**
     * @var string
     *
     * @ORM\Column(name="unite", type="string", length=15)
    */
    protected $unite;

    /**
     * @var boolean
     *
     * @ORM\Column(name="has_donnees", type="boolean", options={"defaut":false})
    */
    protected $hasDonnees;

    /**
     * @ORM\ManyToOne(targetEntity="Ipc\ProgBundle\Entity\Genre", inversedBy="modules")
     * @ORM\JoinColumn(name="genre_id", referencedColumnName="id", nullable=false)
    */
    protected $genre;

    /**
     * @ORM\ManyToOne(targetEntity="Ipc\ProgBundle\Entity\FichierIpc", inversedBy="modules")
     * @ORM\JoinColumn(name="fichieripc_id", referencedColumnName="id", nullable=false)
    */
    protected $fichieripc;

    /**
     * @ORM\ManyToOne(targetEntity="Ipc\ProgBundle\Entity\Mode", inversedBy="modules")
     * @ORM\JoinColumn(name="mode_id", referencedColumnName="id")
    */
    protected $mode;

	public function __construct(){
        $this->hasDonnees = false;
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
     * @return Module
     */
    public function setId($id)
    {
        $this->id = $id;
    }


    /**
     * Set categorie
     *
     * @param string $categorie
     * @return Module
     */
    public function setCategorie($categorie)
    {
        $this->categorie = $categorie;

        return $this;
    }

    /**
     * Get categorie
     *
     * @return string 
     */
    public function getCategorie()
    {
        return $this->categorie;
    }

    /**
     * Set numeroMessage
     *
     * @param integer $numeroMessage
     * @return Module
     */
    public function setNumeroMessage($numeroMessage)
    {
        $this->numeroMessage = $numeroMessage;

        return $this;
    }

    /**
     * Get numeroMessage
     *
     * @return integer 
     */
    public function getNumeroMessage()
    {
        return $this->fillNumber($this->numeroMessage);
    }

    /**
     * Set message
     *
     * @param string $message
     * @return Module
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string 
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set unite
     *
     * @param string $unite
     * @return Module
     */
    public function setUnite($unite)
    {
        $this->unite = $unite;

        return $this;
    }

    /**
     * Get unite
     *
     * @return string 
     */
    public function getUnite()
    {
        return $this->unite;
    }

    /**
     * Set genre
     *
     * @param \Ipc\ProgBundle\Entity\Genre $genre
     * @return Module
     */
    public function setGenre(\Ipc\ProgBundle\Entity\Genre $genre)
    {
        $this->genre = $genre;

        return $this;
    }

    /**
     * Get genre
     *
     * @return \Ipc\ProgBundle\Entity\Genre 
     */
    public function getGenre()
    {
        return $this->genre;
    }

    /**
     * Set fichieripc
     *
     * @param \Ipc\ProgBundle\Entity\FichierIpc $fichieripc
     * @return Module
     */
    public function setFichieripc(\Ipc\ProgBundle\Entity\FichierIpc $fichieripc)
    {
        $this->fichieripc = $fichieripc;

        return $this;
    }

    /**
     * Get fichieripc
     *
     * @return \Ipc\ProgBundle\Entity\FichierIpc 
     */
    public function getFichieripc()
    {
        return $this->fichieripc;
    }

    /**
     * Set numeroModule
     *
     * @param integer $numeroModule
     * @return Module
     */
    public function setNumeroModule($numeroModule)
    {
        $this->numeroModule = $numeroModule;

        return $this;
    }

    /**
     * Get numeroModule
     *
     * @return integer 
     */
    public function getNumeroModule()
    {
        return $this->fillNumber($this->numeroModule);
    }

    /**
     * Set intituleModule
     *
     * @param string $intituleModule
     * @return Module
     */
    public function setIntituleModule($intituleModule)
    {
        $this->intituleModule = $intituleModule;

        return $this;
    }

    /**
     * Get intituleModule
     *
     * @return string 
     */
    public function getIntituleModule()
    {
        return $this->intituleModule;
    }


    // Nouvelle Version pour inclure le code du programme
    public function SqlGetModules($dbh) {
        $donnees = null;
        $requete = "SELECT tm.id AS id,tm.intitule_module AS intitule_module,tm.categorie AS categorie,tm.numero_module AS numero_module,tm.numero_message AS numero_message,tm.message AS message,tm.unite AS unite,tm.genre_id AS genre_id,tm.fichieripc_id AS fichieripc_id,tm.mode_id as mode_id FROM t_module tm";
        if (($reponse = $dbh->query($requete)) != false) {
            $donnees = $reponse->fetchAll();
            $reponse->closeCursor();
        }
        return($donnees);
    }

	// Récupération du module et du genre
	public function SqlGetModulesGenreAndUnit($dbh) {
		$donnees = null;
		$requete = "SELECT m.id AS id, m.intitule_module AS intituleModule, m.categorie AS categorie, m.numero_module AS numeroModule, m.numero_message AS numeroMessage, m.message AS message, m.unite AS unite, m.genre_id AS idGenre, m.has_donnees AS hasDonnees FROM t_module m";
		if (($reponse = $dbh->query($requete)) != false) {
			$donnees = $reponse->fetchAll();
			$reponse->closeCursor();
		}
		return($donnees);
	}

	// Fonction d'insertion d'une nouvelle donnée en base
	public function SqlInsert($dbh, $liste_donnees) {
		$requete = "INSERT INTO t_module ( genre_id, fichieripc_id, intitule_module, categorie, numero_module, numero_message, message, unite ) VALUES $liste_donnees;";
		$retour = $dbh->exec($requete);
	}


	public function SqlGetId($dbh) {
		$donnees = null;
		$requete = "SELECT SQL_NO_CACHE tm.id AS id FROM t_module tm WHERE tm.categorie = '$this->categorie' AND tm.numero_message = '$this->numeroMessage' AND tm.numero_module = '$this->numeroModule LIMIT 1";
        if (($reponse = $dbh->query($requete)) != false) {
			$donnees = $reponse->fetchColumn();
			$reponse->closeCursor();
       	}
		return($donnees);
    }


	public function SqlGetModuleByCode($dbh) {
		$donnees = null;
		$requete = "SELECT SQL_NO_CACHE tm.id AS id,tm.intitule_module AS intitule_module,tm.message AS message,tm.unite AS unite,tm.genre_id AS genre_id,tm.fichieripc_id AS fichieripc_id FROM t_module tm WHERE tm.categorie = '$this->categorie' AND tm.numero_message = '$this->numeroMessage' AND tm.numero_module = '$this->numeroModule' LIMIT 1"; 
		if (($reponse = $dbh->query($requete)) != false) {
			$donnees = $reponse->fetchAll();
			$reponse->closeCursor();
       	}
		return($donnees);
   	}


	public function SqlUpdateByParams($dbh, $verif_intitule, $verif_message, $verif_genre, $verif_unite, $verif_fichieripc) {
		$requete = "UPDATE t_module tm SET tm.fichieripc_id = '$verif_fichieripc',";
		if ($verif_intitule != null) { 
			$requete .= "tm.intitule_module = \"$verif_intitule\","; 
		}
		if ($verif_message != null) { 
			$requete .= "tm.message = \"$verif_message\","; 
		}
		if ($verif_genre != null) { 
			$requete .= "tm.genre_id = '$verif_genre',"; 
		}
		if ($verif_unite != null) { 
			$requete .= "tm.unite = '$verif_unite',"; 
		}
		$requete = substr($requete, 0, -1);
		$requete.=" WHERE tm.id = '$this->id'";
		$retour = $dbh->exec($requete);
	}


	//	Compte le nombre de liens :  module - localisation si il existe
	public function sqlGetNbLien($dbh, $module_id, $localisation_id) {
		$donnees = 0;
		$requete = "SELECT COUNT(*) FROM localisation_module WHERE module_id = '$module_id' AND localisation_id='$localisation_id'";
		if (($reponse = $dbh->query($requete)) != false) {
			$donnees = $reponse->fetchColumn();
			$reponse->closeCursor();
		}
		return($donnees);
	}


	public function sqlGetLiens($dbh,$localisation_id) {
		$donnees = null;
		$requete = "SELECT module_id FROM localisation_module WHERE localisation_id='$localisation_id'";
		if (($reponse = $dbh->query($requete)) != false) {
			$donnees = $reponse->fetchAll();
			$reponse->closeCursor();
		}
		return($donnees);
	}


	public function sqlGetAllLiens($dbh, $liste_localisation) {
		$donnees = null;
		$requete = "SELECT * FROM localisation_module WHERE localisation_id IN (".$liste_localisation.")";
		if(($reponse = $dbh->query($requete)) != false) {
			$donnees = $reponse->fetchAll();
			$reponse->closeCursor();
		}
		return($donnees);
	}

    /**
     * Set mode
     *
     * @param \Ipc\ProgBundle\Entity\Mode $mode
     * @return Module
     */
    public function setMode(\Ipc\ProgBundle\Entity\Mode $mode = null) {
		$this->mode = $mode;
		return $this;
	}

    /**
     * Get mode
     *
     * @return \Ipc\ProgBundle\Entity\Mode 
     */
    public function getMode() {
		return $this->mode;
    }

	private function fillNumber($number) {
	    $pattern = '/^.$/';
	    if (preg_match($pattern, $number)) {
	        $numberRetour = "0$number";
	    } else {
	        $numberRetour = $number;
	    }
	    return($numberRetour);
	}

   	public function getCode(){
		return $this->getCategorie().$this->getNumeroModule().$this->getNumeroMessage();		
	}


    /**
     * Set hasDonnees
     *
     * @param boolean $hasDonnees
     * @return Module
     */
    public function setHasDonnees($hasDonnees)
    {
        $this->hasDonnees = $hasDonnees;

        return $this;
    }

    /**
     * Get hasDonnees
     *
     * @return boolean 
     */
    public function getHasDonnees()
    {
        return $this->hasDonnees;
    }
}
