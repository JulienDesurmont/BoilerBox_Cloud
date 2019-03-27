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
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\InvalidArgumentException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;


use Ipc\ProgBundle\Entity\Configuration;
use \PDO;
use \PDOException;


/**
 * UsernamePasswordFormAuthenticationListener is the default implementation of
 * an authentication via a simple form composed of a username and a password.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class UsernamePasswordFormAuthenticationListener extends AbstractAuthenticationListener {
    private $csrfTokenManager;

    /**
     * {@inheritdoc}
     */
    public function __construct(SecurityContextInterface $securityContext, AuthenticationManagerInterface $authenticationManager, SessionAuthenticationStrategyInterface $sessionStrategy, HttpUtils $httpUtils, $providerKey, AuthenticationSuccessHandlerInterface $successHandler, AuthenticationFailureHandlerInterface $failureHandler, array $options = array(), LoggerInterface $logger = null, EventDispatcherInterface $dispatcher = null, $csrfTokenManager = null) {
		$configuration = new Configuration();
        try {
			$fichier_config = fopen(getenv("DOCUMENT_ROOT").'/web/config_ipc.txt', 'r');
            $base = trim(fgets($fichier_config));
			$socket = trim(fgets($fichier_config));
            fclose($fichier_config);
			$dbh = new PDO("mysql:dbname=$base;unix_socket=$socket", 'cargo', 'adm5667');
			$dbh->exec('SET CHARACTER SET UTF-8');
			$dbh->exec('SET NAMES utf8');
	    	if ($configuration->SqlGetParam($dbh,'timezone') != null) {
	        	date_default_timezone_set($configuration->SqlGetParam($dbh,'timezone'));
	    	}
        } catch (PDOException $e) {
            echo $e->getMessage();
            $dbh = null;
        }
        if ($csrfTokenManager instanceof CsrfProviderInterface) {
            $csrfTokenManager = new CsrfProviderAdapter($csrfTokenManager);
        } elseif (null !== $csrfTokenManager && ! $csrfTokenManager instanceof CsrfTokenManagerInterface) {
            throw new InvalidArgumentException('The CSRF token manager should be an instance of CsrfProviderInterface or CsrfTokenManagerInterface.');
        }

        parent::__construct($securityContext, $authenticationManager, $sessionStrategy, $httpUtils, $providerKey, $successHandler, $failureHandler, array_merge(array(
            'username_parameter' => '_username',
            'password_parameter' => '_password',
	    	'label_parameter' => '_label',
			'database_parameter' => '_database',
            'csrf_parameter' => '_csrf_token',
            'intention' => 'authenticate',
            'post_only' => true,
        ), $options), $logger, $dispatcher);
        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function requiresAuthentication(Request $request) {
        if ($this->options['post_only'] && ! $request->isMethod('POST')) {
            return false;
        }
        return parent::requiresAuthentication($request);
    }

    /**
     * {@inheritdoc}
     */
    protected function attemptAuthentication(Request $request) {
        if (null !== $this->csrfTokenManager) {
            $csrfToken = $request->get($this->options['csrf_parameter'], null, true);
            if (false === $this->csrfTokenManager->isTokenValid(new CsrfToken($this->options['intention'], $csrfToken))) {
                throw new InvalidCsrfTokenException('Invalid CSRF token.');
            }
        }
        if ($this->options['post_only']) {
            $username = trim($request->request->get($this->options['username_parameter'], null, true));
            $password = $request->request->get($this->options['password_parameter'], null, true);
	    	$label = $request->request->get($this->options['label_parameter'], null, true);
			$database = $request->request->get($this->options['database_parameter'], null, true);
        } else {
            $username = trim($request->get($this->options['username_parameter'], null, true));
            $password = $request->get($this->options['password_parameter'], null, true);
	    	$label = $request->get($this->options['label_parameter'], null, true);
			$database = $request->get($this->options['database_parameter'], null, true);
        }
        $request->getSession()->set(SecurityContextInterface::LAST_USERNAME, $username);
        // Définition de la variable de session 'label'
		$request->getSession()->set('label', $label);
		$request->getSession()->set('database', $database);
        $_SESSION['label'] = $label;
		$_SESSION['database'] = $database;
        // Inscription de l'utilisateur connecté au fichier de log
        $urlFichierConnexion = getenv("DOCUMENT_ROOT").'/web/logs/tokenIpcWeb.txt';
        $fichierConnexion = fopen($urlFichierConnexion, 'a+');
        $date = new \Datetime();
		if (empty($label)) {
	    	fwrite($fichierConnexion, $date->format('Y-m-d H:i:s').";Tentative de connexion locale : compte [$username]\n");
        } else {
            fwrite($fichierConnexion, $date->format('Y-m-d H:i:s').";Tentative de connexion de $label\n");
		}
       	fclose($fichierConnexion);
        return $this->authenticationManager->authenticate(new UsernamePasswordToken($username, $password, $this->providerKey));
    }
}
