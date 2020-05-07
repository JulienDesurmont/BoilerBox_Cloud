<?php

namespace Ipc\ProgBundle\Listener\Locale;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class LocaleListener
{
	protected $service_session;

	public function __construct($service_session){
		$this->service_session = $service_session;
	}


	// Vérifie si la locale change et réinitialise les variables de session lors d'un changement detecté
    public function checkLocale(GetResponseEvent $event)
    {
		$last_locale = $this->service_session->get('lang_locale');	
		$new_locale = $event->getRequest()->getLocale();

		if ($new_locale != $last_locale) {
			// Si un changement de locale est détecté : Réinitialisation des variables de session
			$this->service_session->reinitialisationSession('liste_des_requetes');
			$this->service_session->set('lang_locale', $new_locale);
 			$url_fichier_log = getenv("DOCUMENT_ROOT").'/web/logs/system.log';
			$date = new \Datetime();
        	$dateChangementLocale = $date->format('Y-m-d H:i:s');
			$fichier_log = fopen($url_fichier_log, 'a+');
			fputs($fichier_log, "$dateChangementLocale;Nouvelle locale : ".$event->getRequest()->getLocale()."\n");
			fclose($fichier_log);
		}
    }
}
