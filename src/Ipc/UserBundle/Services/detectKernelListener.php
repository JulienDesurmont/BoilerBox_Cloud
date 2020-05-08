<?php
namespace Ipc\UserBundle\Services;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Session\Session;

//use Symfony\Component\EventDispatcher\EventDispatcher;
use Ipc\UserBundle\Services\detectLogin;



class detectKernelListener {
    protected $detectLogin;
    protected $dateConnexion;
    protected $session;
    protected $security;

    public function __construct(detectLogin $detectLogin, SecurityContext $security, Session $session) {
        $this->detectLogin = $detectLogin;
        $date = new \Datetime();
        $this->dateConnexion = $date->format('Y-m-d H:i:s');
		$this->security	= $security;
		$this->session = $session;
    }

    public function processResponse(FilterResponseEvent $event) {
		if ($this->security->isGranted('ROLE_ADMIN_LTS')) {
			$test = $this->session->get('redirection');
			if ($test == null) {
				$test = 0;
			}
			$user = $this->security->getToken()->getUser();
			$session_id = $this->session->getId();
			$this->detectLogin->enregistreUtilisateur('CONTROLLER', $this->dateConnexion, $user.' - '.$session_id);
			if ($test < 6) {
				$test ++;
				$this->session->set('redirection', $test);
        		// Récupère le retour du controller (ce qu'il y a dans son 'return')
        		$reponse_init   = $event->getResponse();
				$content = "<!DOCTYPE html>
							<html>
							    <head>
							        <meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
									<meta http-equiv='refresh' content='1;url=/BoilerBox/web/app.php/etat/afficheRequeteType/listing' />
        							<title>Redirecting to /BoilerBox/web/app.php/etat/afficheRequeteType/listing</title>
   			 					</head>
    							<body>
        							Redirecting to <a href='/BoilerBox/web/app.php/etat/afficheRequeteType/listing'>etat/afficheRequeteType/listing/</a>.
    							</body>
							</html>
							";
				$this->detectLogin->enregistreUtilisateur('CONTROLLER', $this->dateConnexion, $reponse_init);
        		$event->setResponse($reponse_init);
			}
		} else {
			$this->detectLogin->enregistreUtilisateur('CONTROLLER', $this->dateConnexion, $event->getResponse());
		}
    }
}
