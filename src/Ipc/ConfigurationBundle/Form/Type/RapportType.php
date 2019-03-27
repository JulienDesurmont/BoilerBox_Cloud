<?php
namespace Ipc\ConfigurationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Ipc\ProgBundle\Entity\LocalisationRepository;
use Ipc\ConfigurationBundle\Form\Type\FichierRapportType;

class RapportType extends AbstractType {

public function buildForm(FormBuilderInterface $builder, array $options) {
	//$maxSize = ini_get('upload_max_filesize');
	$builder->add('titre','text',array(	'label' => "label.rapport.titre",
					   					'trim'	=> true,
										'attr'	=> array('maxlength'    => '150')
								)
		)
		->add('dateRapport','datetime',array(
										'label' => "label.rapport.horodatage",
										'widget' =>'single_text',
										'format' =>'dd-MM-yyyy HH:mm',
    									'data' => new \Datetime(),
										'invalid_message' => 'Date incorrectement formatée'
										)
		)
		->add('rapport','textarea',array('label'	=> 'label.rapport.zone_rapport'))
		->add('site','choice',array(	'choices' => array(
										true => 'label.oui',
										false => 'label.non',
						),
						'empty_value' 	=> false,
						'expanded' 	=> true,
						'label'		=>'label.rapport.equipement',
						'mapped'	=>false,
						'data'		=>true,
						'required'	=>false))
		->add('localisation', 'entity', array(
										'class' 	=> 'IpcProgBundle:Localisation',
										'property' 	=> 'designation',
										'query_builder'	=> function(LocalisationRepository $local){
			    							return $local->getLocalisationsCourantes();
										}
										)
		)
		->add('nomTechnicien','text',array('label' 	=> 'label.rapport.intervenant'))
		->add('fichiersrapport','collection',array(
										'label'		=> 'label.rapport.fichiers_inclus',
										'type' 		=> new FichierRapportType(),
										'allow_add' 	=> true,
										'allow_delete' 	=> true,
										'options' 	=> array('data_class' => 'Ipc\ProgBundle\Entity\FichierRapport')
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
