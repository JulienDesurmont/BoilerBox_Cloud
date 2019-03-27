<?php
namespace Ipc\ConfigurationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Ipc\ProgBundle\Entity\LocalisationRepository;
use Ipc\ConfigurationBundle\Form\Type\FichierRapportType;

class ModifyRapportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
    $maxSize = ini_get('upload_max_filesize');

	$builder
		->add('rapport','textarea',array(	
				'label'		=> 'Rapport',
				'attr'		=> array(
					'rows'	=> 20,
					'cols'	=> 105
				)
			)
		)
		->add('fichiersrapport', 'collection', array(
				'label'			=> "Fichier(s) associé(s) au rapport (max $maxSize)",
				'type' 			=> new FichierRapportType(),
				'allow_add' 	=> true,
				'allow_delete' 	=> true,
				'options' 		=> array('data_class' => 'Ipc\ProgBundle\Entity\FichierRapport')
		   )
		)
		->add('enregistrer','submit');
    }

    public function setDefaultsOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Ipc\ProgBundle\Entity\Rapport',
        ));
    }

    public function getName() {
        return 'Rapport';
    }

}
