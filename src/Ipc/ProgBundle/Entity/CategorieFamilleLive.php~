<?php

namespace Ipc\ProgBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
  * CategorieFamilleLive
  *
  * @ORM\Table(name="t_categoriefamillelive")
  * @ORM\Entity(repositoryClass="Ipc\ProgBundle\Entity\CategorieFamilleLiveRepository")
  * @UniqueEntity("designation")
*/
class CategorieFamilleLive
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
     * @ORM\Column(name="designation", type="string", length=100, nullable=false, unique=true) 
    */
    protected $designation;


    /**
     * @var string
     *
     * @ORM\Column(name="classe", type="string", length=100, nullable=false)
    */
    protected $classe;



    /**
     * @var string
     *
     * @ORM\Column(name="informations", type="string", length=255)
    */
    protected $informations;


    /**
     * @var string
     * @ORM\column(name="couleur", type="string", length=20)
     * @Assert\Length(
     * min = 1,
     * max = 20,
     * minMessage = "Nombre minimum de caractères : 1",
     * maxMessage = "Nombre maximum de caractères autorisé : 20"
     * )
     */
    protected $couleur;


    /**
     * @ORM\OneToMany(targetEntity="Ipc\ProgBundle\Entity\DonneeLive", mappedBy="categorie", cascade={"remove"})
    */
    protected $donneesLive;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->donneesLive = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set designation
     *
     * @param string $designation
     * @return CategorieFamilleLive
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

    /**
     * Set informations
     *
     * @param string $informations
     * @return CategorieFamilleLive
     */
    public function setInformations($informations)
    {
        $this->informations = $informations;

        return $this;
    }

    /**
     * Get informations
     *
     * @return string 
     */
    public function getInformations()
    {
        return $this->informations;
    }

    /**
     * Set couleur
     *
     * @param string $couleur
     * @return CategorieFamilleLive
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

    /**
     * Add donneesLive
     *
     * @param \Ipc\ProgBundle\Entity\DonneeLive $donneesLive
     * @return CategorieFamilleLive
     */
    public function addDonneesLive(\Ipc\ProgBundle\Entity\DonneeLive $donneesLive)
    {
        $this->donneesLive[] = $donneesLive;

        return $this;
    }

    /**
     * Remove donneesLive
     *
     * @param \Ipc\ProgBundle\Entity\DonneeLive $donneesLive
     */
    public function removeDonneesLive(\Ipc\ProgBundle\Entity\DonneeLive $donneesLive)
    {
        $this->donneesLive->removeElement($donneesLive);
    }

    /**
     * Get donneesLive
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getDonneesLive()
    {
        return $this->donneesLive;
    }

    /**
     * Set classe
     *
     * @param string $classe
     * @return CategorieFamilleLive
     */
    public function setClasse($classe)
    {
        $this->classe = $classe;

        return $this;
    }

    /**
     * Get classe
     *
     * @return string 
     */
    public function getClasse()
    {
        return $this->classe;
    }
}
