<?php 
//src/Ipc/ProgBundle/Services/Session/ServiceSession.php
//Service permettant la gestion de sessions différentes en fonction de l'affaire du site analysé
namespace Ipc\ProgBundle\Services\Session;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;


class ServiceSession {
protected $session;
protected $nom_de_session;
protected $tab_sessions;
protected $securityContext;

public function __construct($securityContext, Session $session) {
	$this->session = $session;
	$this->securityContext = $securityContext;

	// Création du bag de session courante si il n'existe pas déjà
    $this->nom_de_session = $this->session->get('nom_session_courante', array());
	if ($this->nom_de_session == null) {
		$this->nom_de_session = $this->nouvelleSession();
	}
    if (! $this->session->has($this->nom_de_session)) {
        $bag_session_courante = new AttributeBag();
        $bag_session_courante->setName($this->nom_de_session);
		$this->saveSession($bag_session_courante);
    }
}

public function getSessionName(){
	return $this->session->get('nom_session_courante', array());
}

// Récupération du bag de la session courante
public function getSessionCourante(){
	$bag_session_courante = $this->session->get($this->nom_de_session);
	return $bag_session_courante;
}

// Enregistrement du bag de la session courante
public function saveSession($bagSession){
	$this->session->set($this->nom_de_session, $bagSession);	
}

// Définition d'une variable dans le bag de la session courante
public function __set($variable, $valeur_variable) {
	$bag_session_courante = $this->getSessionCourante();
	$bag_session_courante->set($variable, $valeur_variable);
	$this->saveSession($bag_session_courante);
}

// Récupération d'une variable depuis le bag de la session courante
public function __get($variable){
    $bag_session_courante = $this->getSessionCourante();
    if ($bag_session_courante->get($variable) == null) {
        return array();
    } else {
        return $bag_session_courante->get($variable);
    }
}

// Récupération d'une variable depuis le bag de la session courante
public function get($variable){
    $bag_session_courante = $this->getSessionCourante();
	if ($bag_session_courante->get($variable) == null) {
		if ($variable == 'label'){
			$label = $this->securityContext->getToken()->getUser();
			$this->set($variable, $label);
			return $label;
		} else {
			return array();
		}
	} else {
		return $bag_session_courante->get($variable);
	}
}


public function getStr($variable){
    $bag_session_courante = $this->getSessionCourante();
        if ($bag_session_courante->get($variable) == null) {
                if ($variable == 'label'){
                        $label = $this->securityContext->getToken()->getUser();
                        $this->set($variable, $label);
                        return $label;
                } else {
                        return '';
                }
        } else {
                return $bag_session_courante->get($variable);
        }
}


// Définition d'une variable dans le bag de la session courante
public function set($variable, $valeur_variable) {
    $bag_session_courante = $this->getSessionCourante();
    $bag_session_courante->set($variable, $valeur_variable);
    $this->saveSession($bag_session_courante);
}


public function remove($variable){
    $bag_session_courante = $this->getSessionCourante();
    $bag_session_courante->remove($variable);
	$this->saveSession($bag_session_courante);
}

// Création d'une nouvelle session
public function nouvelleSession($nom_de_session = 'session_1'){
	// Récupération du tableau des sessions
	$tab_sessions = $this->session->get('tab_sessions_ipc', array());
	// Vérification que le bag de session n'existe pas déjà : Si il n'existe pas création du bag et ajout du nom au tableau des sessions
	$this->nom_de_session = $nom_de_session;
    if (! $this->session->has($this->nom_de_session)) {
        $bag_session_courante = new AttributeBag();
        $bag_session_courante->setName($this->nom_de_session);
        $this->saveSession($bag_session_courante);
		array_push($tab_sessions, $this->nom_de_session);
		$this->session->set('tab_sessions_ipc', $tab_sessions);
    }
	$this->changeSession($this->nom_de_session);
}

// Changement de session
public function changeSession($nom_de_session){
	$this->session->set('nom_session_courante', $nom_de_session);
}

// Retourne la liste des sessions existantes
public function getTabSessions(){
	$tab_sessions = $this->session->get('tab_sessions_ipc', array());
	return $tab_sessions;
}


}
