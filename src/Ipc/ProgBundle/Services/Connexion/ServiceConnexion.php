<?php
//src/Ipc/ProgBundle/Services/Connexion/Connexion.php
namespace Ipc\ProgBundle\Services\Connexion;

use \PDO;
use \PDOException;

class ServiceConnexion 
{
  // Les bases de données doivent être enregistrées en minuscule dans phpMyAdmin
  private $dbh;
  private $base;
  private $chemin_socket;
  private $srv_doctrine;
  private $srv_session;

  public function getBase() {
	return $this->base;
  }


  public function __construct($srv_doctrine, $srv_session, $base, $chemin_socket) {
	$this->srv_session = $srv_session;
	if ($this->srv_session->get('base') != null) {
		$base = $this->srv_session->get('base');
	}
	$this->base = strtolower($base);
	$this->srv_session->set('base', $this->base);
	try {
		$this->chemin_socket = $chemin_socket;
		$this->srv_doctrine = $srv_doctrine;
		$this->dbh = new PDO("mysql:dbname=$base;unix_socket=$chemin_socket", 'cargo', 'adm5667');
		$this->dbh->exec('SET CHARACTER SET UTF-8');
		$this->dbh->exec('SET NAMES utf8');
	} catch (PDOException $e) {
		//echo $e->getMessage();
		$this->dbh = null;
	}
  } 

  public function getDbh() {
 	return $this->dbh;
  }

  public function connect() {
	try {
		$base = $this->base;
		$chemin_socket = $this->chemin_socket;
		$this->dbh = new PDO("mysql:dbname=$base;unix_socket=$chemin_socket", 'cargo', 'adm5667');
		$this->dbh->exec('SET CHARACTER SET UTF-8');
        $this->dbh->exec('SET NAMES utf8');
	} catch (PDOException $e) {
		//echo $e->getMessage();
		$this->dbh = null;
	}
	return($this->dbh);
  }

  public function disconnect() {
	$this->dbh = null;
	return(null);
  }


  public function changementDeBase($base, $ancienne_base = null) {
	$this->disconnect();
	$this->base = strtolower($base);
	if ($this->connect() == null) {
		if ($ancienne_base != null) {
			$this->base = $ancienne_base;
			$this->connect();
		}
	}
	$this->srv_session->set('base', $this->base);
	$this->srv_session->set('pageTitle', array());
  }

/*
  public function switchDatabase($dbName, $dbUser, $dbPass)
  {
    $connection = $this->get(sprintf('doctrine.dbal.%s_connection', 'dynamic_conn'));
    $connection->close();

    $refConn = new \ReflectionObject($connection);
    $refParams = $refConn->getProperty('_params');
    $refParams->setAccessible('public'); //we have to change it for a moment

    $params = $refParams->getValue($connection);
    $params['dbname'] = $dbName;
    $params['user'] = $dbUser;
    $params['password'] = $dbPass;

    $refParams->setAccessible('private');
    $refParams->setValue($connection, $params);
    //$this->container->get('doctrine')->resetEntityManager('dynamic_manager'); // for sure (unless you like broken transactions)
  }
*/

  // Fonction qui retourne l'entité manager de la base en cours d'étude
  public function getManager() {
    return $this->srv_doctrine->getManager($this->base);
  }

}
