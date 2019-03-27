<?php
namespace Ipc\ConfigurationBundle\Form\Type\EnteteLive ;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class LiveBruleurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
	$builder->add('label','text',array(             'label'         => 'Label',
                                                        'trim'          => true,
                                                        'attr'          => array('maxlength'    => '50')))
	  	->add('label2','text',array(            'label'         => 'Label',
                                                        'trim'          => true,
                                                        'attr'          => array('maxlength'    => '50')))
		->add('adPrFlamme1','text',array(   	'label'         => "Adresse du mot : Présence flamme",
                                                	'trim'          => true))
	 	->add('adPrFlamme2','text',array(       'label'         => "Adresse du mot : Présence flamme",
                                                        'trim'          => true,
                                                        'required'      => false))
                ->add('adChBruleur1','text',array(   	'label'         => "Adresse du mot : Charge du brûleur",
                                                	'trim'          => true))
                ->add('adChBruleur2','text',array(      'label'         => "Adresse du mot : Charge du brûleur",
                                                        'trim'          => true,
                                                        'required'      => false))
		->add('idLocalisation','hidden')
                ->add('enregistrer','submit');

    }

    public function setDefaultsOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Ipc\ProgBundle\Entity\FormulairesLive\LiveBruleur',
        ));
    }

    public function getName()
    {
        return 'FormLiveBruleur';
    }

}
