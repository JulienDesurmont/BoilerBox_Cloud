<?php

namespace Ipc\UserBundle\Entity;

use FOS\UserBundle\Entity\User as BaseUser;
//use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User
 * @ORM\Entity
 * @ORM\Table(name="t_user")
 */
class User extends BaseUser
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
    */
    protected $id;

    /**
	 * Un utilisateur pour enregistrer plusieurs requêtes personnelles
	 *
	 * @ORM\OneToMany(targetEntity="Ipc\ConfigurationBundle\Entity\Requete", mappedBy="utilisateur", cascade={"persist", "remove"})
	 */
	protected $requetes;



    public function __construct()
    {
        parent::__construct();
		$this->requetes = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set new activation token
     * @return User
    */
    public function changeActivation() {
		if($this->enabled == true) {
			$this->setEnabled(false);
		} else {
			$this->setEnabled(true);
		}
		return $this;
    }

	/**
	 * Add requetes
	 *
	 * @param \Ipc\ConfigurationBundle\Entity\Requete $requetes
	 * @return Utilisateur
	 */
	public function addRequete(\Ipc\ConfigurationBundle\Entity\Requete $requetes)
	{
		$this->requetes[] = $requetes;
		return $this;
	}

	/**
	 * Remove requetes
	 *
	 * @param \Ipc\ConfigurationBundle\Entity\Requete $requetes
	 */
	public function removeRequete(\Ipc\ConfigurationBundle\Entity\Requete $requetes)
	{
		$this->requetes->removeElement($requetes);
	}

	
	/**
	 * Get requetes
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getRequetes()
	{
		return $this->requetes;
 	}
}
