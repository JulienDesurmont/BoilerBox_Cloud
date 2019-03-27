<?php
namespace Ipc\ProgBundle\Entity\FormulairesLive;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * FormCombustible
 *
*/
class FormCombustible
{

    /**
     * @var string
     *
     * @Assert\Length(
     * min = 1,
     * max = 50,
     * minMessage = "Nombre minimum de caractères : 1",
     * maxMessage = "Nombre maximum de caractères autorisé : 50"
     * )
     */
    protected $label;


    /**
     * @var string
     *
     * @Assert\Length(
     * min = 1,
     * max = 50,
     * minMessage = "Nombre minimum de caractères : 1",
     * maxMessage = "Nombre maximum de caractères autorisé : 50"
     * )
     */
    protected $label2;


    /**
     * @var string
     *
     */
    protected $adCombustibleBruleur1;

    /**
     * @var string
     *
     */
    protected $adCombustibleBruleur2;


    /**
     * @var integer
     */
    protected $idLocalisation;


    /**
     * Set adCombustibleBruleur1
     *
     * @param string $adresse
     * @return FormCombustible
     */
    public function setAdCombustibleBruleur1($adresse)
    {
	$this->adCombustibleBruleur1 = $adresse;
        return $this;
    }

    /**
     * Get adCombustibleBruleur1
     *
     * @return string
     */
    public function getAdCombustibleBruleur1()
    {
        return $this->adCombustibleBruleur1;
    }



    /**
     * Set adCombustibleBruleur2
     *
     * @param string $adresse
     * @return FormCombustible
     */
    public function setAdCombustibleBruleur2($adresse)
    {
        $this->adCombustibleBruleur2 = $adresse;
        return $this;
    }

    /**
     * Get adCombustibleBruleur2
     *
     * @return string
     */
    public function getAdCombustibleBruleur2()
    {
        return $this->adCombustibleBruleur2;
    }



    /**
     * Set idLocalisation
     *
     * @param integer $idLocalisation
     * @return FormCombustible
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

    /**
     * Set label
     *
     * @param string $label
     * @return FormBruleur
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }


    /**
     * Set label2
     *
     * @param string $label2
     * @return FormBruleur
     */
    public function setLabel2($label2)
    {
        $this->label2 = $label2;
        return $this;
    }

    /**
     * Get label2
     *
     * @return string
     */
    public function getLabel2()
    {
        return $this->label2;
    }



}
