<?php
namespace Ipc\ProgBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Rapport
 *
 * @ORM\Table(name="t_rapport",
 * uniqueConstraints={@ORM\UniqueConstraint(name="UK_search", columns={"date_rapport", "localisation_id", "titre", "nom_technicien"})},
 * indexes={
 * @ORM\Index(name="MK_search", columns={"localisation_id","date_rapport"})})
 * @ORM\Entity(repositoryClass="Ipc\ProgBundle\Entity\RapportRepository")
 */
class Rapport
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_rapport", type="datetime")
    */
    protected $dateRapport;


    /**
     * @var string
     *
     * @ORM\Column(name="titre", type="string", length=255)
     *
     * @Assert\Length(
     * min = 1,
     * max = 150,
     * minMessage = "Nombre minimum de caractères : 1",
     * maxMessage = "Nombre maximum de caractères autorisé : 150"
     * )
    */
    protected $titre;

    /**
     * @var string
     *
     * @ORM\Column(name="nom_technicien", type="string", length=255)
     *
     * @Assert\Length(
     * min = 1,
     * max = 100,
     * minMessage = "Nombre minimum de caractères : 1",
     * maxMessage = "Nombre maximum de caractères autorisé : 100"
     * )
    */
    protected $nomTechnicien;

    /**
     * @var string
     *
     * @ORM\Column(name="login", type="string", length=255)
    */
    protected $login;

    /**
     * @var rapport
     *
     * @ORM\Column(name="rapport", type="text", nullable=false)
    */
    protected $rapport;

    /**
     * @ORM\OneToMany(targetEntity="Ipc\ProgBundle\Entity\FichierRapport", mappedBy="rapport", cascade={"persist"})
    */
    protected $fichiersrapport;

    /**
     * @ORM\ManyToOne(targetEntity="Ipc\ProgBundle\Entity\Site", inversedBy="rapport")
     * @ORM\JoinColumn(name="site_id", referencedColumnName="id", nullable=false)
    */
    protected $site;

    /**
     * @ORM\ManyToOne(targetEntity="Ipc\ProgBundle\Entity\Localisation", inversedBy="rapport")
     * @ORM\JoinColumn(name="localisation_id", referencedColumnName="id", nullable=true)
    */
    protected $localisation;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->fichiersrapport = new \Doctrine\Common\Collections\ArrayCollection();
		$this->dateRapport = new \DateTime();
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
     * Set dateRapport
     *
     * @param \DateTime $dateRapport
     * @return Rapport
     */
    public function setDateRapport($dateRapport)
    {
        $this->dateRapport = $dateRapport;

        return $this;
    }

    /**
     * Get dateRapport
     *
     * @return \DateTime 
     */
    public function getDateRapport()
    {
        return $this->dateRapport;
    }

    /**
     * Set titre
     *
     * @param string $titre
     * @return Rapport
     */
    public function setTitre($titre)
    {
        $this->titre = $titre;

        return $this;
    }

    /**
     * Get titre
     *
     * @return string 
     */
    public function getTitre()
    {
        return $this->titre;
    }

    /**
     * Set nomTechnicien
     *
     * @param string $nomTechnicien
     * @return Rapport
     */
    public function setNomTechnicien($nomTechnicien)
    {
        $this->nomTechnicien = $nomTechnicien;

        return $this;
    }

    /**
     * Get nomTechnicien
     *
     * @return string 
     */
    public function getNomTechnicien()
    {
        return $this->nomTechnicien;
    }

    /**
     * Set login
     *
     * @param string $login
     * @return Rapport
     */
    public function setLogin($login)
    {
        $this->login = $login;

        return $this;
    }

    /**
     * Get login
     *
     * @return string 
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * Set rapport
     *
     * @param string $rapport
     * @return Rapport
     */
    public function setRapport($rapport)
    {
        $this->rapport = $rapport;

        return $this;
    }

    /**
     * Get rapport
     *
     * @return string 
     */
    public function getRapport()
    {
        return $this->rapport;
    }

    /**
     * Set localisation
     *
     * @param \Ipc\ProgBundle\Entity\Localisation $localisation
     * @return Rapport
     */
    public function setLocalisation(\Ipc\ProgBundle\Entity\Localisation $localisation)
    {
        $this->localisation = $localisation;

        return $this;
    }

    /**
     * Get localisation
     *
     * @return \Ipc\ProgBundle\Entity\Localisation 
     */
    public function getLocalisation()
    {
        return $this->localisation;
    }


    /**
     * Remove Localisation
     *
     * @return Rapport
    */
    public function removeLocalisation()
    {
		$this->localisation = null;
		return $this;
    }

    /**
     * Add fichiersrapport
     *
     * @param \Ipc\ProgBundle\Entity\FichierRapport $fichiersrapport
     * @return Rapport
     */
    public function addFichiersrapport(\Ipc\ProgBundle\Entity\FichierRapport $fichiersrapport)
    {
        $this->fichiersrapport[] = $fichiersrapport;
		$fichiersrapport->setRapport($this);
        return $this;
    }

    public function addReverseRapport(\Ipc\ProgBundle\Entity\FichierRapport $fichiersrapport)
    {
		$fichiersrapport->setRapport($this);
		return $this;
    }

    /**
     * Remove fichiersrapport
     *
     * @param \Ipc\ProgBundle\Entity\FichierRapport $fichiersrapport
     */
    public function removeFichiersrapport(\Ipc\ProgBundle\Entity\FichierRapport $fichiersrapport)
    {
        $this->fichiersrapport->removeElement($fichiersrapport);
    }

    /**
     * Remove all fichiersrapport
     *
    */
    public function removeAllFichiersrapport()
    {
		$this->fichiersrapport = null;
    }

    /**
     * Get fichiersrapport
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getFichiersrapport()
    {
        return $this->fichiersrapport;
    }

    /**
     * Set site
     *
     * @param \Ipc\ProgBundle\Entity\Site $site
     * @return Rapport
     */
    public function setSite(\Ipc\ProgBundle\Entity\Site $site)
    {
        $this->site = $site;

        return $this;
    }

    /**
     * Get site
     *
     * @return \Ipc\ProgBundle\Entity\Site 
     */
    public function getSite()
    {
        return $this->site;
    }
}
