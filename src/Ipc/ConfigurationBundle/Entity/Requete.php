<?php
// src/Ipc/ConfigurationBundle/Entity/Requete.php
namespace Ipc\ConfigurationBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Requete
 *
 * @ORM\Table(name="t_requete_perso")
 * @ORM\Entity(repositoryClass="Ipc\ConfigurationBundle\Entity\RequeteRepository")
 * @UniqueEntity(
 *    fields={"appellation", "compte"},
 *    message="Ce nom de requête est déjà utilisé"
 * )
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
     * @ORM\Column(type="date", name="date_enregistrement")
     */
    protected $dateEnregistrement;

   /**
    * @var string
    *
    * @Assert\Length(
    * min = "2",
    * max = "85",
    * minMessage = "Nombre minimum de caractères : 2",
    * maxMessage = "Nombre maximum de caractères autorisé : 85"
    * )
    * @ORM\Column(type="text", name="appellation")
    */
    protected $appellation;


   /**
    * @var string
    *
    * @ORM\Column(type="text", name="requete")
    */
    protected $requete;


   /**
    * @var string
    *
    * @ORM\Column(type="text", name="description", nullable=true)
    */
    protected $description;

   /**
    * @var string
    *
    * @ORM\Column(type="text", name="type")
    * @Assert\Choice({"listing", "graphique"});
   */
    protected $type;

   /**
    * @var string
    *
    * @ORM\Column(type="text", name="createur")
   */
    protected $createur;

   /**
    * @var string
    *
    * @ORM\Column(type="text", name="compte", nullable=true)
    * @Assert\Choice({"Admin", "Technicien", "Client"})
   */
    protected $compte;


   /**
    *
    * @ORM\ManyToOne(targetEntity="Ipc\UserBundle\Entity\User", inversedBy="requetes", cascade={"persist"})
    */
    protected $utilisateur;

    public function __construct() {
        $this->dateEnregistrement = new \Datetime();
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
     * Set dateEnregistrement
     *
     * @param \DateTime $dateEnregistrement
     * @return Requete
     */
    public function setDateEnregistrement($dateEnregistrement)
    {
        $this->dateEnregistrement = $dateEnregistrement;

        return $this;
    }


    /**
     * Get dateEnregistrement
     *
     * @return \DateTime
     */
    public function getDateEnregistrement()
    {
        return $this->dateEnregistrement;
    }

    /**
     * Set appellation
     *
     * @param string $appellation
     * @return Requete
     */
    public function setAppellation($appellation)
    {
        $this->appellation = $appellation;

        return $this;
    }

    /**
     * Get appellation
     *
     * @return \appellation
     */
    public function getAppellation()
    {
        return $this->appellation;
    }

    /**
     * Set requete
     *
     * @param string $requete
     * @return Requete
     */
    public function setRequete($requete)
    {
        $this->requete = $requete;

        return $this;
    }

    /**
     * Get requete
     *
     * @return string
     */
    public function getRequete()
    {
        return $this->requete;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Requete
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set utilisateur
     *
     * @param \Ipc\UserBundle\Entity\User $utilisateur
     * @return Requete
     */
    public function setUtilisateur(\Ipc\UserBundle\Entity\User $utilisateur = null)
    {
        $this->utilisateur = $utilisateur;
        $utilisateur->addRequete($this);
        return $this;
    }

    /**
     * Get utilisateur
     *
     * @return \Ipc\UserBundle\Entity\User
     */
    public function getUtilisateur()
    {
        return $this->utilisateur;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return Requete
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set createur
     *
     * @param string $createur
     * @return Requete
     */
    public function setCreateur($createur)
    {
        $this->createur = $createur;

        return $this;
    }

    /**
     * Get createur
     *
     * @return string
     */
    public function getCreateur()
    {
        return $this->createur;
    }


    /**
     * Set compte
     *
     * @param string $compte
     * @return Requete
     */
    public function setCompte($compte)
    {
        $this->compte = $compte;

        return $this;
    }

    /**
     * Get compte
     *
     * @return string
     */
    public function getCompte()
    {
        return $this->compte;
    }


}

