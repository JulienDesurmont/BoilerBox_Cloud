<?php
namespace Ipc\UserBundle\Services;

use Symfony\Component\Security\Core\Event\AuthenticationEvent;

class detectLoginListener {
    // Login à capturer
    protected $detectLogin;
    protected $dateConnexion;

    public function __construct(detectLogin $detectLogin) {
		$this->detectLogin = $detectLogin;
		$date = new \Datetime();
		$this->dateConnexion = $date->format('Y-m-d H:i:s');
    }

    public function successLogin(AuthenticationEvent $event) {
		// Récupération du token : 
		$token = $event->getAuthenticationToken();
		// Modification des informations
		$this->detectLogin->enregistreUtilisateur('SUCCESS', $this->dateConnexion, $token);
    }

    public function failedLogin(AuthenticationEvent $event) {
    	$token = $event->getAuthenticationException();
		$this->detectLogin->enregistreUtilisateur('FAILED', $this->dateConnexion, $token);
    }
}

