<?php
//src/Ipc/UserBundle/Controller/SecurityController.php

namespace Ipc\UserBundle\Controller;

use FOS\UserBundle\Controller\SecurityController as BaseController;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;

use Ipc\ProgBundle\Entity\Configuration;
use Ipc\ProgBundle\Entity\Site;

class SecurityController extends BaseController 
{
  private $session;
  private $em;

  public function constructeur(){
    if (empty($this->session)) {
        $service_session = $this->container->get('ipc_prog.session');
        $this->session = $service_session;
    }
	//$this->container->get('ipc_prog.connectbd')->changementDeBase('boilerbox_c671_dev');
	//$this->container->get('ipc_prog.connectbd')->changementDeBase('boilerbox_c782');
	$this->em = $this->container->get('ipc_prog.connectbd')->getManager();
  }

	
  ///public function loginAction(Request $request) {
  public function loginAction() {
		$this->constructeur();
		$request = $this->container->get('request');
		//$session = $this->container->get('request')->getSession();
		$service_configuration = $this->container->get('ipc_prog.configuration');
		// Récupération des informations de la date courante
		$tab_date = $service_configuration->maj_date();
        // Création du message pour le titre de la page
        // Récupération du site courant : Du numero de l'affaire
        $connexion 	= $this->container->get('ipc_prog.connectbd');
        $dbh = $connexion->getDbh();
        $site = new Site();
        $id_site = $site->SqlGetIdCourant($dbh);
        $site = $this->em->getRepository('IpcProgBundle:Site')->find($id_site);
        if ($site != null) {
            $affaire = $site->getAffaire();
            $intitule = $site->getintitule();
            $session_pageTitle['title'] = $affaire.' : '.$intitule;
        } else {
	    	$affaire = ' - ';
	    	$intitule = ' - ';
        }
		$session_pageTitle['version'] = "";
		$versionning = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('numero_version');
		if ($versionning != null) {
	    	$session_pageTitle['version'] = $versionning->getValeur();
		}
        $this->session->set('pageTitle', $session_pageTitle);
        $dbh = $connexion->disconnect();
        $session = $request->getSession();
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } elseif (null !== $session && $session->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = '';
        }
        if ($error) {
            // TODO: this is a potential security risk (see http://trac.symfony-project.org/ticket/9523)
            $error = $error->getMessage();
        }
        // last username entered by the user
        $lastUsername = (null === $session) ? '' : $session->get(SecurityContext::LAST_USERNAME);
        $csrfToken = $this->container->has('form.csrf_provider')
            ? $this->container->get('form.csrf_provider')->generateCsrfToken('authenticate')
            : null;
        return $this->renderLogin(array(
            'last_username' => $lastUsername,
            'error'         => $error,
            'csrf_token'    => $csrfToken,
	    	'affaire'	    => $affaire,
	    	'intitule'	    => $intitule,
	    	'leJour'	    => $tab_date['jour'],
	    	'lHeure'	    => $tab_date['heure'],
	    	'timestamp'	    => $tab_date['timestamp']
        ));
    }


    /**
     * Renders the login template with the given parameters. Overwrite this function in
     * an extended controller to provide additional data for the login template.
     *
     * @param array $data
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function renderLogin(array $data) {
		$template = sprintf('IpcUserBundle:Security:login.html.%s', $this->container->getParameter('fos_user.template.engine'));
        return $this->container->get('templating')->renderResponse($template, $data);
    }
}
