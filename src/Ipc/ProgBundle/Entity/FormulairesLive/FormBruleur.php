<?php
namespace Ipc\ProgBundle\Entity\FormulairesLive;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * FormBruleur
 *
*/
class FormBruleur
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
    protected $adPrFlamme1;

    /**
     * @var string
     *
     */
    protected $adPrFlamme2;

    /**
     * @var string
     *
     */
    protected $adChBruleur1;

    /**
     * @var string
     *
     */
    protected $adChBruleur2;



    /**
     * @var integer
     */
    protected $idLocalisation;


    /**
     * Set adPrFlamme1
     *
     * @param string $adresse
     * @return FormBruleur
     */
    public function setAdPrFlamme1($adresse)
    {
	$this->adPrFlamme1 = $adresse;
        return $this;
    }

    /**
     * Get adPrFlamme1
     *
     * @return string
     */
    public function getAdPrFlamme1()
    {
        return $this->adPrFlamme1;
    }



    /**
     * Set adPrFlamme2
     *
     * @param string $adresse
     * @return FormBruleur
     */
    public function setAdPrFlamme2($adresse)
    {
        $this->adPrFlamme2 = $adresse;
        return $this;
    }

    /**
     * Get adPrFlamme2
     *
     * @return string
     */
    public function getAdPrFlamme2()
    {
        return $this->adPrFlamme2;
    }



    /**
     * Set adChBruleur1
     *
     * @param string $adresse
     * @return FormBruleur
     */
    public function setAdChBruleur1($adresse)
    {
        $this->adChBruleur1 = $adresse;
        return $this;
    }

    /**
     * Get adChBruleur1
     *
     * @return string
     */
    public function getAdChBruleur1()
    {
        return $this->adChBruleur1;
    }




    /**
     * Set adChBruleur2
     *
     * @param string $adresse
     * @return FormBruleur
     */
    public function setAdChBruleur2($adresse)
    {
        $this->adChBruleur2 = $adresse;
        return $this;
    }

    /**
     * Get adChBruleur2
     *
     * @return string
     */
    public function getAdChBruleur2()
    {
        return $this->adChBruleur2;
    }



    /**
     * Set idLocalisation
     *
     * @param integer $idLocalisation
     * @return FormBruleur
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
