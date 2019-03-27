<?php
//src/Ipc/UserBundle/Controller/RegistrationController

namespace Ipc\UserBundle\Controller;

use FOS\UserBundle\Controller\RegistrationController as BaseController;

use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\Event\UserEvent;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\UserBundle\Model\UserInterface;

class RegistrationController extends BaseController 
{
  private $session;
  private $pageActive;
  private $ping_intervalle;
  private $ping_timeout;
  private $em;

  public function constructeur(){
    if (empty($this->session)) {
        $service_session = $this->container->get('ipc_prog.session');
        $this->session = $service_session;
    }
	$this->em = $this->get('ipc_prog.connectbd')->getManager();
  }

  public function initialisation() {
	$this->constructeur();
    $this->pageActive = $this->session->get('pageActive');
   	$this->ping_intervalle = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('ping_intervalle')->getValeur();
   	$this->ping_timeout = $this->em->getRepository('IpcProgBundle:Configuration')->findOneByParametre('ping_timeout')->getValeur();
  }

  public function registerAction()
  {
    $this->initialisation();
    $form = $this->container->get('fos_user.registration.form');
    $formHandler = $this->container->get('fos_user.registration.form.handler');
    $confirmationEnabled = $this->container->getParameter('fos_user.registration.confirmation.enabled');

    $process = $formHandler->process($confirmationEnabled);
    if ($process) {
    	$user = $form->getData();
        $authUser = false;
        if ($confirmationEnabled) {
            $this->container->get('session')->set('fos_user_send_confirmation_email/email', $user->getEmail());
            $route = 'fos_user_registration_check_email';
        } else {
            $authUser = true;
            $route = 'ipc_createUser';
        }
        $this->setFlash('fos_user_success', 'registration.flash.user_created');
        $url = $this->container->get('router')->generate($route);
        $response = new RedirectResponse($url);
        if ($authUser) {
            $this->authenticateUser($user, $response);
        }
        return $response;
    }

    return $this->container->get('templating')->renderResponse('FOSUserBundle:Registration:register.html.'.$this->getEngine(), array(
        'form' => $form->createView(),
        'pageActive' => $this->pageActive,
        'ping_intervalle' => $this->ping_intervalle,
        'ping_timeout' => $this->ping_timeout
    ));
  }
}
