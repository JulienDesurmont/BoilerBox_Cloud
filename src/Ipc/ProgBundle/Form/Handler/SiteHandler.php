<?php
# src/Ipc/ProgBundle/Form/Handler/SiteHandler.php
namespace Ipc\ProgBundle\Form\Handler;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Ipc\ProgBundle\Entity\Site;

// SiteHandler : Utilisé pour gérer la soumission des formulaires
class SiteHandler {

protected $request;
protected $form;
protected $mailer;

//	Initialisation du Handler avec le formulaire et la requête
public function __construct(Form $form, Request $request, $mailer) {
	$this->form = $form;
	$this->request = $request;
	$this->mailer = $mailer;
}

//	Gére la reception du formulaire
/**
 * Process form
 * 
 * @return boolean
*/
public function process($dbh) {
	//	Si la requête est de type POST on récupère les données du formulaire dans la variable $data 
	//	- On Execute le traitement de formulaire en cas de succès et on retourne true
	if ('POST' == $this->request->getMethod()) {
		$this->form->bind($this->request);
		$data = $this->form->getData();
		$this->onSuccess($data,$dbh);
		return true;
	}
	return false;
}

//	A faire en cas de succès d'envoi du formulaire
protected function onSuccess($site, $dbh) {
	//	Si le Site est définit comme site courant on passe à false les anciens sites
	if ($site->getSiteCourant() == true) {
		//	Le précédent Site courant passe à false et sa date de fin d'exploitation est mise à jour	
		$site->setId($site->SqlGetIdCourant($dbh));
		$site->SqlUncheck($dbh,$site->SqlGetIdCourant($dbh),$site->getDebutExploitationStr());
	}
	/* Le nouveau Site est enregistré en base de donnée*/
	$site->SqlInsert($dbh);
	// Récupération de l'id du site inséré
	$site->setId = $site->SqlGetIdAffaire($dbh,$site->getAffaire());
	$localisations = $site->getLocalisations();
	// Les localisations sont insérées en base
	foreach ($localisations as $localisation) {
		$localisation->SqlInsert($dbh,$site->getId());
	}
}

}
