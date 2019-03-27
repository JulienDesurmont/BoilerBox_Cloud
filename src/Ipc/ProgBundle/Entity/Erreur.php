<?php

namespace Ipc\ProgBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Erreur
 *
 * @ORM\Table(name="t_erreur")
 * @ORM\Entity(repositoryClass="Ipc\ProgBundle\Entity\ErreurRepository")
 */
class Erreur
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
     * @ORM\Column(name="code", type="string", length=5, unique=true)
     */
    protected $code;

    /**
     * @var string
     *
     * @ORM\Column(name="message_erreur", type="string", length=255, unique=true)
     */
    protected $messageErreur;



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
     * Set code
     *
     * @param string $code
     * @return Erreur
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }


    /**
     * Get texte
     *
     * @return string
    */
    public function getTexte($patterns, $replacements)
    {
		if (isset($patterns[0])) {
			$message = preg_replace($patterns, $replacements, $this->getMessageErreur());
			return($message);
		} else {
			return($this->getMessageErreur());
		}
    }

    /**
     * Set messageErreur
     *
     * @param string $messageErreur
     * @return Erreur
     */
    public function setMessageErreur($messageErreur)
    {
        $this->messageErreur = $messageErreur;

        return $this;
    }

    /**
     * Get messageErreur
     *
     * @return string 
     */
    public function getMessageErreur()
    {
        return $this->messageErreur;
    }
}
