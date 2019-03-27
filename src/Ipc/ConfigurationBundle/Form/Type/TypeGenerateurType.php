<?php

namespace Ipc\ConfigurationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TypeGenerateurType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
    */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            	->add('mode',			'text',		array(  'label'         => 'Mode',
						   			'trim'          => true,
						  			'attr'          => array('maxlength'    => '5')))
	     	->add('description',		'text',		array(  'label'         => 'Description',
                                                   			'trim'          => true,
                                                  			'attr'          => array('maxlength'    => '255')))
		->add('modulesEnteteLive',	'entity',	array( 	'class' 	=> 'IpcProgBundle:ModuleEnteteLive',
							 		'property'	=> 'description',
									'label'		=> "Module(s) d'en-tête associé(s)",
									'expanded'	=> true,
									'multiple'	=> true))
		->add('enregistrer','submit')
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Ipc\ProgBundle\Entity\TypeGenerateur'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'ipc_progbundle_typegenerateur';
    }
}
