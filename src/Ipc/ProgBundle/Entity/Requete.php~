<?php

namespace Ipc\ProgBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Requete
 *
 * @ORM\Table(name="t_requete")
 * @ORM\Entity(repositoryClass="Ipc\ProgBundle\Entity\RequeteRepository")
 */
class Requete
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
     * @var integer
     *
     * @ORM\Column(name="localisation", type="smallint")
     */
    protected $localisation;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="string", length=255)
     */
    protected $message;

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
     * @return Requete
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
     * Set localisation
     *
     * @param integer $localisation
     * @return Requete
     */
    public function setLocalisation($localisation)
    {
        $this->localisation = $localisation;

        return $this;
    }

    /**
     * Get localisation
     *
     * @return integer 
     */
    public function getLocalisation()
    {
        return $this->localisation;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return Requete
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string 
     */
    public function getMessage()
    {
        return $this->message;
    }

}
