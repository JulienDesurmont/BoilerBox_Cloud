<?php
namespace Ipc\ProgBundle\Entity;

class EtatDate {

/**
*
* @var integer
*/
protected $id;

/**
*
* @var datetime
*/
protected $champsDateDebut;

/**
*
* @var datetime
*/
protected $champsDateFin;


public function getId(){
	return $this->id;
}

public function setId($identifiant) {
	$this->id = $identifiant;
	return $this;
}

public function setChampsDateDebut(\Datetime $nouvelleDate) {
	$this->champsDateDebut = $nouvelleDate;
	return $this;
}


public function getChampsDateDebut() {
	return $this->champsDateDebut;
}

public function getChampsDateDebutStr() {
    return $this->champsDateDebut->format('Y/m/d H:i:s');
}



public function setChampsDateFin(\Datetime $nouvelleDate) {
    $this->champsDateFin = $nouvelleDate;
    return $this;
}


public function getChampsDateFin() {
    return $this->champsDateFin;
}


public function getChampsDateFinStr() {
    return $this->champsDateFin->format('Y/m/d H:i:s');
}


}
