<?php
// src/Ipc/ProgBundle/Services/Password/ServicePassword.php
namespace Ipc\ProgBundle\Services\Password;

// Service de cryptage et decryptage de mot de passes
class ServicePassword {

public function hashPassword($pwd) {
	return sha1('e*?g^*~Ga8'.$pwd.'5!cF;.!Y)?');
}

}
