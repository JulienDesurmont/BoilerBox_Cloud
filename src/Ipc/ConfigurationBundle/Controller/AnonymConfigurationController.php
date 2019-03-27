<?php
//src/Ipc/ConfigurationBundle/Controller/AnonymConfigurationController.php

namespace Ipc\ConfigurationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Ipc\ProgBundle\Entity\Site;


class AnonymConfigurationController extends Controller {
private $session;
private $pageTitle;
private $pageActive;
private $userLabel;

public function constructeur(){
    if (empty($this->session)) {
        $service_session = $this->container->get('ipc_prog.session');
        $this->session = $service_session;
    }
}

private function initialisation() {
	$this->constructeur();
	$this->pageTitle = $this->session->get('pageTitle');
	$this->pageActive = $this->session->get('page_active');
	$this->userLabel = $this->session->get('label');
    if ($this->userLabel == '' ) {
        if ($this->get('security.context')->isGranted('ROLE_ADMIN')) {
            $this->userLabel = 'Administrateur';
        } elseif ($this->get('security.context')->isGranted('ROLE_SUPERVISEUR')) {
            $this->userLabel = 'Superviseur';
        } elseif ($this->get('security.context')->isGranted('ROLE_TECHNICIEN')) {
            $this->userLabel = 'Technicien';
        } elseif ($this->get('security.context')->isGranted('ROLE_USER')) {
            $this->userLabel = 'Client';
        }
    }
}

public function getInfosSessionAction() {
	$this->constructeur();
	$this->initialisation();
	$tab_session = array();
	$tab_session['pageTitle'] = $this->pageTitle;
	$tab_session['pageActive'] = $this->pageActive;
	$tab_session['userLabel'] = $this->userLabel;
	echo json_encode($tab_session);
	$response = new Response();
	return $response;
}

public function selectLangueAction($langue = null, $site = null){
	$this->constructeur();
    if($langue != null) {
		$this->session->set('_locale', $langue);
    }
    $url = $this->container->get('request')->headers->get('referer');
    if(empty($url)) {
		//	Redirection vers la page d'accueil si aucune url 
		if (($site == null) || ($site == 'boilerbox')) {
			$url = $this->container->get('router')->generate('ipc_prog_homepage', array('_locale' => $langue));
		} elseif ($site == 'boilerboxlive') {
			$url = $this->container->get('router')->generate('ipc_supervision_accueil', array('_locale' => $langue));	
		}	
    } else {
		// Modification de la variable de langue et redirection de la page
		$pattern_langue = "/^(.+?[app_dev.php|app.php]\/)(.+?)(\/.*)$/";
		if (preg_match($pattern_langue, $url, $tab_url)) {
			$remplacement = "$1$langue$3";
			$url = preg_replace($pattern_langue, $remplacement, $url);
		}
	} 
    return new RedirectResponse($url);
}

}
