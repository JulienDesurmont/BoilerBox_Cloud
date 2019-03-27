<?php
namespace Ipc\ConfigurationBundle\Form\Type\EnteteLive;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class LiveBaseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
	$builder->add('adBase','text',array(   		'trim'          => true))
		->add('labelBase','text',array(        	'trim'          => true,
                                                        'attr'          => array('maxlength'    => '255')))
		->add('familleBase','text',array(         'trim'          => true,
                                                        'attr'          => array('maxlength'    => '255')))
		->add('idLocalisation','hidden')
                ->add('enregistrer','submit');

    }

    public function setDefaultsOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Ipc\ProgBundle\Entity\FormulairesLive\LiveBase',
        ));
    }

    public function getName()
    {
        return 'FormLiveBase';
    }

}
