<?php

namespace Ipc\ProgBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * FichierIpc
 *
 * @ORM\Table(name="t_fichierrapport")
 * @ORM\Entity(repositoryClass="Ipc\ProgBundle\Entity\FichierRapportRepository")
*/
class FichierRapport
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
      * @ORM\Column(name="nom", type="string", length=255)
     */
     protected $nom;

     /**
      * @Assert\File(maxSize="20000000",uploadErrorMessage="Impossible d'importer le fichier",maxSizeMessage="Taille maximum du fichier autorisé:20Mo")
     */
     protected $file;

    /**
     * @ORM\ManyToOne(targetEntity="Ipc\ProgBundle\Entity\Rapport", inversedBy="fichiersrapport")
     * @ORM\JoinColumn(name="rapport_id", referencedColumnName="id", nullable=false)
    */
    protected $rapport;


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
     * Set nom
     *
     * @param string $nom
     * @return FichierRapport
     */
    public function setNom($nom)
    {
	//	Créer le nom du fichier en supprimant les eventuels espace
        $this->nom = str_replace(' ','',$nom);
        return$this;
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
     * Get file
     *
     * @return string
    */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set file
     *
     */
    public function setFile(UploadedFile $file)
    {
        $this->file = $file;
    }


    /**
     * Set rapport
     *
     * @param \Ipc\ProgBundle\Entity\Rapport $rapport
     * @return FichierRapport
     */
    public function setRapport(\Ipc\ProgBundle\Entity\Rapport $rapport)
    {
        $this->rapport = $rapport;

        return $this;
    }

    /**
     * Get rapport
     *
     * @return \Ipc\ProgBundle\Entity\Rapport 
     */
    public function getRapport()
    {
        return $this->rapport;
    }



    //Repertoire des fichiers téléchargés
    public function getUploadDir()
    {
        return __DIR__.'/../../../../web/uploads/';
    }

    //Chemin vers le répértoire de téléchargement des fichier de rapports
    public function getInterventionsDir()
    {
        return $this->getUploadDir().'interventions/';
    }


   // Déplacement du fichier dans le répértoire des fichiers du rapport
   public function deplacement(){
		if (($this->rapport->getId() == null) || ($this->nom == null)) {
	   		echo "Paramètre de fichier manquant : Enregistrement impossible"; 
	    	return 1;
		} else {
        	$this->file->move($this->getInterventionsDir(),$this->rapport->getId().'_'.$this->nom);
	    	return 0;
		}
   }

}
