<?php

namespace Ipc\ProgBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * FichierIpc
 *
 * @ORM\Table(name="t_fichieripc")
 * @ORM\Entity(repositoryClass="Ipc\ProgBundle\Entity\FichierIpcRepository")
*/
class FichierIpc
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
      * @ORM\Column(name="nom", type="string", length=255, unique=true)
     */
     protected $nom;

     /**
      * @var \DateTime
      *
      * @ORM\Column(name="date_traitement", type="datetime")
     */
     protected $dateTraitement;

     /**
      * @var integer
      *
      * @ORM\Column(name="nombre_messages", type="smallint")
     */
     protected $nombreMessages;

    /**
     * @ORM\OneToMany(targetEntity="Ipc\ProgBundle\Entity\Module", mappedBy="fichieripc")
    */
    protected $modules;

    protected $file;



    public function __construct()
    {
      	$this->dateTraitement = new \Datetime();
       	$this->nombreMessages = 0;
		$this->modules = new ArrayCollection();
    }	


    /**
     * Get file
     *
     * @return string
    */
    public function getFile()
    {
		return $this->file;
    }

    public function getNomdOrigine()
    {
		if (isset($this->file)) {
	    	return strtolower($this->file->getClientOriginalName());
		} else {
	    	return null;
		}
    }

    public function getEncodeType($str)
    {
		return(mb_detect_encoding($str, 'UTF-8', true));
    }

    public function updateDateTraitement()
    {
		$this->dateTraitement = new \Datetime();
    }

    /**
     * Set file
     *
     */
    public function setFile(UploadedFile $file)
    {
		$this->file = $file;
    }

    //Repertoire des fichiers telechargés
    public function getUploadDir()
    {
		return __DIR__.'/../../../../web/uploads/';
    }

    //Chemin vers le repertoire de téléchargement du fichier csv Table_echange_IPC (tables message/fichier/) 
    public function getUpdatedbDir()
    {
		return $this->getUploadDir().'csv/bddipc/';
    }


   //Déplacement du fichier dans le repertoire des fichiers téléchargés
   public function deplacement()
   {
        $this->file->move($this->getUpdatedbDir(), $this->nom);
		return 0;
   }


   //Récupération du contenu du fichier 
   public function getContenu()
   {
		// On déplace le fichier uploadé dans le répertoire /uploads/bddipc 
		$nomfichier = $this->getUpdatedbDir().$this->nom;
		$ligne = 0;
		$contenus_fichier = file($nomfichier);
		foreach ($contenus_fichier as $contenu) {
			// Si on detecte une ligne qui n'est pas formatée en UTF-8 on ne traite pas le fichier
			if ($this->getEncodeType($contenu) != 'UTF-8') {
				$liste_messages = array();	
				$this->setNombreMessages(0);
				return $liste_messages;
			}
			$contenu_ligne = explode(';',$contenu);
			// Parcours du fichier pour placer son contenu dans un tableau	
			$liste_messages[$ligne]["numero_genre"] 	= $this->toint($contenu_ligne[0]);
			$liste_messages[$ligne]["intitule_genre"]	= trim($contenu_ligne[1]);
			$liste_messages[$ligne]["type_categorie"] 	= trim($contenu_ligne[2]);
			$liste_messages[$ligne]["numero_module"] 	= $this->toint($contenu_ligne[3]);
			$liste_messages[$ligne]["intitule_module"] 	= trim($contenu_ligne[4]);
			$liste_messages[$ligne]["numero_message"] 	= $this->toint($contenu_ligne[5]);
			$liste_messages[$ligne]["intitule_message"] = trim($contenu_ligne[6]);
			$liste_messages[$ligne]["type_unite"]		= trim($contenu_ligne[7]);
			$ligne ++;
		}
		// On stock le nombre de lignes traitées dans la variable nombreMessages
		$this->setNombreMessages($ligne);
		return $liste_messages;
   }

   public function toint($valeur)
   {
		return($nouvelle_valeur=preg_replace('/[^\-\d]*(\-?\d*).*/','$1',$valeur))?$nouvelle_valeur:'0';
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
     * @param integer
     * @return id
     */
    public function setId($id)
    {
    	$this->id = $id;
    }

    /**
     * Set nom
     *
     * @param string $nom
     * @return FichierIpc
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get nom
     *
     * @return string 
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * Set dateTraitement
     *
     * @param \DateTime $dateTraitement
     * @return FichierIpc
     */
    public function setDateTraitement($dateTraitement)
    {
        $this->dateTraitement = $dateTraitement;

        return $this;
    }

    /**
     * Get dateTraitement
     *
     * @return \DateTime 
     */
    public function getDateTraitement()
    {
        return $this->dateTraitement;
    }

   /**
    * Get dateTraitementStr
    *
    * @return string
   */
    public function getDateTraitementStr()
    {
	$datestr = $this->getDateTraitement()->format('YmdHis');
	return $datestr;
    }
   
    /**
     * Set nombreMessages
     *
     * @param integer $nombreMessages
     * @return FichierIpc
     */
    public function setNombreMessages($nombreMessages)
    {
        $this->nombreMessages = $nombreMessages;
        return $this;
    }

    /**
     * Get nombreMessages
     *
     * @return integer 
     */
    public function getNombreMessages()
    {
        return $this->nombreMessages;
    }

    /**
     * Add modules
     *
     * @param \Ipc\ProgBundle\Entity\Module $modules
     * @return FichierIpc
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


    // Fonction d'insertion d'une nouvelle donnée en base
    public function SqlInsert($dbh)
    {
        $requete = "INSERT INTO t_fichieripc ( nom, date_traitement, nombre_messages )
                    VALUES ('$this->nom','".$this->getDateTraitementStr()."','$this->nombreMessages');";
        $retour = $dbh->exec($requete);
    }

    public function SqlGetId($dbh)
    {
		$donnees = null;
       	$requete = "SELECT tf.id  AS id FROM t_fichieripc tf WHERE tf.nom = '$this->nom' LIMIT 1";
        if (($reponse = $dbh->query($requete)) != false) {
			$donnees = $reponse->fetchColumn();
			$reponse->closeCursor();
        }
		return($donnees);
    }
}
