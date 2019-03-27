<?php
namespace Ipc\ConfigurationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
 * ConfigModbus
 *
 * @ORM\Table(name="t_configmodbus")
 * @ORM\Entity(repositoryClass="Ipc\ConfigurationBundle\Entity\ConfigModbusRepository")
 * @UniqueEntity("localisation")
 */

class ConfigModbus 
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
     * @var string
     *
     * @ORM\column(name="message", type="string", length=255, nullable=true)
     *
     */
    protected $message;

    /**
     * @var string
     *
     * @ORM\column(name="ip", type="string", length=255)
     * @Assert\Url()
     */
    protected $ip;

    /**
     * @var string
     *
     * @ORM\column(name="designation", type="string", length=255, nullable=true)
     *
     */
    protected $designation;

    /**
     * @var integer
     * @ORM\column(name="nb_variables", type="integer", nullable=true)
     */
    protected $nb_variables;



    /**
     * @ORM\OneToMany(targetEntity="Ipc\ProgBundle\Entity\DonneeLive", mappedBy="configmodbus", cascade={"remove"})
    */
    private $donneesLive;

    /**
     * @ORM\OneToOne(targetEntity="Ipc\ProgBundle\Entity\Localisation", cascade={"persist"})
     * @ORM\JoinColumn(name="localisation_id", referencedColumnName="id")
    */
    protected $localisation;



    public  $tab_variables;
    private $tab_fraction;



/*
    function __construct($id,$designation)
    {
	$this->tab_variables 	= array();
	$this->nb_variables  	= 0;
	$this->tab_fraction 	= array(pow(2,-1),pow(2,-2),pow(2,-3),pow(2,-4),pow(2,-5),pow(2,-6),pow(2,-7),pow(2,-8),pow(2,-9),pow(2,-10),pow(2,-11),pow(2,-12),pow(2,-13),pow(2,-14),pow(2,-15),pow(2,-16),pow(2,-17),pow(2,-18),pow(2,-19),pow(2,-20),pow(2,-21),pow(2,-22),pow(2,-23));
	$this->ip 		= $id;
	$this->designation 	= $designation;
	$this->message		= null;
    }
*/

    function __construct($localisation)
    {
        $this->tab_variables    = array();
        $this->setNbVariables(0);
        //$this->tab_fraction     = array(pow(2,-1),pow(2,-2),pow(2,-3),pow(2,-4),pow(2,-5),pow(2,-6),pow(2,-7),pow(2,-8),pow(2,-9),pow(2,-10),pow(2,-11),pow(2,-12),pow(2,-13),pow(2,-14),pow(2,-15),pow(2,-16),pow(2,-17),pow(2,-18),pow(2,-19),pow(2,-20),pow(2,-21),pow(2,-22),pow(2,-23));
	$this->setIp($localisation->getAdresseIp());
	$this->setLocalisation($localisation);
    }


    /**
     * Set ip
     *
     * @param integer $ip
     * @return ConfigModbus
    */
    public function setIp($ip)
    {
	$this->ip = $ip;
	return $this;
    }

    /**
     * Get ip
     *
     * @return Integer
    */
    public function getIp()
    {
	return $this->ip;
    }


    /**
     * Set designation
     *
     * @param string $designation
     * @return ConfigModbus
    */
    public function setDesignation($designation)
    {
	$this->designation = $designation;
	return $this;
    }

    /**
     * Get designation
     *
     * @return String
    */
    public function getDesignation()
    {
	return $this->designation;
    }

    /**
     * Set Message
     *
     * @param string message
     * @return ConfigModbus
    */
    public function setMessage($message)
    {
	$this->message = $message;
  	return $this;
    }

    /**
     * Get Message
     *
     * @return string
    */
    public function getMessage()
    {
	return $this->message;
    }



  /**
   * setVariable
   * Remplit le tableau des variables modbus : tab_variables et la variable indiquant le nombre de variables modbus à rechercher
   * @param String $name        : Nom de la variable
   * @param String $type        : Type de la variable : real, bool, boolX
   * @param String $numWord     : Adresse du registre à lire
   * @param String $description : Description de la variable
   * @param String $class	: Classe de la variable : liveRed, liveGreen, liveBlue, livePurple, liveOrange
    public function setVariable($name,$type,$numWord,$unit,$description,$class)
    {
	$name 		= strtolower($name);
	$type 		= strtolower($type);
	$numWord 	= strtolower($numWord);
	$nbRegistres 	= 1;
	$numBit		= null;
	if($type == 'real')
	{
	    $nbRegistres = 2;
	}
	if($type == 'bool')
	{
	    $pattern 	= '/^(\d+?)x(\d+)$/';
	    preg_match($pattern,$numWord,$tabBool);
	    $numWord	= $tabBool[1];
	    $numBit 	= $tabBool[2]; 
	}
	
	$this->tab_variables[$this->nb_variables] 		= array();
	$this->tab_variables[$this->nb_variables]['name'] 	= $name;
	$this->tab_variables[$this->nb_variables]['type'] 	= $type;
	$this->tab_variables[$this->nb_variables]['numWord'] 	= $numWord;
	$this->tab_variables[$this->nb_variables]['nbRegistres']= $nbRegistres;
	$this->tab_variables[$this->nb_variables]['numBit']	= $numBit;
	$this->tab_variables[$this->nb_variables]['description']= $description;
	$this->tab_variables[$this->nb_variables]['value']	= '-';//rand(-1000,1000);	//null;
	$this->tab_variables[$this->nb_variables]['unit']	= $unit;
	$this->tab_variables[$this->nb_variables]['class']	= $class;
	$this->nb_variables ++;
	return(0);
    }
    */

