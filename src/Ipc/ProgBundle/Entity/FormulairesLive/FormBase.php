<?php
namespace Ipc\ProgBundle\Entity\FormulairesLive;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * FormBase
 *
*/
class FormBase
{
    /**
     * @var string
     *
     */
    protected $adBase;

    /**
     * @var string
     *
     * @Assert\Length(
     * min = 1,
     * max = 255,
     * minMessage = "Nombre minimum de caractères : 1",
     * maxMessage = "Nombre maximum de caractères autorisé : 255"
     * )
     */
    protected $labelBase;

    /**
     * @var string
     *
     * @Assert\Length(
     * min = 1,
     * max = 255,
     * minMessage = "Nombre minimum de caractères : 1",
     * maxMessage = "Nombre maximum de caractères autorisé : 255"
     * )
     */
    protected $familleBase;


    /**
     * @var integer
     */
    protected $idLocalisation;


    /**
     * Set adBase
     *
     * @param string $adresse
     * @return FormBase
     */
    public function setAdBase($adresse)
    {
	$this->adBase = $adresse;
        return $this;
    }

    /**
     * Get adBase
     *
     * @return string
     */
    public function getAdBase()
    {
        return $this->adBase;
    }


    /**
     * Set labelBase
     *
     * @param string $label
     * @return FormBase
     */
    public function setLabelBase($label)
    {
        $this->labelBase = $label;
        return $this;
    }

    /**
     * Get labelBase
     *
     * @return string
     */
    public function getLabelBase()
    {
        return $this->labelBase;
    }


    /**
     * Set familleBase
     *
     * @param string $famille
     * @return FormBase
     */
    public function setFamilleBase($famille)
    {
        $this->familleBase = $famille;
        return $this;
    }

    /**
     * Get familleBase
     *
     * @return string
     */
    public function getFamilleBase()
    {
        return $this->familleBase;
    }


    /**
     * Set idLocalisation
     *
     * @param integer $idLocalisation
     * @return FormBase
     */
    public function setIdLocalisation($idLocalisation)
    {
        $this->idLocalisation = $idLocalisation;

        return $this;
    }


    /**
     * Get idLocalisation
     *
     * @return string
     */
    public function getIdLocalisation()
    {
        return $this->idLocalisation;
    }

}
