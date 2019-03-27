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



    public function __construct()
    {
        parent::__construct();
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
    
}
