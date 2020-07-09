<?php

namespace Ipc\ProgBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;

//* indexes={@ORM\Index(name="MK_search", columns={'localisation_id"})})

// Un fichier peut pointer sur            	plusieurs donnees
// Plusieurs fichiers peuvent pointer sur  	une localisation

/**
 * Fichier
 *
 * @ORM\Table(name="t_fichier", indexes={
 *  @ORM\Index(name="MK_search", columns={"localisation_id"})
 * })
 * @ORM\Entity(repositoryClass="Ipc\ProgBundle\Entity\FichierRepository")
 * @ORM\HasLifecycleCallbacks()
*/
class Fichier
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
     * @ORM\Column(name="nom", type="string", length=255, unique=true)
     */
    protected $nom;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_traitement", type="datetime")
     */
    protected $dateTraitement;

    /**
     * @var integer
     *
     * @ORM\Column(name="nombre_messages", type="smallint")
     */
    protected $nombreMessages;

    /**
     * @var integer
     *
     * @ORM\Column(name="nombre_vides", type="smallint")
     */
    protected $nombreVides;

    /**
     * @var integer
     *
     * @ORM\Column(name="nombre_sans_designation", type="smallint")
     */
    protected $nombreSansDesignation;

    /**
     * @ORM\ManyToOne(targetEntity="Ipc\ProgBundle\Entity\Localisation", inversedBy="fichiers")
     * @ORM\JoinColumn(name="localisation_id", referencedColumnName="id", nullable=false)
    */
    protected $localisation;

    public function __construct()
    {
        //$this->dateTraitement = new \Datetime();
        $this->nombreMessages = 0;
        $this->nombreVides = 0;
        $this->nombreSansDesignation = 0;
    }

    /*Déplacement du fichier binaire vers les repertoires des fichiers binaires temporaire (données en base mais non checkées)
    public function moveBinToTempo($ficlog)
    {
	$oldfic=$this->getBinaryDir().$this->getNom();
	$newfic=$this->getBinaryTmpDir().$this->getNom();
	//$this->logg("Déplacement de ".$oldfic,$ficlog);
        //$this->logg("Vers ".$newfic,$ficlog);
	rename($oldfic, $newfic);
    }
    */


    // Supprime le fichier binaire du répertoire /fichiers_binaires
    public function deleteBinary()
    {
        $fichierBinaire = $this->getBinaryDir().$this->getNom();
        if (file_exists($fichierBinaire)) {
                unlink($fichierBinaire);
        }
    }

    // Déplace le fichier d'origine du répertoire /fichiers_tmpencours vers le répertoire des fichiers traités /fichiers_traites
    public function moveToProcessed()
    {
        $pattern            = '/^(.+?)\.bin$/';                     // Pattern du nom du fichier d'origine
        $replacement        = '$1';
        $nomFichierOrigine  = preg_replace($pattern,$replacement,$this->getNom());
        $fichierOrigine     = $this->getOrigineDir().$nomFichierOrigine;
        $fichierTraite      = $this->getProcessedDir().$nomFichierOrigine;
        if(is_file($fichierOrigine))
        {
            rename($fichierOrigine, $fichierTraite);
        }
    }

    // Déplace le fichier d'origine du répertoire /fichiers_tmpencours vers le répertoire des fichiers en erreur /fichiers_errors
    public function moveToError()
    {
        // Le nom du fichier d'origine est le nom du fichier binaire sans l'extension .bin
        $pattern                = '/^(.+?)\.bin$/';                                             // Pattern du nom du fichier d'origine
        $replacement            = '$1';
        $nomFichierOrigine      = preg_replace($pattern,$replacement,$this->getNom());          // Nom du fichier d'origine
        $fichierOrigine         = $this->getOrigineDir().$nomFichierOrigine;                    // Chemin vers le fichier d'origine
        $fichierErreur          = $this->getErrorDir().$nomFichierOrigine.'.error';             // Chemin vers le fichier en erreur
        if (file_exists($fichierOrigine)) {
                rename($fichierOrigine, $fichierErreur);                                        // Déplacement du fichier du dossier tmp vers le dossier erreur si le fichier existe
        } else {
                $fichierBinaire = $this->getBinaryDir().$this->getNom();                        // Déplacement du fichier du dossier binaire vers le dossier erreur si le fichier n'est pas trouvé
                $fichierErreur  = $this->getErrorDir().$this->getNom().'.error';
                rename($fichierBinaire, $fichierErreur);
        }
    }



    //Récupération des données du fichier binaire: Retour des valeurs dans un tableau associatif
    public function getContenu()
    {
	$tabFraction = array(pow(2,-1),pow(2,-2),pow(2,-3),pow(2,-4),pow(2,-5),pow(2,-6),pow(2,-7),pow(2,-8),pow(2,-9),pow(2,-10),pow(2,-11),pow(2,-12),pow(2,-13),pow(2,-14),pow(2,-15),pow(2,-16),pow(2,-17),pow(2,-18),pow(2,-19),pow(2,-20),pow(2,-21),pow(2,-22),pow(2,-23),pow(2,-24));

	$nu_donnee 		= 0;
	$nombreLignesVides 	= 0;
	$nombreLignesNonVides 	= 0;
	$contenus 		= array();
	$fp 			= fopen($this->getBinaryDir().$this->getNom(),"r");
	while($ligne = fgets($fp))
	{
	    //		Les fichiers binaires sont formatés pour que chaque ligne fasse 24 octets
            $champs         = explode(" ",$ligne);
	    //		On ne traite la donnée que si les champs de la catégorie sont différents de 0
	    if( ($champs[9] != 0) && ($champs[10]!= 0) )
	    {
        	$annee 		= $this->completeNum(bindec($champs[1]));
	       	$mois 		= $this->completeNum(bindec($champs[2]));
             	$jour		= $this->completeNum(bindec($champs[3]));
            	$heure 		= $this->completeNum(bindec($champs[4]));
             	$minute 	= $this->completeNum(bindec($champs[5]));
            	$seconde	= $this->completeNum(bindec($champs[6]));
		//	Si la date de la donnée n'est pas correcte, on considère la ligne comme étant vide
		$testDate	= checkdate($mois,$jour,'20'.$annee);
		if($testDate == false)
		{
		    $nombreLignesVides ++;
		}else{
		    $contenus[$nu_donnee]["datedonnee"]     	= "$annee$mois$jour$heure$minute$seconde";
		    $contenus[$nu_donnee]["nu_cycle"]       	= $this->completeNum($this->toint(bindec($champs[8].$champs[7])));
          	    $contenus[$nu_donnee]["ty_categorie"] 	= chr(bindec($champs[9])).chr(bindec($champs[10]));
            	    $contenus[$nu_donnee]["nu_localisation"]	= $this->completeNum($this->toint(bindec($champs[11])));
           	    $contenus[$nu_donnee]["nu_module"]      	= $this->completeNum($this->toint(bindec($champs[12])));
             	    $contenus[$nu_donnee]["nu_message"]     	= $this->completeNum($this->toint(bindec($champs[13])));
            	    $contenus[$nu_donnee]["nu_genre"]   	= $this->completeNum($this->toint(bindec($champs[14])));
		    //$contenus[$nu_donnee]["reserve"] 	= bindec($champs[15]).bindec($champs[16]);
		    $valeur1 					= $champs[20].$champs[19].$champs[18].$champs[17];
            	    $contenus[$nu_donnee]["valeur1"]        	= $this->bintodecieee($tabFraction,$valeur1);	/*round($this->bintodecieee($tabFraction,$valeur1), 3);*/
             	    $valeur2 					= $champs[24].$champs[23].$champs[22].$champs[21];
            	    $contenus[$nu_donnee]["valeur2"]        	= $this->bintodecieee($tabFraction,$valeur2); /*round($this->bintodecieee($tabFraction,$valeur2), 3);*/
		    $nu_donnee++;
		    $nombreLignesNonVides ++;
		}
    	    }else{
			$nombreLignesVides ++;
	    }
	}
	fclose($fp);
	$this->nombreVides 	= $nombreLignesVides;
	$this->nombreMessages 	= $nombreLignesNonVides;
	return $contenus;
    }

   public function toint($valeur)
   {
        return($nouvelle_valeur=preg_replace('/[^\-\d]*(\-?\d*).*/','$1',$valeur))?$nouvelle_valeur:'0';
   }


    //Fonction qui retourne une valeur sur deux caractéres si la valeur passée n'en fait qu'un (exemple entrée 1 -> sortie 01 )
    public function completeNum($valeur)
    {
	$pattern = '/^.$/';
	if(preg_match($pattern,$valeur))
	{
		$valeur = "0".$valeur;
	}
	return $valeur;
    }

	/*
    public function bintodecieee($nbinaire)
    {
       	$signe 		= substr($nbinaire,0,1);
       	$bin_exposant 	= substr($nbinaire,1,8);
       	$exposant 	= bindec($bin_exposant)-127;
       	$mantisse 	= substr($nbinaire,9);
       	$fraction	= 0;
       	for($i=0;$i<23;$i++)
       	{
               	$puissance 	= $i+1;
               	$nb 		= substr($mantisse,$i,1).' - ';
               	$fraction 	+= $nb*pow(2,-$puissance);
       	}
       	$valeur = (1+$fraction)*pow(2,$exposant);
       	if($signe == 1)
       	{
               	$valeur = -$valeur;
       	}
       	return $valeur;
    }
	*/

