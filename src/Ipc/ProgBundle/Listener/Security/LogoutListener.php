<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ipc\ProgBundle\Listener\Security;

use Symfony\Component\Form\Extension\Csrf\CsrfProvider\CsrfProviderAdapter;
use Symfony\Component\Form\Extension\Csrf\CsrfProvider\CsrfProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Exception\InvalidArgumentException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Exception\LogoutException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;
use Symfony\Component\Security\Http\Firewall\ListenerInterface; 

use Ipc\ProgBundle\Entity\Configuration;
use \PDO;
use \PDOException;


/**
 * LogoutListener logout users.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class LogoutListener implements ListenerInterface {
    private $securityContext;
    private $options;
    private $handlers;
    private $successHandler;
    private $httpUtils;
    private $csrfTokenManager;

    /**
     * Constructor.
     *
     * @param SecurityContextInterface      $securityContext
     * @param HttpUtils                     $httpUtils        An HttpUtilsInterface instance
     * @param LogoutSuccessHandlerInterface $successHandler   A LogoutSuccessHandlerInterface instance
     * @param array                         $options          An array of options to process a logout attempt
     * @param CsrfTokenManagerInterface     $csrfTokenManager A CsrfTokenManagerInterface instance
     */
    public function __construct(SecurityContextInterface $securityContext, HttpUtils $httpUtils, LogoutSuccessHandlerInterface $successHandler, array $options = array(), $csrfTokenManager = null) {

        $configuration = new Configuration();
        try{
			$fichier_config = fopen(getenv("DOCUMENT_ROOT").'/config_ipc.txt', 'r');
            $base = trim(fgets($fichier_config));
			$socket = trim(fgets($fichier_config));
            fclose($fichier_config);
            //$dbh = new PDO('mysql:host=127.0.0.1;dbname=ipc', 'cargo', 'adm5667');
			$dbh = new PDO("mysql:dbname=$base;unix_socket=$socket", 'cargo', 'adm5667');
			$dbh->exec('SET CHARACTER SET UTF-8');
			$dbh->exec('SET NAMES utf8');
	    	if($configuration->SqlGetParam($dbh,'timezone') != null) {
            	date_default_timezone_set($configuration->SqlGetParam($dbh,'timezone'));
	    	}
        }catch (PDOException $e){
            echo $e->getMessage();
            $dbh = null;
        }


        if ($csrfTokenManager instanceof CsrfProviderInterface) {
            $csrfTokenManager = new CsrfProviderAdapter($csrfTokenManager);
        } elseif (null !== $csrfTokenManager && !$csrfTokenManager instanceof CsrfTokenManagerInterface) {
            throw new InvalidArgumentException('The CSRF token manager should be an instance of CsrfProviderInterface or CsrfTokenManagerInterface.');
        }

        $this->securityContext = $securityContext;
        $this->httpUtils = $httpUtils;
        $this->options = array_merge(array(
            'csrf_parameter' => '_csrf_token',
            'intention'      => 'logout',
            'logout_path'    => '/logout',
        ), $options);
        $this->successHandler = $successHandler;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->handlers = array();
    }

    /**
     * Adds a logout handler
     *
     * @param LogoutHandlerInterface $handler
     */
    public function addHandler(LogoutHandlerInterface $handler)
    {
        $this->handlers[] = $handler;
    }

    /**
     * Performs the logout if requested
     *
     * If a CsrfTokenManagerInterface instance is available, it will be used to
     * validate the request.
     *
     * @param GetResponseEvent $event A GetResponseEvent instance
     *
     * @throws LogoutException if the CSRF token is invalid
     * @throws \RuntimeException if the LogoutSuccessHandlerInterface instance does not return a response
     */
    public function handle(GetResponseEvent $event) {
        $request = $event->getRequest();
        if (!$this->requiresLogout($request)) {
            return;
        }
		//	Enregistrement du logout
		$date = new \Datetime();
		$dateDeconnexion = $date->format('Y-m-d H:i:s');
		$urlFichierToken = getenv("DOCUMENT_ROOT").'/logs/tokenIpcWeb.txt';
		$fichierToken = fopen($urlFichierToken,'a+');
        if (! empty($_SESSION['label'])) {
            fputs($fichierToken,"$dateDeconnexion;Demande de déconnexion de ".$_SESSION['label']."\n");
        }else{
	    	fputs($fichierToken,"$dateDeconnexion;Demande de déconnexion locale\n");
		}
		fclose($fichierToken);
        if (null !== $this->csrfTokenManager) {
            $csrfToken = $request->get($this->options['csrf_parameter'], null, true);
            if (false === $this->csrfTokenManager->isTokenValid(new CsrfToken($this->options['intention'], $csrfToken))) {
                throw new LogoutException('Invalid CSRF token.');
            }
        }
        $response = $this->successHandler->onLogoutSuccess($request);
        if (!$response instanceof Response) {
            throw new \RuntimeException('Logout Success Handler did not return a Response.');
        }
        // handle multiple logout attempts gracefully
        if ($token = $this->securityContext->getToken()) {
            foreach ($this->handlers as $handler) {
                $handler->logout($request, $response, $token);
            }
        }
        $this->securityContext->setToken(null);
        $event->setResponse($response);
    }

    /**
     * Whether this request is asking for logout.
     *
     * The default implementation only processed requests to a specific path,
     * but a subclass could change this to logout requests where
     * certain parameters is present.
     *
     * @param Request $request
     *
     * @return Boolean
     */
    protected function requiresLogout(Request $request)
    {
        return $this->httpUtils->checkRequestPath($request, $this->options['logout_path']);
    }
}
