<?php
//src/Ipc/ConfigurationBundle/Service/ServiceConfigurationCloud.php
namespace Ipc\ConfigurationBundle\Services;


class ServiceConfigurationCloud {
	private $fichier_configuration_cloud;
	private $fichier_sites_suivis;

	public function __construct($rootDir) {
		$this->fichier_configuration_cloud =  $rootDir.'/../web/config/configCloud.boiler';
		$this->fichier_sites_suivis =  $rootDir.'/../web/config/sites_suivis.boiler';
	}

	// Fonction qui lit le fichier de configuration [web/config/configCloud.boiler] et retourne les valeurs des différents paramètres dans un tableau associatif
	public function recuperationVariables() 
	{
		$tab_associatif_fichier = null;
		$lignes_du_fichier = file($this->fichier_configuration_cloud);
		foreach($lignes_du_fichier as $n => $ligne)
		{
			$pattern = '/^(.+?)=(.+?)$/';
			if(preg_match($pattern, $ligne, $tableau_ligne)) {
				$tab_associatif_fichier[$tableau_ligne[1]] = $tableau_ligne[2];
			}
		}
		return($tab_associatif_fichier);
	}

	//fonction qui li le fichier de liste des sites suivit et retourne le resultat sous forme de tableau
	    // Fonction qui lit le fichier de configuration [web/config/configCloud.boiler] et retourne les valeurs des différents paramètres dans un tableau associatif
    public function recuperationSitesSuivis()
    {
        $tab_sites_suivis = null;
        $lignes_du_fichier = file($this->fichier_sites_suivis);
        foreach($lignes_du_fichier as $n => $ligne)
        {
        	$tab_sites_suivis[] = $ligne;
        }
        return($tab_sites_suivis);
    }

	
}
