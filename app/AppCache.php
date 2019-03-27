<?php

require_once __DIR__.'/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache;

class AppCache extends HttpCache
{
/*
    protected function getOptions()
    {
	return array(
	    'debug'                  => true,
	    'default_ttl'            => 0,
	    'allow_reload'           => false,
	    'allow_revalidate'       => false
	);
    }
*/
}
