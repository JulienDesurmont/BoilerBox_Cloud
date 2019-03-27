<?php
namespace Ipc\ConfigurationBundle\Form\Type\EnteteLive;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class LiveEtatGenerateurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
	$builder->add('label','text',array( 		'label'         => 'Label',
							'trim'          => true,
							'attr'          => array('maxlength'    => '50')))
		->add('adNbEvenements','text',array(   	'label'         => "Adresse du mot : Nombre d'événements",
                                                	'trim'          => true))
                ->add('adNbAlarmes','text',array(   	'label'         => "Adresse du mot : Nombre d'alarmes",
                                                	'trim'          => true))
		->add('adNbDefauts','text',array(       'label'         => "Adresse du mot : Nombre de défauts",
                                                        'trim'          => true))
		->add('idLocalisation','hidden')
                ->add('enregistrer','submit');

    }

    public function setDefaultsOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Ipc\ProgBundle\Entity\FormulairesLive\LiveEtatGenerateur',
        ));
    }

    public function getName()
    {
        return 'FormLiveEtatGenerateur';
    }

}
