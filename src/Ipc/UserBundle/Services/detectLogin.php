<?php
// /src/Ipc/UserBundle/Services/detectLogin.php
namespace Ipc\UserBundle\Services;

use Symfony\Component\HttpFoundation\Response;

class detectLogin {
    // Méthode pour modification de la réponse
    public function enregistreUtilisateur($typeConnexion, $dateConnexion, $token) {
		$urlFichierToken = getenv("DOCUMENT_ROOT").'/logs/login.log';
        $fichierToken = fopen($urlFichierToken, 'a+');
		// Enregistremùent du token dans le fichier
		fputs($fichierToken, "$dateConnexion;Connexion $typeConnexion;$token\n");
		fclose($fichierToken);
		return;	
    }
}
