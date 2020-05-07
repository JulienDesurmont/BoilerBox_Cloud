<?php

namespace Ipc\ProgBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/** 
 * InfosLocalisation 
 *
 * @ORM\Table(name="t_infoslocalisation")
 * @ORM\Entity(repositoryClass="Ipc\ProgBundle\Entity\InfosLocalisationRepository")
*/
class InfosLocalisation
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
     * @var \DateTime
     *
     * @ORM\Column(name="horodatageDeb", type="datetime", nullable=true)
     */
    protected $horodatageDeb;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="horodatageFin", type="datetime", nullable=true)
     */
    protected $horodatageFin;

    /**
     * @var boolean
     *
     * @ORM\Column(name="periodeCourante", type="boolean", options={"default":false})
    */
    protected $periodeCourante;

 
    /**
     * @ORM\ManyToOne(targetEntity="Ipc\ProgBundle\Entity\Localisation" , inversedBy="infosLocalisation")
     * @ORM\JoinColumn(name="localisation_id", referencedColumnName="id")
    */
    protected $localisation;

    /**
     * @ORM\ManyToOne(targetEntity="Ipc\ProgBundle\Entity\Mode", inversedBy="infosLocalisation")
     * @ORM\JoinColumn(name="mode_id", referencedColumnName="id")
    */
    protected $mode;



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
     * Set horodatageDeb
     *
     * @param \DateTime $horodatageDeb
     * @return InfosLocalisation
     */
    public function setHorodatageDeb($horodatageDeb)
    {
        $this->horodatageDeb = $horodatageDeb;

        return $this;
    }

    /**
     * Get horodatageDeb
     *
     * @return \DateTime 
     */
    public function getHorodatageDeb()
    {
        return $this->horodatageDeb;
    }

    /**
     * Set horodatageFin
     *
     * @param \DateTime $horodatageFin
     * @return InfosLocalisation
     */
    public function setHorodatageFin($horodatageFin)
    {
        $this->horodatageFin = $horodatageFin;

        return $this;
    }

    /**
     * Get horodatageFin
     *
     * @return \DateTime 
     */
    public function getHorodatageFin()
    {
        return $this->horodatageFin;
    }

    /**
     * Set localisation
     *
     * @param \Ipc\ProgBundle\Entity\Localisation $localisation
     * @return InfosLocalisation
     */
    public function setLocalisation(\Ipc\ProgBundle\Entity\Localisation $localisation = null)
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
     * Set mode
     *
     * @param \Ipc\ProgBundle\Entity\Mode $mode
     * @return InfosLocalisation
     */
    public function setMode(\Ipc\ProgBundle\Entity\Mode $mode = null)
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * Get mode
     *
     * @return \Ipc\ProgBundle\Entity\Mode 
     */
    public function getMode()
    {
        return $this->mode;
    }

    public function sqlCheckLink($dbh,$mode_id,$localisation_id)
    {
 		$donnees = null;
        $requete = "SELECT ti.id AS id FROM t_infoslocalisation ti WHERE mode_id = '$mode_id' and localisation_id = '$localisation_id'";
        if (($reponse = $dbh->query($requete)) != false) {
            $donnees = $reponse->fetchColumn();
            $reponse->closeCursor();
        }
        return($donnees);
    }

    public function sqlGetIdModeCourant($dbh,$localisation_id)
    {
		$donnees = null;
        $requete = "SELECT mode_id FROM t_infoslocalisation ti WHERE localisation_id = '$localisation_id' AND periodeCourante = '1'"; 	
		if (($reponse = $dbh->query($requete)) != false) {
            $donnees = $reponse->fetchColumn();
            $reponse->closeCursor();
        }
        return($donnees);
    }


    /**
     * Set periodeCourante
     *
     * @param boolean $periodeCourante
     * @return InfosLocalisation
     */
    public function setPeriodeCourante($periodeCourante)
    {
        $this->periodeCourante = $periodeCourante;

        return $this;
    }

    /**
     * Get periodeCourante
     *
     * @return boolean 
     */
    public function getPeriodeCourante()
    {
        return $this->periodeCourante;
    }
}
