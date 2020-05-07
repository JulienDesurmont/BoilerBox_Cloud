<?php
namespace Ipc\ProgBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
 * DonneeLive
 *
 * @ORM\Table(name="t_donneelive",
 * uniqueConstraints={@ORM\UniqueConstraint(name="UK_search", columns={"localisation_id", "adresse", "valeur_entree_vrai"})},
 * indexes={
 * @ORM\Index(name="MK_search", columns={"localisation_id", "adresse"})})
 * @ORM\Entity(repositoryClass="Ipc\ProgBundle\Entity\DonneeLiveRepository")
 */
class DonneeLive
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
     * @ORM\column(name="label", type="string", length=255)
     *
     * @Assert\Length(
     * min = "1",
     * max = "255",
     * minMessage = "Nombre minimum de caractères : 1",
     * maxMessage = "Nombre maximum de caractères autorisé : 255"
     * )
     */
    protected $label;

    /**
     * @var string
     *
     * @ORM\column(name="adresse", type="string", length=50)
     */
    protected $adresse;

    /**
     * @var string
     * @ORM\column(name="type", type="string", length=255)
	 * @Assert\Choice(choices = {"BOOL", "INT", "REAL"}, message = "Choix non valide")
     */
    protected $type;

    /**
     * @var string
     * @ORM\column(name="unite", type="string", length=20, nullable=true)
     * @Assert\Length(
     * min = 1,
     * max = 20,
     * minMessage = "Nombre minimum de caractères : 1",
     * maxMessage = "Nombre maximum de caractères autorisé : 20"
     * )
     */
    protected $unite;

    /**
     * @var string
     * @ORM\column(name="famille", type="string", length=50)
     * @Assert\Length(
     * min = 1,
     * max = 50,
     * minMessage = "Nombre minimum de caractères : 1",
     * maxMessage = "Nombre maximum de caractères autorisé : 50"
     * )
     */
    protected $famille;

    /**
     * @var string
     * @ORM\column(name="placement", type="string", length=10, nullable=true)
     * @Assert\Length(
     * min = 1,
     * max = 10,
     * minMessage = "Nombre minimum de caractères : 1",
     * maxMessage = "Nombre maximum de caractères autorisé : 10"
     * )
     */
    protected $placement;

    /**
     * @var string
     * @ORM\column(name="couleur", type="string", length=20, nullable=true)
     * @Assert\Length(
     * min = 1,
     * max = 20,
     * minMessage = "Nombre minimum de caractères : 1",
     * maxMessage = "Nombre maximum de caractères autorisé : 20"
     * )
     */
    protected $couleur;

    /**
     * @var integer
     * @ORM\column(name="valeur_entree_vrai", type="integer", nullable=true)
     */
    protected $valeurEntreeVrai;

    /**
     * @var string
     * @ORM\column(name="valeur_sortie_vrai", type="string", length=255, nullable=true)
     * @Assert\Length(
     * min = 1,
     * max = 50,
     * minMessage = "Nombre minimum de caractères : 1",
     * maxMessage = "Nombre maximum de caractères autorisé : 50"
     * )
     */
    protected $valeurSortieVrai;

    /**
     * @var string
     * @ORM\column(name="valeur_sortie_faux", type="string", length=255, nullable=true)
     * @Assert\Length(
     * min = 1,
     * max = 50,
     * minMessage = "Nombre minimum de caractères : 1",
     * maxMessage = "Nombre maximum de caractères autorisé : 50"
     * )
     */
    protected $valeurSortieFaux;

    /**
     * @var integer
     * @ORM\column(name="nb_registres", type="integer", nullable=true)
     */
    protected $nb_registres;

    /**
     * @var integer
     * @ORM\column(name="numBit", type="integer", nullable=true)
     */
    protected $numBit;

    /**
     * @var string
     * @ORM\column(name="valeur", type="string", length=255, nullable=true)
     */
    protected $valeur;

 
    /**
     * @ORM\ManyToOne(targetEntity="Ipc\ProgBundle\Entity\Localisation", inversedBy="donneesLive", cascade={"persist"})
     * @ORM\JoinColumn(name="localisation_id", referencedColumnName="id")
     */
    protected $localisation;

    /**
     * @ORM\ManyToOne(targetEntity="Ipc\ConfigurationBundle\Entity\ConfigModbus", inversedBy="donneesLive", cascade={"persist"})
     * @ORM\JoinColumn(name="configmodbus_id", referencedColumnName="id", nullable=false)
    */
    protected $configmodbus;

    /**
     * @ORM\ManyToOne(targetEntity="Ipc\ProgBundle\Entity\CategorieFamilleLive", inversedBy="donneesLive", cascade={"persist"})
     * @ORM\JoinColumn(name="categorieFamilleLive_id", referencedColumnName="id", nullable=true)
     * @ORM\OrderBy({"designation" = "ASC"})
    */
    protected $categorie;

    /**
     * @ORM\ManyToOne(targetEntity="Ipc\ProgBundle\Entity\TypeFamilleLive", inversedBy="donneesLive", cascade={"persist"})
     * @ORM\JoinColumn(name="typeFamilleLive_id", referencedColumnName="id", nullable=true)
    */
    protected $typeFamille;

    /**
     * @ORM\ManyToOne(targetEntity="Ipc\ProgBundle\Entity\Icone", inversedBy="donneesLive", cascade={"persist"})
     * @ORM\JoinColumn(name="icone_id", referencedColumnName="id", nullable=true)
     * @ORM\OrderBy({"designation" = "ASC"})
    */
    protected $icone;


     
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
     * 
     * Set id
    */
    public function setId($id)
    {
	$this->id = $id;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return DonneeLive
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
     * Set adresse
     *
     * @param string $adresse
     * @return DonneeLive
     */
    public function setAdresse($adresse)
    {
        $this->adresse = $adresse;

        return $this;
    }

    /**
     * Get adresse
     *
     * @return string 
     */
    public function getAdresse()
    {
        return $this->adresse;
    }

    /**
     * Set unite
     *
     * @param string $unite
     * @return DonneeLive
     */
    public function setUnite($unite)
    {
        $this->unite = $unite;

        return $this;
    }

    /**
     * Get unite
     *
     * @return string 
     */
    public function getUnite()
    {
        return $this->unite;
    }

    /**
     * Set famille
     *
     * @param string $famille
     * @return DonneeLive
     */
    public function setFamille($famille)
    {
        $this->famille = $famille;

        return $this;
    }

    /**
     * Get famille
     *
     * @return string 
     */
    public function getFamille()
    {
        return $this->famille;
    }

    /**
     * Set couleur
     *
     * @param string $couleur
     * @return DonneeLive
     */
    public function setCouleur($couleur)
    {
        $this->couleur = $couleur;

        return $this;
    }

    /**
     * Get couleur
     *
     * @return string 
     */
    public function getCouleur()
    {
        return $this->couleur;
    }

    /**
     * Set localisation
     *
     * @param \Ipc\ProgBundle\Entity\Localisation $localisation
     * @return DonneeLive
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
     * Set configmodbus
     *
     * @param \Ipc\ConfigurationBundle\Entity\ConfigModbus $configmodbus
     * @return DonneeLive
     */
    public function setConfigmodbus(\Ipc\ConfigurationBundle\Entity\ConfigModbus $configmodbus)
    {
        $this->configmodbus = $configmodbus;

        return $this;
    }

    /**
     * Get configmodbus
     *
     * @return \Ipc\ConfigurationBundle\Entity\ConfigModbus 
     */
    public function getConfigmodbus()
    {
        return $this->configmodbus;
    }

    //  Transforme la valeur binaire en reel selon la norme IEE...
    public function binToReel($tabData)
    {
        $nbBinaire  	= sprintf("%'.08d", decbin($tabData[1])).sprintf("%'.08d", decbin($tabData[0])).sprintf("%'.08d", decbin($tabData[3])).sprintf("%'.08d", decbin($tabData[2]));
        $signe          = substr($nbBinaire, 0, 1);
        $bin_exposant   = substr($nbBinaire, 1, 8);
        $exposant       = bindec($bin_exposant)-127;
        $mantisse       = substr($nbBinaire, 9);
        $tabBinaire     = str_split($mantisse);
        $tabDecimal     = array_map(array($this, "multiplieTab"), $this->getConfigmodbus()->getTabFraction(), $tabBinaire);
        $decimal        = 1 + array_sum($tabDecimal);
        $valeur         = pow(-1, $signe) * pow(2, $exposant) * $decimal;
		if ($this->getValeur() === null) {
            $this->setValeur($this->formatValeur(round($valeur, 2)));
		} else {
	    	$this->setValeur($this->formatValeur($this->getValeur().';'.round($valeur, 2)));
		}
        return($this);
    }

    public function binToBool($tabData)
    {
        $donneeBinaire = $this->inverse(sprintf("%'.08d", decbin($tabData[0]))).$this->inverse(sprintf("%'.08d", decbin($tabData[1])));
        // Récupération de la valeur selon la valeur du bit de 0 à 8
        // Récupération de la valeur binaire correspondant au bit définissant la valeur binaire
        $valeurRetour = substr($donneeBinaire, $this->getNumBit(), 1);
        if ($this->getValeur() === null) {
            $this->setValeur($valeurRetour);
        } else {
            $this->setValeur($this->getValeur().';'.$valeurRetour);
        }
        return($this);
    }

    public function binToInt($tabData)
    {
        $nbBinaire = sprintf("%'.08d", decbin($tabData[1])).sprintf("%'.08d", decbin($tabData[0]));
		$signe = substr($nbBinaire,0,1);
		// Si le signe est négatif traitement : Recherche de la valeur 
		if ($signe == 1) {
	    	// 1 Inversion des 0 et des 1
	    	$valeur = $this->getMirror($nbBinaire);
	    	// 2 ajout de 1 à la valeur 
	    	$valeur = bindec($valeur) + 1;
	    	// 3 ajout du signe
	    	$valeur = '-'.$valeur;
		} else {
	   	$valeur = bindec($nbBinaire); 
		}
		$valeurRetour = $valeur;
        if ($this->getValeur() === null) {
            $this->setValeur($valeurRetour);
        } else {
            $this->setValeur($this->getValeur().';'.$valeurRetour);
        }
        return($this);
    }


    private function multiplieTab($tab1,$tab2)
    {
        return($tab2*$tab1);
    }


    private function inverse($data)
    {
        $motInverse = '';
        $sizeMot = strlen($data);
        for ($i = 0; $i < $sizeMot; $i++) {
            $motInverse .= substr($data, $sizeMot - 1 - $i, 1);
        }
        return(trim($motInverse));
    }

    // Remplace tous les 0 par des 1 et inversement : Utilisé pour la recherche de nombre négatifs
    private function getMirror($data)
    {
		$sizeMot = strlen($data);
		$motMiroire = '';
        for ($i = 0; $i < $sizeMot; $i++) {
	    	if (substr($data, $i, 1) == '1') {
				$motMiroire .= '0';
	    	} elseif (substr($data, $i, 1) == '0') {
				$motMiroire .= '1';
	    	} 
        }
        return($motMiroire);
    }


    public function setRegistresAndBit()
    {
        $nbRegistres = 1;
        $numBit = null;
        if (strtoupper($this->getType()) == 'REAL') {
            $nbRegistres = 2;
        }
        if (strtoupper($this->getType()) == 'BOOL') {
            $pattern = '/^(\d+?)[Xx](\d+)$/';
            preg_match($pattern, $this->getAdresse(), $tabBool);
            $numWord = $tabBool[1];
            $numBit  = $tabBool[2];
        }
        $this->setNbRegistres($nbRegistres);
        $this->setNumBit($numBit);
        return(0);
    }




    //  Fonction qui formate une valeur pour ajouter des 0 lorsqu'il n'y a qu'un seul chiffre après la virgule
    private function formatValeur($valeur)
    {
        $valeurRetour = $valeur;
        $pattern = '/\.\d$/';
        if (preg_match($pattern, $valeur)) {
            $valeurRetour = $valeur.'0';
        }
        return $valeurRetour;
    }


    /**
     * Set nb_registres
     *
     * @param integer $nbRegistres
     * @return DonneeLive
     */
    public function setNbRegistres($nbRegistres)
    {
        $this->nb_registres = $nbRegistres;

        return $this;
    }

    /**
     * Get nb_registres
     *
     * @return integer 
     */
    public function getNbRegistres()
    {
        return $this->nb_registres;
    }

    /**
     * Set numBit
     *
     * @param integer $numBit
     * @return DonneeLive
     */
    public function setNumBit($numBit)
    {
        $this->numBit = $numBit;

        return $this;
    }

    /**
     * Get numBit
     *
     * @return integer 
     */
    public function getNumBit()
    {
        return $this->numBit;
    }

    /**
     * Set valeur
     *
     * @param integer $valeur
     * @return DonneeLive
     */
    public function setValeur($valeur)
    {
        $this->valeur = $valeur;

        return $this;
    }

    /**
     * Get valeur
     *
     * @return integer 
     */
    public function getValeur()
    {
        return $this->valeur;
    }


    /**
     * Set label
     *
     * @param string $label
     * @return DonneeLive
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

    /**
     * Set valeurEntreeVrai
     *
     * @param integer $valeurEntreeVrai
     * @return DonneeLive
     */
    public function setValeurEntreeVrai($valeurEntreeVrai)
    {
        $this->valeurEntreeVrai = $valeurEntreeVrai;

        return $this;
    }

    /**
     * Get valeurEntreeVrai
     *
     * @return integer 
     */
    public function getValeurEntreeVrai()
    {
        return $this->valeurEntreeVrai;
    }

    /**
     * Set valeurSortieVrai
     *
     * @param string $valeurSortieVrai
     * @return DonneeLive
     */
    public function setValeurSortieVrai($valeurSortieVrai)
    {
        $this->valeurSortieVrai = $valeurSortieVrai;

        return $this;
    }

    /**
     * Get valeurSortieVrai
     *
     * @return string 
     */
    public function getValeurSortieVrai()
    {
        return $this->valeurSortieVrai;
    }

    /**
     * Set valeurSortieFaux
     *
     * @param string $valeurSortieFaux
     * @return DonneeLive
     */
    public function setValeurSortieFaux($valeurSortieFaux)
    {
        $this->valeurSortieFaux = $valeurSortieFaux;

        return $this;
    }

    /**
     * Get valeurSortieFaux
     *
     * @return string 
     */
    public function getValeurSortieFaux()
    {
        return $this->valeurSortieFaux;
    }

    /**
     * Set categorie
     *
     * @param \Ipc\ProgBundle\Entity\CategorieFamilleLive $categorie
     * @return DonneeLive
     */
    public function setCategorie(\Ipc\ProgBundle\Entity\CategorieFamilleLive $categorie)
    {
        $this->categorie = $categorie;

        return $this;
    }

    /**
     * Get categorie
     *
     * @return \Ipc\ProgBundle\Entity\CategorieFamilleLive 
     */
    public function getCategorie()
    {
        return $this->categorie;
    }

    /**
     * Set typeFamille
     *
     * @param \Ipc\ProgBundle\Entity\TypeFamilleLive $typeFamille
     * @return DonneeLive
     */
    public function setTypeFamille(\Ipc\ProgBundle\Entity\TypeFamilleLive $typeFamille)
    {
        $this->typeFamille = $typeFamille;

        return $this;
    }

    /**
     * Get typeFamille
     *
     * @return \Ipc\ProgBundle\Entity\TypeFamilleLive 
     */
    public function getTypeFamille()
    {
        return $this->typeFamille;
    }

    /**
     * Set placement
     *
     * @param string $placement
     * @return DonneeLive
     */
    public function setPlacement($placement)
    {
        $this->placement = $placement;

        return $this;
    }

    /**
     * Get placement
     *
     * @return string 
     */
    public function getPlacement()
    {
        return $this->placement;
    }

    /**
     * Set icone
     *
     * @param \Ipc\ProgBundle\Entity\icone $icone
     * @return DonneeLive
     */
    public function setIcone(\Ipc\ProgBundle\Entity\icone $icone)
    {
        $this->icone = $icone;

        return $this;
    }

    /**
     * Get icone
     *
     * @return \Ipc\ProgBundle\Entity\icone 
     */
    public function getIcone()
    {
        return $this->icone;
    }

}
