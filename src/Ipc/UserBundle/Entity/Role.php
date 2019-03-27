<?php
 
#namespace MyApp\UserBundle\Entity;
namespace Ipc\UserBundle\Entity;
 
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\Role\RoleInterface;
use Doctrine\Common\Collections\ArrayCollection;
 
/**
 * Ipc\UserBundle\Entity\Role
 * 
 * @ORM\Table(name="t_role")
 * @ORM\Entity(repositoryClass="Ipc\UserBundle\Entity\RoleRepository")
 */
class Role implements RoleInterface
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
 
    /**
     * @ORM\Column(name="name", type="string", length=30)
     */
    private $name;
 
    /**
     * @ORM\Column(name="role", type="string", length=20)
     */
    private $role;
 
     
    public function __toString()  
    {  
        return $this->getName();  
    }
     
    /**
     * @see RoleInterface
     */
    public function getRole()
    {
        return $this->role;
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
     * Set name
     *
     * @param string $name
     * @return Role
     */
    public function setName($name)
    {
        $this->name = $name;
     
        return $this;
    }
 
    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }
 
    /**
     * Set role
     *
     * @param string $role
     * @return Role
     */
    public function setRole($role)
    {
        $this->role = $role;
     
        return $this;
    }
 
}
