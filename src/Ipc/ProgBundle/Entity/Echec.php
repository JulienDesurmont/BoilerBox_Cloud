<?php

namespace Ipc\ProgBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Echec
 *
 * @ORM\Table(name="t_echec")
 * @ORM\Entity(repositoryClass="Ipc\ProgBundle\Entity\EchecRepository")
 */
class Echec
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
     * @var \DateTime
     *
     * @ORM\Column(name="horodatage", type="datetime")
     */
    protected $horodatage;

    /**
     * @ORM\ManyToOne(targetEntity="Ipc\ProgBundle\Entity\Fichier")
    */
    protected $fichier;

    /**
     * @ORM\ManyToOne(targetEntity="Ipc\ProgBundle\Entity\Requete")
    */
    protected $requete;

    /**
     * @ORM\ManyToOne(targetEntity="Ipc\ProgBundle\Entity\Erreur")
     * @ORM\JoinColumn(nullable=false)
    */
    protected $erreur;   


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
     * @return Echec
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
     * Set fichier
     *
     * @param \Ipc\ProgBundle\Entity\Fichier $fichier
     * @return Echec
     */
    public function setFichier(\Ipc\ProgBundle\Entity\Fichier $fichier = null)
    {
        $this->fichier = $fichier;

        return $this;
    }

    /**
     * Get fichier
     *
     * @return \Ipc\ProgBundle\Entity\Fichier 
     */
    public function getFichier()
    {
        return $this->fichier;
    }

    /**
     * Set requete
     *
     * @param \Ipc\ProgBundle\Entity\Requete $requete
     * @return Echec
     */
    public function setRequete(\Ipc\ProgBundle\Entity\Requete $requete = null)
    {
        $this->requete = $requete;

        return $this;
    }

    /**
     * Get requete
     *
     * @return \Ipc\ProgBundle\Entity\Requete 
     */
    public function getRequete()
    {
        return $this->requete;
    }

    /**
     * Set erreur
     *
     * @param \Ipc\ProgBundle\Entity\Erreur $erreur
     * @return Echec
     */
    public function setErreur(\Ipc\ProgBundle\Entity\Erreur $erreur)
    {
        $this->erreur = $erreur;

        return $this;
    }

    /**
     * Get erreur
     *
     * @return \Ipc\ProgBundle\Entity\Erreur 
     */
    public function getErreur()
    {
        return $this->erreur;
    }
}
