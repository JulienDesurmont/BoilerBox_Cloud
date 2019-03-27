<?php
namespace Ipc\ConfigurationBundle\Form\Type\EnteteLive;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class LiveCombustibleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
	$builder->add('label','text',array(             'label'         => 'Label',
                                                        'trim'          => true,
                                                        'attr'          => array('maxlength'    => '50')))
                ->add('label2','text',array(            'label'         => 'Label',
                                                        'trim'          => true,
                                                        'attr'          => array('maxlength'    => '50')))
		->add('adCombustibleBruleur1','text',array(   	'label'         => "Adresse du mot : Combustible brûleur 1",
                                                		'trim'          => true))
	 	->add('adCombustibleBruleur2','text',array(     'label'         => "Adresse du mot : Combustible brûleur 2",
                                                        	'trim'          => true,
                                                        	'required'      => false))
		->add('idLocalisation','hidden')
                ->add('enregistrer','submit');

    }

    public function setDefaultsOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Ipc\ProgBundle\Entity\FormulairesLive\LiveCombustible',
        ));
    }

    public function getName()
    {
        return 'FormLiveCombustible';
    }

}
