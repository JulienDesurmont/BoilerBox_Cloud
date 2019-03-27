<?php
#/src/Ipc/ProgBundle/Form/Handler/ConfigurationHandler.php
namespace Ipc\ProgBundle\Form\Handler;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Ipc\ProgBundle\Entity\Configuration;

class ConfigurationHandler {

protected $form;
protected $request;

public function __construct(Form $form, Request $request) {
	$this->form = $form;
	$this->request = $request;
}

public function process($dbh) {
	if ('POST' == $this->request->getMethod()) {
		$this->form->bind($this->request);
		$data = $this->form->getData();
		$this->onSuccess($data,$dbh);
		return true;
	}	
	return false;
}

//	Fonction appelée en cas de reception du formulaire
public function onSuccess($configurations, $dbh)
{
}

}
