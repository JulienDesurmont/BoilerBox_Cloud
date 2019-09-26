<?php
// src/Ipc/ProgBundle/Services/Mailing/ServiceMailing.php
namespace Ipc\ProgBundle\Services\Mailing;

use Ipc\ProgBundle\Entity\Configuration;
use Ipc\ProgBundle\Entity\Site;

// Service d'envoi de mails ( par SMTP )
class ServiceMailing {
protected $mailer;
protected $templating;
protected $log;
protected $fichier_log;
protected $dbh;
protected $document_root;
protected $email_assistance;

public function __construct(\Swift_Mailer $mailer, $templating, $log, $connexion, $email_assistance) {
	$this->log = $log;
	$this->fichier_log = 'mailing.log';
	$this->mailer = $mailer;
	$this->templating = $templating;
	$this->dbh = $connexion->getDbh();
	//$this->document_root = getenv("DOCUMENT_ROOT");
	$this->document_root = __DIR__.'/../../../../..';
	$this->email_assistance = $email_assistance;
}


public function sendAssistance($sujet, $contenu_titre, $liste_contenus) {
    // Récupération de l'affaire du site courant
    $message = \Swift_Message::newInstance()
        ->setSubject($sujet)
        ->setFrom($this->email_assistance)
        ->setTo($this->email_assistance);
    $image_link = $message->embed(\Swift_Image::fromPath($this->document_root.'/web/images/icones/logo_lci.jpg'));
	$liste_contenus= [];
    $message ->setBody($this->templating->render('IpcProgBundle:Mail:email.html.twig', array(
                                                                                        'liste_contenus' => $liste_contenus,
                                                                                        'image_link' => $image_link))
    );
    $message ->setContentType('text/html');
    // Send the message.
    $nb_delivery = $this->mailer->send($message);
    if ($nb_delivery == 0) {
        $this->log->setLog("[ERROR] [MAIL];Echec de l'envoi de l'email : $sujet à $this->email_assistance", $this->fichier_log);
        return(1);
    } else {
        $this->log->setLog("[INFO] [MAIL];Email envoyé à $this->email_assistance : $sujet", $this->fichier_log);
        $this->sendAllMails();
        return(0);
    }
}


public function send($destinataire, $sujet, $contenu_titre, $liste_contenus) {
	// Récupération de l'affaire du site courant
	$site = new Site();
	$affaire_site = $site->SqlGetCourant($this->dbh, 'affaire');
	$affaire_site .= '@cargo-france.fr';
	$message = \Swift_Message::newInstance()
		->setSubject($sujet)
		->setFrom($affaire_site)
		->setTo($destinataire);
	$image_link = $message->embed(\Swift_Image::fromPath($this->document_root.'/web/images/icones/logo_lci.jpg'));
	$message ->setBody($this->templating->render('IpcProgBundle:Mail:email.html.twig', array(
																						'liste_contenus' => $liste_contenus,
																						'image_link' => $image_link))
	);
	$message ->setContentType('text/html');
	// Send the message.
	$nb_delivery = $this->mailer->send($message);
	if ($nb_delivery == 0) {
		$this->log->setLog("[ERROR] [MAIL];Echec de l'envoi de l'email : $sujet à $destinataire", $this->fichier_log);
		return(1);
	} else {
		$this->log->setLog("[INFO] [MAIL];Email envoyé à $destinataire : $sujet", $this->fichier_log);
		$this->sendAllMails();
		return(0);
	}
}	

// Sauvegarde le contenu du mail dans un fichier html situé dans le dossier $this->document_root./web/uploads/rapportsJournaliers
public function saveMail($destinataire, $titreMail, $liste_contenus) {
	$fichierSauvegarde = $this->document_root.'/web/uploads/rapportsJournaliers/'.$titreMail;
	// Sauvegarde du fichier si il n'existe pas déjà
	if (file_exists($fichierSauvegarde) === false){
    	// Récupération de l'affaire du site courant
    	$site = new Site();
    	$affaire_site = $site->SqlGetCourant($this->dbh, 'affaire');
    	$affaire_site .= '@cargo-france.fr';
    	$image_link = '';
    	$message = $this->templating->render('IpcProgBundle:Mail:email.html.twig', array(
                           																'liste_contenus' => $liste_contenus,
                                                                                        'image_link' => $image_link));
		$fichier_mail = fopen($fichierSauvegarde, 'w+');
		fprintf($fichier_mail, chr(0xEF).chr(0xBB).chr(0xBF));
		fputs($fichier_mail, $message);
		fclose($fichier_mail);
	}
    return(0);
}


public function sendGraph($destinataire, $sujet, $contenu_titre, $liste_contenus) {
	// Récupération de l'affaire du site courant
	$site = new Site();
	$affaire_site = $site->SqlGetCourant($this->dbh, 'affaire');
	$affaire_site .= '@cargo-france.fr';
	$message = \Swift_Message::newInstance()
		->setSubject($sujet)
		->setFrom($affaire_site)
		->setTo($destinataire);

	$message ->setBody($this->templating->render('IpcProgBundle:Mail:emailGraph.html.twig'));
	$message ->setContentType('text/html');
	$nb_delivery = $this->mailer->send($message); 
	if ($nb_delivery == 0) {
		$this->log->setLog("[ERROR] [MAIL];Echec de l'envoi de l'email : $sujet à $destinataire", $this->fichier_log);
		return(1);
	} else {
		$this->log->setLog("[INFO] [MAIL];Email graphique envoyé à $destinataire : $sujet", $this->fichier_log);
		$this->sendAllMails();
		return(0);
	}
}

public function sendAnalyse($destinataire, $sujet, $contenu_titre, $liste_contenus) {
	// Récupération de l'affaire du site courant
	$site = new Site();
	$affaire_site = $site->SqlGetCourant($this->dbh, 'affaire');
	$affaire_site .= '@cargo-france.fr';
	$message = \Swift_Message::newInstance()
		->setSubject($sujet)
		->setFrom($affaire_site)
		->setTo($destinataire);
	$image_link = $message->embed(\Swift_Image::fromPath($this->document_root.'/web/images/icones/logo_lci.jpg'));
	$message ->setBody($this->templating->render('IpcProgBundle:Mail:emailAnalyse.html.twig', array(
																								'liste_contenus' => $liste_contenus,
																								'image_link' => $image_link))
	);
	$message ->setContentType('text/html');
	$nb_delivery = $this->mailer->send($message); 
	if ($nb_delivery == 0) {
		$this->log->setLog("[ERROR] [MAIL];Echec de l'envoi de l'email : $sujet à $destinataire", $this->fichier_log);
		return(1);
	} else {
		 $this->log->setLog("[INFO] [MAIL];Email analyse envoyé à $destinataire : $sujet", $this->fichier_log);
		$this->sendAllMails();
		return(0);
	}
}

//	Fonction qui envoi par mail la table d'échange du site courant
public function sendTableEchange($destinataire, $sujet, $cheminFichier, $liste_contenus) {
	$site = new Site();
	$affaire_site = $site->SqlGetCourant($this->dbh, 'affaire');
	$affaire_site .= '@cargo-france.fr';
	$message = \Swift_Message::newInstance()
		->setSubject($sujet)
		->setFrom($affaire_site)
		->setTo($destinataire);
	$image_link = $message->embed(\Swift_Image::fromPath($this->document_root.'/web/images/icones/logo_lci.jpg'));
	$message 
		->attach(\Swift_Attachment::fromPath($cheminFichier))
		->setBody($this->templating->render('IpcProgBundle:Mail:emailTableEchange.html.twig', array('liste_contenus' => $liste_contenus, 'image_link' => $image_link)))
		->setContentType('text/html');
	$nb_delivery = $this->mailer->send($message);
	if ($nb_delivery == 0) {
		$this->log->setLog("[ERROR] [MAIL];Echec de l'envoi de l'email : $sujet à $destinataire", $this->fichier_log);
		return(1);
	} else {
		$this->log->setLog("[INFO] [MAIL];Email Table d'échange envoyé à $destinataire : $sujet", $this->fichier_log);
		$this->sendAllMails();
		return(0);
	}
}

public function sendAllMails() {
	$cheminConsole = $this->document_root.'/app/console';
	$commande = "php $cheminConsole swiftmailer:spool:send --env=prod";
	//$commande = "php $cheminConsole swiftmailer:spool:send";
	$retour = shell_exec($commande);
	//echo  $retour;
	return(0);
}

}
