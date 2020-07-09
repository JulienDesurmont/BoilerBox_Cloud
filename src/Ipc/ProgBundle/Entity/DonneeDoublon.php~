<?php

namespace Ipc\ProgBundle\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * DonneeDoublon
 *
 * @ORM\Table(name="t_donneedoublon",
 * indexes={@ORM\Index(name="K_doublon", columns={"horodatage", "cycle", "erreur"})})
 * @ORM\Entity(repositoryClass="Ipc\ProgBundle\Entity\DonneeDoublonRepository")
 */
class DonneeDoublon
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
     * @var String
     *
     * @ORM\Column(name="erreur", type="string", length=7)
    */
    protected $erreur;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="horodatage", type="datetime")
     */
    protected $horodatage;

    /**
     * @var integer
     *
     * @ORM\Column(name="cycle", type="smallint")
     */
    protected $cycle;

    /**
     * @var float
     *
     * @ORM\Column(name="valeur1", type="float")
     */
    protected $valeur1;

    /**
     * @var float
     *
     * @ORM\Column(name="valeur2", type="float")
     */
    protected $valeur2;

    /**
     * @var integer
     *
     * @ORM\Column(name="numero_genre", type="smallint")
    */
    protected $numeroGenre;


    /**
     * @var string
     *
     * @ORM\Column(name="categorie", type="string", length=2)
    */
    protected $categorie;

    /**
     * @var integer
     *
     * @ORM\Column(name="numero_module", type="smallint")
     */
    protected $numeroModule;

    /**
     * @var integer
     *
     * @ORM\Column(name="numero_message", type="smallint")
    */
    protected $numeroMessage;

    /**
     * @var string
     *
     * @ORM\Column(name="nom_fichier", type="string")
    */
    protected $nomFichier;

    /**
     * @var string
     *
     * @ORM\Column(name="affaire", type="string", length=10)
    */
    protected $affaire;

    /**
     * @var integer
     *
     * @ORM\Column(name="numero_localisation", type="integer")
    */
    protected $numero_localisation;


    /**
     * @var string
     *
     * @ORM\Column(name="programme", type="string", length=20, nullable=true)
    */
    protected $programme;


   
    public function __construct()
    {
		$this->horodatage = new \Datetime();
		$this->erreur = false;
    }

    /** Set id
     *
     * @param integer $id
     * @return Donneedoublon
     *
    */
    public function setId($id)
    {
		$this->id = $id;
		return $this;
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
     * Set horodatage
     *
     * @param \DateTime $horodatage
     * @return Donneedoublon
     */
    public function setHorodatage($horodatage)
    {
        $this->horodatage = $horodatage;
        return $this;
    }

    /**
     * Get horodatage
     *
     * @return \DateTime 
     */
    public function getHorodatage()
    {
        return $this->horodatage;
    }

    /**
     * Set cycle
     *
     * @param integer $cycle
     * @return Donneedoublon
     */
    public function setCycle($cycle)
    {
        $this->cycle = $cycle;

        return $this;
    }

    /**
     * Get cycle
     *
     * @return integer 
     */
    public function getCycle()
    {
        return $this->cycle;
    }

    /**
     * Set valeur1
     *
     * @param float $valeur1
     * @return Donneedoublon
     */
    public function setValeur1($valeur1)
    {
        $this->valeur1 = $valeur1;

        return $this;
    }

    /**
     * Get valeur1
     *
     * @return float 
     */
    public function getValeur1()
    {
        return $this->valeur1;
    }

    /**
     * Set valeur2
     *
     * @param float $valeur2
     * @return Donneedoublon
     */
    public function setValeur2($valeur2)
    {
        $this->valeur2 = $valeur2;

        return $this;
    }

    /**
     * Get valeur2
     *
     * @return float 
     */
    public function getValeur2()
    {
        return $this->valeur2;
    }

    /**
     * Set categorie
     *
     * @param string $categorie
     * @return Donneedoublon
     */
    public function setCategorie($categorie)
    {
        $this->categorie = $categorie;

        return $this;
    }

    /**
     * Get categorie
     *
     * @return string 
     */
    public function getCategorie()
    {
        return $this->categorie;
    }

    /**
     * Set numeroModule
     *
     * @param integer $numeroModule
     * @return Donneedoublon
     */
    public function setNumeroModule($numeroModule)
    {
        $this->numeroModule = $numeroModule;

        return $this;
    }

    /**
     * Get numeroModule
     *
     * @return integer 
     */
    public function getNumeroModule()
    {
        return $this->numeroModule;
    }

    /**
     * Set numeroMessage
     *
     * @param integer $numeroMessage
     * @return Donneedoublon
     */
    public function setNumeroMessage($numeroMessage)
    {
        $this->numeroMessage = $numeroMessage;

        return $this;
    }

    /**
     * Get numeroMessage
     *
     * @return integer 
     */
    public function getNumeroMessage()
    {
        return $this->numeroMessage;
    }


   /**
    * Get horodatageStr
    *
    * @return string
   */
    public function getHorodatageStr()
    {
        return $this->horodatage->format('YmdHis');
    }

    /**
     * Set erreur
     *
     * @param string $erreur
     * @return Donneedoublon
     */
    public function setErreur($erreur)
    {
        $this->erreur = $erreur;

        return $this;
    }

    /**
     * Get erreur
     *
     * @return string 
     */
    public function getErreur()
    {
        return $this->erreur;
    }

    /**
     * Set programme
     *
     * @param string $programme
     * @return Donneedoublon
     */
    public function setProgramme($programme)
    {
        $this->programme = $programme;

        return $this;
    }

    /**
     * Get programme
     *
     * @return string
     */
    public function getProgramme()
    {
        return $this->programme;
    }

    /**
     * Set numeroGenre
     *
     * @param integer $numeroGenre
     * @return Donneedoublon
     */
    public function setNumeroGenre($numeroGenre)
    {
        $this->numeroGenre = $numeroGenre;

        return $this;
    }

    /**
     * Get numeroGenre
     *
     * @return integer 
     */
    public function getNumeroGenre()
    {
        return $this->numeroGenre;
    }

    /**
     * Set nomFichier
     *
     * @param string $nomFichier
     * @return Donneedoublon
     */
    public function setNomFichier($nomFichier)
    {
        $this->nomFichier = $nomFichier;

        return $this;
    }

    /**
     * Get nomFichier
     *
     * @return string 
     */
    public function getNomFichier()
    {
        return $this->nomFichier;
    }

    /**
     * Set affaire
     *
     * @param string $affaire
     * @return Donneedoublon
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
     * Set numero_localisation
     *
     * @param integer $numeroLocalisation
     * @return Donneedoublon
     */
    public function setNumeroLocalisation($numeroLocalisation)
    {
        $this->numero_localisation = $numeroLocalisation;

        return $this;
    }

    /**
     * Get numero_localisation
     *
     * @return integer
     */
    public function getNumeroLocalisation()
    {
        return $this->numero_localisation;
    }


    //  *********************************************************************************************************************************************************************************************************************
    //                                                                  F o n c t i o n s    U t i l i s é e s    par les services        [ ServiceImportBin.php et ServiceRattrapImportBin.php ]
    //  *********************************************************************************************************************************************************************************************************************

    // Insertion d'une donnée temporaire en base de donnée
    public function myInsert($dbh, $liste_donnees) {
        $retour = null;
        $requete = "INSERT INTO t_donneedoublon ( erreur, horodatage, cycle, valeur1, valeur2, numero_genre, categorie, numero_module, numero_message, nom_fichier, affaire, programme, numero_localisation ) 
				   VALUES $liste_donnees";
        $retour = $dbh->exec($requete);
        $errorCode = $dbh->errorCode();
        if($errorCode != 0) {
            $retour = null;
        }
        return($retour);
    }

    //  *********************************************************************************************************************************************************************************************************************
    //                                                                  F o n c t i o n s    U t i l i s é e s    par le service        [ ServiceRattrapImportBin.php ]
    //  *********************************************************************************************************************************************************************************************************************
    public function myUpdateError($dbh) {
		$requete = "UPDATE t_donneedoublon SET erreur = '$this->erreur' WHERE id = '$this->id'";
		$retour = $dbh->exec($requete);
 		return($retour);
    }

    public function myDelete($dbh, $liste_donnees) {
       	$requete = "DELETE FROM t_donneedoublon WHERE id IN (".$liste_donnees.");";
		$retour = $dbh->exec($requete);
        return($retour);
    }

    public function SqlGetByError($dbh) {
		$donnees = null;
		$requete = "SELECT SQL_NO_CACHE * FROM t_donneedoublon WHERE nom_fichier = '$this->nomFichier' AND erreur = '$this->erreur'";
		if (($reponse = $dbh->query($requete)) != false) {
            $donnees = $reponse->fetchAll();
            $reponse->closeCursor();
		}
		return $donnees;
    }

    public function checkDoublon($dbh) {
        $donnees = null;
        $horodatageStr = $this->getHorodatageStr();
        $requete = "
            SELECT SQL_NO_CACHE id AS id
            FROM t_donneedoublon td
            WHERE td.horodatage         = '$horodatageStr'
            AND td.cycle                = '$this->cycle'
            AND td.valeur1              = '$this->valeur1'
            AND td.valeur2              = '$this->valeur2'
            AND td.numero_module        = '$this->numeroModule'
            AND td.numero_message       = '$this->numeroMessage'
            AND td.categorie            = '$this->categorie'
            AND td.numero_genre         = '$this->numeroGenre'
            AND td.affaire              = '$this->affaire'
            AND td.programme            = '$this->programme'
            AND td.numero_localisation  = '$this->numero_localisation'
            LIMIT 1";
        if (($reponse = $dbh->query($requete)) != false) {
            $donnees 	= $reponse->fetchColumn();
            $reponse->closeCursor();
        }
        return($donnees);
    }



    //  *********************************************************************************************************************************************************************************************************************
    //                                                                  F o n c t i o n s    U t i l i s é e s    par le module        [ C O N F I G U R A T I O N ]
    //  *********************************************************************************************************************************************************************************************************************

    public function SqlGetNb($dbh) {
        $donnees = null;
        $requete = "SELECT SQL_NO_CACHE COUNT(*) FROM t_donneedoublon";
        if (($reponse = $dbh->query($requete)) != false) {
            $donnees = $reponse->fetchColumn();
            $reponse->closeCursor();
        }
        return($donnees);
    }

    //	Utilisé par ConfigurationBundle/Controller/ConfigurationController.php pour rechercher la liste des donnéesTmp présentant des erreurs
    public function SqlGetFiles($dbh, $limit, $offset) {
		$donnees = null;
		$requete = "SELECT SQL_NO_CACHE * FROM ( SELECT nom_fichier,erreur FROM t_donneedoublon LIMIT $limit OFFSET $offset) AS Table1 GROUP BY Table1.nom_fichier, Table1.erreur";
		if (($reponse = $dbh->query($requete)) != false) {
            $donnees = $reponse->fetchAll();
            $reponse->closeCursor();
		}
		return $donnees;
    }


    //	Retourne un nombre de ligne définie de la table t_donneedoublon
    public function SqlGet($dbh, $offset, $limit) {
		$donnees = null;
		$requete = "SELECT SQL_NO_CACHE * FROM t_donneedoublon ORDER BY horodatage LIMIT $limit OFFSET $offset";
		if (($reponse = $dbh->query($requete)) != false) {
	    	$donnees = $reponse->fetchAll();
	    	$reponse->closeCursor();
		}
		return($donnees);
    }


    protected function fillNumber($num) {
        $pattern = '/^(.)$/';
        if(preg_match($pattern, $num)) {
            $num = "0".$num;
        }
        return($num);
    }

    /**
     * Get quadruplet
     *
     * @return string
    */
    public function getQuadruplet() {
        $quadruplet 	= $this->fillNumber($this->numeroGenre).$this->categorie.$this->fillNumber($this->numeroModule).$this->fillNumber($this->numeroMessage);
        return $quadruplet;
    }



}
