<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function __construct($environment, $debug)
    {
        date_default_timezone_set('Europe/Paris');
        parent::__construct($environment, $debug);
    }

    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new Ipc\ProgBundle\IpcProgBundle(),
            new Ipc\ListingBundle\IpcListingBundle(),
            new Ipc\GraphiqueBundle\IpcGraphiqueBundle(),
            new Ipc\UserBundle\IpcUserBundle(),
	    	new FOS\UserBundle\FOSUserBundle(),
            new Ipc\ConfigurationBundle\IpcConfigurationBundle(),
            new Ipc\EtatBundle\IpcEtatBundle(),
            new Ipc\SupervisionBundle\IpcSupervisionBundle(),
            new Ipc\RapportsBundle\IpcRapportsBundle(),
            new Ipc\OutilsBundle\IpcOutilsBundle(),
            new Ipc\AnalyseBundle\IpcAnalyseBundle(),
			//new Lci\BoilerBoxBundle\LciBoilerBoxBundle()
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
            //$bundles[] = new Alex\DoctrineExtraBundle\AlexDoctrineExtraBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
    }
}
