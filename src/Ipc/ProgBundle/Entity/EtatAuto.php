<?php
namespace Ipc\ProgBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;



/** 
 * EtatAuto
 *
*/
class EtatAuto
{
    protected $calcul;

	protected $file;

	public function setCalcul($calcul){
		$this->calcul = $calcul;
	}

   /**
     * Get calcul
     *
    */
    public function getCalcul()
    {
        return $this->calcul;
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
        return $this->getUploadDir().'tmp/';
    }


   	// Déplacement du fichier dans le repertoire des fichiers téléchargés
	// Retourne l'url du fichier
   	public function deplacement()
   	{
        $this->file->move($this->getUpdatedbDir(), $this->getNomdOrigine());
		return ($this->getUpdatedbDir().'/'.$this->getNomdOrigine());
   	}

}
