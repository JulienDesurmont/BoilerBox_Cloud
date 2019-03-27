<?php
// src/Ipc/ProgBundle/Services/Traduction/ServiceTraduction.php
namespace Ipc\ProgBundle\Services\Traduction;

class ServiceTraduction
{
    /**
     * @var array
     */
    private $variables = array();

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var array
     */
    private $translations;


	public function __construct($translator) {
        $this->translator = $translator;
        $this->translations = array();
		$this->ajoutJsTraduction();
	}

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->variables[$key];
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return Traduction
     */
    public function __set($key, $value)
    {
        $this->variables[$key] = $value;

        return $this;
    }

    /**
     * @param string $key
     *
     * @return boolean
     */
    public function __isset($key)
    {
        return isset($this->variables[$key]);
    }

    /**
     * @param string $key
     */
    public function __unset($key)
    {
        unset($this->variables[$key]);
    }

    /**
     * @return array
     */
    public function getVariables()
    {
        return $this->variables;
    }



    /**
     * Ajoute une clé => traduction utilisant Translator.
     * Le nom de la méthode '->trans()' sera détécté
     * par l'extracteur de clé de traduction.
     *
     * @param string $key
     *
     * @return Traduction
     *
     * @throws \Exception si Translator n'a pas été injecté
     */
    public function trans($key)
    {
        if (null === $this->translator) {
             throw new \Exception('Translator must be enabled to use trans()');
        }
        $this->translations[$key] = $this->translator->trans(/** @Ignore */ $key);
        return $this;
    }

    /**
     * @return array
     */
    public function getTranslations()
    {
        return $this->translations;
    }


    /**
     * Ajoute une clé => traduction utilisant Translator.
     * Le nom de la méthode '->trans()' sera détécté
     * par l'extracteur de clé de traduction.
     *
     * @param string $key
     *
     * @return 0
     *
     * @throws \Exception si Translator n'a pas été injecté
     */
    protected function traduire($key)
    {
        if (null === $this->translator) {
             throw new \Exception('Translator must be enabled to use trans()');
        }
        $this->translations[$key] = $this->translator->trans(/** @Ignore */ $key);
        return 0;
    }


	// Traduit le mot donné en paramètre
	public function getTraduction($mot_a_traduire) {
		return $this->translator->trans($mot_a_traduire);
	}



	public function addPrepareMotsATraduire()
	{
        // Listes de mots à passer au service de traduction javascript
        $this->traduire('label.localisation');
        $this->traduire('label.code_message');
        $this->traduire('label.designation');
        $this->traduire('label.action');
		$this->traduire('label.titre.legende');
        $this->traduire('label.horodatage');
        $this->traduire('label.ajout_listing');
        $this->traduire('label.ajout_courbe');
        $this->traduire('bouton.ajouter_requete');
        $this->traduire('bouton.supprimer_requete');
        $this->traduire('bouton.editer_requete');
		$this->traduire('label.chargement');
		$this->traduire('lien.ajout_fichier');
		$this->traduire('lien.supprimer');
		$this->traduire('info.periode.none');
		$this->traduire('select.requetes_client.titre.listing');
		$this->traduire('select.requetes_client.titre.graphique');
		$this->traduire('select.requetes_perso.titre.listing');
        $this->traduire('select.requetes_perso.titre.graphique');
		$this->traduire('label.sur');
		$this->traduire('periode.compression.moyenne');
		$this->traduire('periode.compression.maximum');
        $this->traduire('periode.compression.minimum');
        $this->traduire('periode.compression.complete');
		$this->traduire('periode.duree.seconde');
		$this->traduire('periode.duree.minute');
        $this->traduire('periode.duree.heure');
		$this->traduire('periode.duree.jour');
        $this->traduire('periode.duree.mois');
        $this->traduire('live.lien.etat');
        $this->traduire('live.lien.graphique');
        $this->traduire('live.label.serveur_actif');
        $this->traduire('live.label.serveur_inactif');
        $this->traduire('live.label.serveur_occupe');
		return $this;
	}

	private function ajoutJsTraduction()
    {
        // Listes de mots à passer au service de traduction javascript
        $this->traduire('label.localisation');
        $this->traduire('label.code_message');
        $this->traduire('label.designation');
        $this->traduire('label.action');
        $this->traduire('label.titre.legende');
        $this->traduire('label.horodatage');
        $this->traduire('label.ajout_listing');
        $this->traduire('label.ajout_courbe');
        $this->traduire('bouton.ajouter_requete');
        $this->traduire('bouton.supprimer_requete');
        $this->traduire('bouton.editer_requete');
        $this->traduire('label.chargement');
        $this->traduire('lien.ajout_fichier');
        $this->traduire('lien.supprimer');
        $this->traduire('info.periode.none');
        $this->traduire('select.requetes_client.titre.listing');
        $this->traduire('select.requetes_client.titre.graphique');
        $this->traduire('select.requetes_perso.titre.listing');
        $this->traduire('select.requetes_perso.titre.graphique');
        $this->traduire('label.sur');
        $this->traduire('periode.compression.moyenne');
        $this->traduire('periode.compression.maximum');
        $this->traduire('periode.compression.minimum');
        $this->traduire('periode.compression.complete');
        $this->traduire('periode.duree.seconde');
        $this->traduire('periode.duree.minute');
        $this->traduire('periode.duree.heure');
        $this->traduire('periode.duree.jour');
        $this->traduire('periode.duree.mois');
        $this->traduire('live.lien.etat');
        $this->traduire('live.lien.graphique');
        $this->traduire('live.label.serveur_actif');
        $this->traduire('live.label.serveur_inactif');
        $this->traduire('live.label.serveur_occupe');
        return 0;
    }


    public function addPrepareMotsLiveATraduire()
    {
        // Listes de mots à passer au service de traduction javascript
        $this->traduire('live.lien.etat');
		$this->traduire('live.lien.graphique');
		$this->traduire('live.label.serveur_actif');
        $this->traduire('live.label.serveur_inactif');
        $this->traduire('live.label.serveur_occupe');
        return $this;
    }

}