private function multiplieTab($tab1,$tab2)
{
    return($tab2*$tab1);
}


    //	Transforme la valeur binaire en reel selon la norme IEE...
    public function bintodecieee($tabFraction,$nbBinaire)
    {
        $signe          = substr($nbBinaire,0,1);
        $bin_exposant   = substr($nbBinaire,1,8);
        $exposant       = bindec($bin_exposant)-127;
        $mantisse       = substr($nbBinaire,9);
        $tabBinaire     = str_split($mantisse);
        $tabDecimal     = array_map(array($this,"multiplieTab"),$tabFraction,$tabBinaire);
        $decimal        = 1 + array_sum($tabDecimal);
        $valeur         = $decimal*pow(2,$exposant);
        if($signe == 1)
        {
            $valeur = -$valeur;
        }
        return $valeur;
    }


	/*
    public function bintodecieeeV2($nbinaire)
    {
        $signe          = substr($nbinaire,0,1);
        $bin_exposant   = substr($nbinaire,1,8);
        $exposant       = bindec($bin_exposant)-127;
        $mantisse       = substr($nbinaire,9);
        $pattern 	= '/^(.+?)0*$/';
        $replacement 	= '$1';
        $mantisse       = preg_replace($pattern,$replacement,$mantisse);
        $fraction       = 0;
        for($i=0;$i<strlen($mantisse);$i++)
        {
            $puissance      = $i+1;
            $nb             = substr($mantisse,$i,1).' - ';
            $fraction       += $nb*pow(2,-$puissance);
        }
        $valeur = (1+$fraction)*pow(2,$exposant);
        if($signe == 1)
        {
            $valeur = -$valeur;
        }
        return $valeur;
    }
	*/



    // Retourne le répertoire des fichiers téléchargés
    public function getUploadDir()
    {
	return __DIR__.'/../../../../web/uploads/';
    }

    public function getLogsDir()
    {
        return __DIR__.'/../../../../web/logs/';
    }


    // Retourne le répertoire des fichiers d'origines convertis en binaire 
    public function getOrigineDir()
    {
	return $this->getUploadDir().'fichiers_tmpencours/';
    }

   // Retourne le répertoire des fichiers d'origines traités, cad dont les données sont insérées en base de donnée
    public function getProcessedDir()
    {
	return $this->getUploadDir().'fichiers_traites/';	
    }

    // Retourne le répertoire des fichiers binaires convertis 
    public function getBinaryDir()
    {
	return $this->getUploadDir().'fichiers_binaires/';
    }
   
    // Retourne le répertoire des fichiers d'etats
    public function getEtatsDir()
    {
        return __DIR__.'/../../../../web/etats/';
    }


    // Retourne le répertoire des fichiers ipc
    public function getTableIpcDir()
    {
        return __DIR__.'/../../../../web/uploads/csv/bddipc/';
    }




    // Retourne le répertoire des fichiers d'origine en erreur
    public function getErrorDir()
    {
		return $this->getUploadDir().'fichiers_errors/';
    }


   //Déplacement du fichier dans le repertoire des fichiers téléchargés
   public function deplacement($type)
   {
		//Si le fichier n'a pas d'url (cad si il n'est pas encore déplacé)
		if($this->nom != null)
		{
	    	if($type == 'bddipc')
            {
				$this->nom = 'Nouveau'.\Datetime().'.csv';
				return $this->move(
		    		$this->getUdatedbDir(),
		    		$this->nom
				);
        	}
		}else{
			return $this;
		}
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
    * Set id
    *
    * @param integer $id
    * @return Fichier
   */
    public function setId($id)
    {
		$this->id = $id;
		return $this;
    }

    /**
     * Set nom
     *
     * @param string $nom
     * @return Fichier
     */
    public function setNom($nom)
    {
        $this->nom = $nom;
        return $this;
    }

    /**
     * Get nom
     *
     * @return string 
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * Set dateTraitement
     *
     * @param \DateTime $dateTraitement
     * @return Fichier
     */
    public function setDateTraitement($dateTraitement)
    {
        $this->dateTraitement = $dateTraitement;

        return $this;
    }

    /**
     * Get dateTraitement
     *
     * @return \DateTime 
     */
    public function getDateTraitement()
    {
        return $this->dateTraitement;
    }

   /**
    * Get dateTraitementStr
    *
    * @return string
   */
    public function getDateTraitementStr()
    {
        $datestr = $this->getDateTraitement()->format('YmdHis');
        return $datestr;
    }

    /**
     * Set affaire
     *
     * @param string $affaire
     * @return Fichier
     */
    public function setAffaire($affaire)
    {
        $this->affaire = $affaire;

        return $this;
    }

    /**
     * Get affaire
     *
     * @return string 
     */
    public function getAffaire()
    {
        return $this->affaire;
    }

    /**
     * Set localisation
     *
     * @param integer $localisation
     * @return Fichier
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
     * Set nombreMessages
     *
     * @param integer $nombreMessages
     * @return Fichier
     */
    public function setNombreMessages($nombreMessages)
    {
        $this->nombreMessages = $nombreMessages;

        return $this;
    }

    /**
     * Get nombreMessages
     *
     * @return integer 
     */
    public function getNombreMessages()
    {
        return $this->nombreMessages;
    }

    /**
     * Set nombreVides
     *
     * @param integer $nombreVides
     * @return Fichier
     */
    public function setNombreVides($nombreVides)
    {
        $this->nombreVides = $nombreVides;

        return $this;
    }

    /**
     * Get nombreVides
     *
     * @return integer 
     */
    public function getNombreVides()
    {
        return $this->nombreVides;
    }

    /**
     * Set nombreSansDesignation
     *
     * @param integer $nombreSansDesignation
     * @return Fichier
     */
    public function setNombreSansDesignation($nombreSansDesignation)
    {
        $this->nombreSansDesignation = $nombreSansDesignation;

        return $this;
    }

    /**
     * Get nombreSansDesignation
     *
     * @return integer 
     */
    public function getNombreSansDesignation()
    {
        return $this->nombreSansDesignation;
    }

    /**
     * Set fichier
     *
     * @param \Ipc\ProgBundle\Entity\Localisation $fichier
     * @return Fichier
     */
    public function setFichier(\Ipc\ProgBundle\Entity\Localisation $fichier)
    {
        $this->fichier = $fichier;

        return $this;
    }

    /**
     * Get fichier
     *
     * @return \Ipc\ProgBundle\Entity\Localisation 
     */
    public function getFichier()
    {
        return $this->fichier;
    }

    //	Fonction qui gère l'insertion des données des fichiers : En cas de doublon, l'insertion des données est ignorée
    public function myInsert($dbh, $localisation_id) {
       	$dateTraitement = $this->getDateTraitementStr();
       	$requete = "INSERT IGNORE INTO t_fichier ( localisation_id, nom, date_traitement, nombre_messages, nombre_vides, nombre_sans_designation ) VALUES ('$localisation_id','$this->nom','$dateTraitement','$this->nombreMessages','$this->nombreVides','$this->nombreSansDesignation')";
		$retour = $dbh->exec($requete);
		return($retour);
    }

	// Fonction qui supprime les informations du fichier en base de données
	// Cette fonction est appelée en cas d'erreur CRITIQUE non prévue
	public function myDelete($dbh) {
		$requete = "DELETE FROM t_fichier WHERE nom = '$this->nom'";
		$retour = $dbh->exec($requete);
		return($retour);
	}

    public function SqlGetNom($dbh, $limit, $id) {
		$donnees = null;
		$requete  = "SELECT tf.nom AS nom FROM t_fichier tf WHERE tf.id = '$id' LIMIT $limit";
		if (($reponse = $dbh->query($requete)) != false) {
			$donnees = $reponse->fetchColumn();
			$reponse->closeCursor();
		}
		return($donnees);
	}


	public function SqlGetInfosBase($dbh, $limit, $nom) {
		$donnees = null;
		$requete  = "SELECT tf.id AS id, tf.date_traitement AS date_traitement FROM t_fichier tf WHERE tf.nom = '$nom' LIMIT $limit";
		if (($reponse = $dbh->query($requete)) != false) {
	    	$donnees = $reponse->fetch();
	    	$reponse->closeCursor();
		}
		return($donnees);
    }

    public function SqlGetId($dbh) {
		$donnees = null;
		$requete = "SELECT tf.id AS id FROM t_fichier tf WHERE tf.nom = '$this->nom' LIMIT 1";
		if (($reponse = $dbh->query($requete)) != false) {
			$donnees = $reponse->fetchColumn();
			$reponse->closeCursor();
		}
		return($donnees);
   	}

    public function SqlGetNextId($dbh) {
		$donnees = null;
		$requete = "SELECT MAX(id) FROM t_fichier";
		if (($reponse = $dbh->query($requete)) != false) {
            $donnees = $reponse->fetchColumn();
            $reponse->closeCursor();
        }
        return($donnees+1);
    }


	public function servDelete($url) {
		$commande = "rm $url";
		exec($commande);
		return(0);
	}


    //  Récupération du dernier message de la base de donnée sur une période définie
    public function sqlGetLast($dbh) {
        $donnee = null;
        $liaison = null;
        $requete = "SELECT SQL_NO_CACHE f.nom, f.date_traitement FROM t_fichier f ORDER BY f.nom DESC LIMIT 1";
        if (($reponse = $dbh->query($requete)) != false) {
            $donnee = $reponse->fetchAll();
            $reponse->closeCursor();
        }
        return($donnee);
    }

}
