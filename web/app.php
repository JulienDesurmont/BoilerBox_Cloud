<?php

use Symfony\Component\ClassLoader\ApcClassLoader;
use Symfony\Component\HttpFoundation\Request;


/*
# Décommenter pour mettre le site en maintenance
if (isset($_SERVER['HTTP_CLIENT_IP'])
    || isset($_SERVER['HTTP_X_FORWARDED_FOR'])
    || !in_array(@$_SERVER['REMOTE_ADDR'], array(
    '127.0.0.1',
    '10.231.18.163'
    ))
) {
    //print_r(@$_SERVER['REMOTE_ADDR']);
    header('HTTP/1.0 403 Forbidden');
    echo "Site en maintenance";die();
}
*/

/*
# Décommenter pour mettre le live maintenance
$pattern_supervision = '/supervision/';
if(preg_match($pattern_supervision, $_SERVER['PHP_SELF'])) {
    if (isset($_SERVER['HTTP_CLIENT_IP'])
        || isset($_SERVER['HTTP_X_FORWARDED_FOR'])
        || !in_array(@$_SERVER['REMOTE_ADDR'], array(
        '127.0.0.1',
        '10.231.18.163',
        '192.168.0.105'
        ))
    ) {
        //print_r(@$_SERVER['REMOTE_ADDR']);
        header('HTTP/1.0 403 Forbidden');
        echo "Site live en maintenance";die();
    }
}
*/




$loader = require_once __DIR__.'/../app/bootstrap.php.cache';

// Use APC for autoloading to improve performance.
// Change 'sf2' to a unique prefix in order to prevent cache key conflicts
// with other applications also using APC.
//apcLoader = new ApcClassLoader('ipcWeb', $loader);
//	//$loader = new ApcClassLoader('ipcWeb', $loader);
//$loader->unregister();
//	//$loader->register(true);
//$apcLoader->register(true);

require_once __DIR__.'/../app/AppKernel.php';
//require_once __DIR__.'/../app/AppCache.php';

$kernel = new AppKernel('prod', false);
//$kernel = new AppKernel('prod', true);
$kernel->loadClassCache();
//$kernel = new AppCache($kernel);

// When using the HttpCache, you need to call the method in your front controller instead of relying on the configuration parameter
//Request::enableHttpMethodParameterOverride();


$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
