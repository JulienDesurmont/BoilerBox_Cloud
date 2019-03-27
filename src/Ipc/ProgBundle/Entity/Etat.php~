<?php
namespace Ipc\ProgBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;


/** 
 * Etat
 *
 * @ORM\Table(name="t_etat", uniqueConstraints={@ORM\UniqueConstraint(name="UK_search", columns={"intitule"})})
 * @ORM\Entity(repositoryClass="Ipc\ProgBundle\Entity\EtatRepository")
 */
class Etat
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
     * @ORM\Column(name="intitule", type="string", length=100)
    */
    protected $intitule;

    /**
     * @var string
     *
     * @ORM\Column(name="periode", type="string", length=90)
    */
    protected $periode;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_activation", type="datetime", nullable=true)
     */
    protected $dateActivation;

    /**
     * @var string
     *
     * @ORM\Column(name="liste_modules", type="string", length=90)
    */
    protected $listeModules;

    /**
     * @var string
     *
     * @ORM\Column(name="liste_designations", type="string", length=255)
    */
    protected $listeDesignations;
    
    /**
     * @var string
     *
     * @ORM\Column(name="option1", type="string", length=255, nullable=true)
    */
    protected $option1;

    /**
     * @var string
     *
     * @ORM\Column(name="option2", type="string", length=255, nullable=true)
    */
    protected $option2;

    /**
     * @var string
     *
     * @ORM\Column(name="option3", type="string", length=255, nullable=true)
    */
    protected $option3;

    /**
     * @var string
     *
     * @ORM\Column(name="option4", type="string", length=255, nullable=true)
    */
    protected $option4;

    /**
     * @var string
     *
     * @ORM\Column(name="option5", type="string", length=255, nullable=true)
    */
    protected $option5;

    /**
     * @var string
     *
     * @ORM\Column(name="option6", type="string", length=255, nullable=true)
    */
    protected $option6;


    /**
     * @var string
     *
     * @ORM\Column(name="optionExclusion1", type="string", length=255, nullable=true)
    */
    protected $optionExclusion1;

    /**
     * @var string
     *
     * @ORM\Column(name="optionExclusion2", type="string", length=255, nullable=true)
    */
    protected $optionExclusion2;



    /**
     * @var boolean
     * @ORM\Column(name="active", type="boolean")
    */
    protected $active;

    /**
     * @ORM\ManyToOne(targetEntity="Ipc\ProgBundle\Entity\Calcul", inversedBy="etats")
    */
    protected $calcul;

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
     * Set intitule
     *
     * @param string $intitule
     * @return Etat
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
     * Set periode
     *
     * @param string $periode
     * @return Etat
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
     * Set dateActivation
     *
     * @param \DateTime $dateActivation
     * @return Etat
     */
    public function setDateActivation($dateActivation)
    {
        $this->dateActivation = $dateActivation;

        return $this;
    }

    /**
     * Get dateActivation
     *
     * @return \DateTime 
     */
    public function getDateActivation()
    {
        return $this->dateActivation;
    }

   /**
    * Get dateActivationStr
    *
    * @return string
   */
    public function getDateActivationStr()
    {
        return $this->dateActivation->format('Y-m-d H:i:s');
    }


    /**
     * Set listeModules
     *
     * @param string $listeModules
     * @return Etat
     */
    public function setListeModules($listeModules)
    {
        $this->listeModules = $listeModules;

        return $this;
    }

    /**
     * Get listeModules
     *
     * @return string 
     */
    public function getListeModules()
    {
        return $this->listeModules;
    }

    /**
     * Set active
     *
     * @param boolean $active
     * @return Etat
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active
     *
     * @return boolean 
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set calcul
     *
     * @param \Ipc\ProgBundle\Entity\Calcul $calcul
     * @return Etat
     */
    public function setCalcul(\Ipc\ProgBundle\Entity\Calcul $calcul = null)
    {
        $this->calcul = $calcul;

        return $this;
    }

    /**
     * Get calcul
     *
     * @return \Ipc\ProgBundle\Entity\Calcul 
     */
    public function getCalcul()
    {
        return $this->calcul;
    }

    /**
     * Set listeDesignations
     *
     * @param string $listeDesignations
     * @return Etat
     */
    public function setListeDesignations($listeDesignations)
    {
        $this->listeDesignations = $listeDesignations;

        return $this;
    }

    /**
     * Get listeDesignations
     *
     * @return string 
     */
    public function getListeDesignations()
    {
        return $this->listeDesignations;
    }

    /**
     * Set option1
     *
     * @param string $option1
     * @return Etat
     */
    public function setListeCompteur($option1)
    {
        $this->option1 = $option1;

        return $this;
    }

    /**
     * Get option1
     *
     * @return string 
     */
    public function getOption1()
    {
        return $this->option1;
    }

    /**
     * Set option1
     *
     * @param string $option1
     * @return Etat
     */
    public function setOption1($option1)
    {
        $this->option1 = $option1;

        return $this;
    }

    /**
     * Set option2
     *
     * @param string $option2
     * @return Etat
     */
    public function setOption2($option2)
    {
        $this->option2 = $option2;

        return $this;
    }

    /**
     * Get option2
     *
     * @return string 
     */
    public function getOption2()
    {
        return $this->option2;
    }

    /**
     * Set option3
     *
     * @param string $option3
     * @return Etat
     */
    public function setOption3($option3)
    {
        $this->option3 = $option3;

        return $this;
    }

    /**
     * Get option3
     *
     * @return string 
     */
    public function getOption3()
    {
        return $this->option3;
    }


    /**
     * Set optionExclusion1
     *
     * @param string $optionExclusion1
     * @return Etat
     */
    public function setOptionExclusion1($optionExclusion1)
    {
        $this->optionExclusion1 = $optionExclusion1;

        return $this;
    }

    /**
     * Get optionExclusion1
     *
     * @return string 
     */
    public function getOptionExclusion1()
    {
        return $this->optionExclusion1;
    }

    /**
     * Set optionExclusion2
     *
     * @param string $optionExclusion2
     * @return Etat
     */
    public function setOptionExclusion2($optionExclusion2)
    {
        $this->optionExclusion2 = $optionExclusion2;

        return $this;
    }

    /**
     * Get optionExclusion2
     *
     * @return string 
     */
    public function getOptionExclusion2()
    {
        return $this->optionExclusion2;
    }

    /**
     * Set option4
     *
     * @param string $option4
     * @return Etat
     */
    public function setOption4($option4)
    {
        $this->option4 = $option4;

        return $this;
    }

    /**
     * Get option4
     *
     * @return string 
     */
    public function getOption4()
    {
        return $this->option4;
    }

    /**
     * Set option5
     *
     * @param string $option5
     * @return Etat
     */
    public function setOption5($option5)
    {
        $this->option5 = $option5;

        return $this;
    }

    /**
     * Get option5
     *
     * @return string
     */
    public function getOption5()
    {
        return $this->option5;
    }

    /**
     * Set option6
     *
     * @param string $option6
     * @return Etat
     */
    public function setOption6($option6)
    {
        $this->option6 = $option6;

        return $this;
    }

    /**
     * Get option6
     *
     * @return string
     */
    public function getOption6()
    {
        return $this->option6;
    }


}
