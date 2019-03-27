<?php
//src/Ipc/ProgBundle/Services/Fichiers/ServiceFichiers.php

namespace Ipc\ProgBundle\Services\Fichiers;

class ServiceFichiers {
protected $message;

public function detectUtf8($file) {
    if ( ($file->getClientMimeType() != 'application/vnd.ms-excel') || ($file->getMimeType() != 'text/plain') ){
        $this->message = "Erreur de format de fichier :<br /><br />Merci d'importer un fichier csv";
        return false;
    }
    if (mb_detect_encoding(file_get_contents($file),'UTF-8,ISO-8859-1,ascii') != 'UTF-8') {
        $this->message =  "Erreur d'encodage du fichier :<br /><br />Merci d'encoder le fichier en UTF-8";
        return false;
    }
    return true;
}


public function getMessage() {
	return $this->message;
}

}
