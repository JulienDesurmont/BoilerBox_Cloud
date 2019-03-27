<?php
namespace Ipc\ProgBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;


/** 
 * Calcul
 *
 * @ORM\Table(name="t_calcul", uniqueConstraints={@ORM\UniqueConstraint(name="UK_search", columns={"intitule"})})
 * @ORM\Entity(repositoryClass="Ipc\ProgBundle\Entity\CalculRepository")
 */
class Calcul
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
     * @ORM\Column(name="description", type="string", length=255)
    */
    protected $description;

    /**
     * @var string
     * @ORM\Column(name="intitule", type="string", length=255)
    */
    protected $intitule;

    /**
     * @var integer
     *
     * @ORM\Column(name="numero_calcul", type="integer", nullable=false)
    */
    protected $numeroCalcul;


    /**
     * @ORM\OneToMany(targetEntity="Ipc\ProgBundle\Entity\Etat", mappedBy="calcul", cascade={"remove"})
    */
    protected $etats;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->etats = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set description
     *
     * @param string $description
     * @return Calcul
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set intitule
     *
     * @param string $intitule
     * @return Calcul
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
     * Add etats
     *
     * @param \Ipc\ProgBundle\Entity\Etat $etats
     * @return Calcul
     */
    public function addEtat(\Ipc\ProgBundle\Entity\Etat $etats)
    {
        $this->etats[] = $etats;

        return $this;
    }

    /**
     * Remove etats
     *
     * @param \Ipc\ProgBundle\Entity\Etat $etats
     */
    public function removeEtat(\Ipc\ProgBundle\Entity\Etat $etats)
    {
        $this->etats->removeElement($etats);
    }

    /**
     * Get etats
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getEtats()
    {
        return $this->etats;
    }

    /**
     * Set numeroCalcul
     *
     * @param integer $numeroCalcul
     * @return Calcul
     */
    public function setNumeroCalcul($numeroCalcul)
    {
        $this->numeroCalcul = $numeroCalcul;

        return $this;
    }

    /**
     * Get numeroCalcul
     *
     * @return integer 
     */
    public function getNumeroCalcul()
    {
        return $this->numeroCalcul;
    }
}
