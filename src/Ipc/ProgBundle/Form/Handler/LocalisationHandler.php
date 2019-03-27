<?php
#src/Ipc/LocalisationHandler/Form/Handler/LocalisationHandler.php
namespace Ipc\ProgBundle\Form\Handler;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Ipc\ProgBundle\Entity\Localisation;

// LocalisationHandler : Utilisé pour gérer la soumission des formulaires
class LocalisationHandler {

protected $request;
protected $form;

//	Initialisation du Handler avec le formulaire et la requête
public function __construct(Form $form, Request $request) {
	$this->form = $form;
	$this->request = $request;
}

//	Gére la reception du formulaire
/**
 * Process form
 * 
 * @return boolean
*/
public function process($dbh) {
	// Si la requête est de type POST on récupère les données du formulaire dans la variable $data - On Execute le traitement de formulaire en cas de succès et on retourne true
	if ('POST' == $this->request->getMethod()) {
		$this->form->bind($this->request);
		$data = $this->form->getData();
		$this->onSuccess($data,$dbh);
		return true;
	}
	return false;
}

//	A faire en cas de succès d'envoi du formulaire
protected function onSuccess($data, $dbh) {
	$localisation = new Localisation();
	$localisation->setNumeroLocalisation($data['numeroLocalisation']);
	$localisation->setAdresseIp($data['adresseIp']);
	$localisation->SqlInsert($dbh);
}

}