/*
    public function setRegistresAndBit()
    {
        $nbRegistres    = 1;
        $numBit         = null;
        if($type == 'real')
        {
            $nbRegistres = 2;
        }
        if($type == 'bool')
        {
            $pattern    = '/^(\d+?)x(\d+)$/';
            preg_match($pattern,$numWord,$tabBool);
            $numWord    = $tabBool[1];
            $numBit     = $tabBool[2];
        }
   	$this->setNbRegistres($nbRegistres);
	$this->setNumBit($numBit);
        return(0);
    }
*/


    /**
     * getVariable
     *
     * @param String name
     *
     * @return Array
    public function getVariable($name)
    {
	$name 			= strtolower($name);
	$tabRetour 		= array();
	$tabRetour['value'] 	= null;
	$tabRetour['unit']  	= null;
	foreach($this->tab_variables as $key=>$variable)
	{
	    if($variable['name'] == $name)
	    {
		$tabRetour['value'] 		= $variable['value'];
		$tabRetour['unit'] 		= $variable['unit'];
		$tabRetour['description']	= $variable['description'];
		break;
	    }
	}
	return($tabRetour);
    }
    */


    /**
     * getVariable
     *
     * @param String name
     *
     * @return Array
    */
    public function getVariable($name)
    {
	// Fonction utilisée dans la feuille "accueil.html.twig" du Bundle Supervision
        $name                   = strtolower($name);
        $tabRetour              = array();
        $tabRetour['value']     = null;
        $tabRetour['unit']      = null;
        foreach($this->getDonneesLive() as $entity_donneeLive)
        {
            if($entity_donneeLive->getLabel() == $name)
            {
                $tabRetour['value']             = $entity_donneeLive->getValeur();
                $tabRetour['unit']              = $entity_donneeLive->getUnite();
                $tabRetour['description']       = $entity_donneeLive->getMessage();
                break;
            }
        }
        return($tabRetour);
    }


    /**
     * getTabVariables
     *
     * @return Array
    */
    public function getTabVariables()
    {
	//print_r($this->tab_variables);
        return($this->tab_variables);
    }


    /**
     * getTabFraction
     *
     * @return Array
    */
    public function getTabFraction()
    {
	return (array(pow(2,-1),pow(2,-2),pow(2,-3),pow(2,-4),pow(2,-5),pow(2,-6),pow(2,-7),pow(2,-8),pow(2,-9),pow(2,-10),pow(2,-11),pow(2,-12),pow(2,-13),pow(2,-14),pow(2,-15),pow(2,-16),pow(2,-17),pow(2,-18),pow(2,-19),pow(2,-20),pow(2,-21),pow(2,-22),pow(2,-23)));
    }


    /*  Transforme la valeur binaire en reel selon la norme IEE...
    public function bintodecieee($nbBinaire)
    {
	//echo "Inscription $key : $nbBinaire<br />";
        $signe          = substr($nbBinaire,0,1);
        $bin_exposant   = substr($nbBinaire,1,8);
        $exposant       = bindec($bin_exposant)-127;
        $mantisse       = substr($nbBinaire,9);
	//echo "Mantisse: $mantisse<br />";
        $tabBinaire     = str_split($mantisse);
        $tabDecimal     = array_map(array($this,"multiplieTab"),$this->getTabFraction(),$tabBinaire);
        $decimal        = 1 + array_sum($tabDecimal);
	//echo "Valeur = pow(-1,$signe) * pow(2,$exposant) * $decimal<br />";
	$valeur         = pow(-1,$signe) * pow(2,$exposant) * $decimal;
	//$this->tab_variables[$key]['value'] = round($valeur,2);
	$this->setValeur(round($valeur,2));
	//echo " --> ".$this->tab_variables[$key]['value']."<br />";
        return $this;
    }
    */

    /*
    private function multiplieTab($tab1,$tab2)
    {
    	return($tab2*$tab1);
    }
    */

    /*
    public function getBit($key,$tab_data)
    {
        $donneeBinaire = $this->inverse(sprintf("%'.08d\n",decbin($tab_data[0]))).$this->inverse(sprintf("%'.08d\n",decbin($tab_data[1])));
        // Récupération de la valeur selon la valeur du bit de 0 à 8
        //	Récupération de la valeur binaire correspondant au bit définissant la valeur binaire
        //$valeurRetour = substr($donneeBinaire,$this->tab_variables[$key]['numBit'],1);
	$valeurRetour = substr($donneeBinaire,$this->getNumBit(),1);
	//$this->tab_variables[$key]['value'] = $valeurRetour;
	$this->setValeur($valeurRetour);
        return($this);
    }
    */

    /*
    private function inverse($data)
    {
        $motInverse     = '';
        $sizeMot        = strlen($data);
        for($i=0;$i<$sizeMot;$i++)
        {
            $motInverse .= substr($data,$sizeMot - 1 - $i,1);
        }
        return(trim($motInverse));
    }
    */

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
     * Set nb_variables
     *
     * @param integer $nbVariables
     * @return ConfigModbus
     */
    public function setNbVariables($nbVariables)
    {
        $this->nb_variables = $nbVariables;

        return $this;
    }

    /**
     * Get nb_variables
     *
     * @return integer 
     */
    public function getNbVariables()
    {
        return $this->nb_variables;
    }

    /**
     * Add donneesLive
     *
     * @param \Ipc\ProgBundle\Entity\DonneeLive $donneesLive
     * @return ConfigModbus
     */
    public function addDonneesLive(\Ipc\ProgBundle\Entity\DonneeLive $donneesLive)
    {
        $this->donneesLive[] = $donneesLive;

        return $this;
    }

    /**
     * Remove donneesLive
     *
     * @param \Ipc\ProgBundle\Entity\DonneeLive $donneesLive
     */
    public function removeDonneesLive(\Ipc\ProgBundle\Entity\DonneeLive $donneesLive)
    {
        $this->donneesLive->removeElement($donneesLive);
    }

    /**
     * Get donneesLive
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getDonneesLive()
    {
        return $this->donneesLive;
    }

    /**
     * Set localisation
     *
     * @param \Ipc\ProgBundle\Entity\Localisation $localisation
     * @return ConfigModbus
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
}
