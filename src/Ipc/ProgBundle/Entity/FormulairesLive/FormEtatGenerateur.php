<?php
namespace Ipc\ProgBundle\Entity\FormulairesLive;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * FormEtatGenerateur
 *
*/
class FormEtatGenerateur
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
     */
    protected $adNbEvenements;

    /**
     * @var string
     *
     */
    protected $adNbAlarmes;

    /**
     * @var string
     *
     */
    protected $adNbDefauts;


    /**
     * @var integer
     */
    protected $idLocalisation;


    /**
     * Set adNbEvenements
     *
     * @param string $adresse
     * @return FormEtatGenerateur
     */
    public function setAdNbEvenements($adresse)
    {
        $this->adNbEvenements = $adresse;

        return $this;
    }

    /**
     * Get adNbEvenements
     *
     * @return string 
     */
    public function getAdNbEvenements()
    {
        return $this->adNbEvenements;
    }



    /**
     * Set adNbAlarmes
     *
     * @param string $adresse
     * @return FormEtatGenerateur
     */
    public function setAdNbAlarmes($adresse)
    {
        $this->adNbAlarmes = $adresse;

        return $this;
    }

    /**
     * Get adNbAlarmes
     *
     * @return string
     */
    public function getAdNbAlarmes()
    {
        return $this->adNbAlarmes;
    }




    /**
     * Set adNbDefauts
     *
     * @param string $adresse
     * @return FormEtatGenerateur
     */
    public function setAdNbDefauts($adresse)
    {
        $this->adNbDefauts = $adresse;

        return $this;
    }

    /**
     * Get adNbDefauts
     *
     * @return string
     */
    public function getAdNbDefauts()
    {
        return $this->adNbDefauts;
    }




    /**
     * Set idLocalisation
     *
     * @param integer $idLocalisation
     * @return FormExploitationGenerateur
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

}
