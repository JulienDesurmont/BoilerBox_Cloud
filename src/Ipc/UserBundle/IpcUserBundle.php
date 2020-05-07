<?php
namespace Ipc\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class IpcUserBundle extends Bundle
{
	public function getParent()
	{
		return 'FOSUserBundle';
	}
}
