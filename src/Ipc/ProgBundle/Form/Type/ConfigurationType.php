<?php
namespace Ipc\ProgBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ConfigurationType extends AbstractType {

/**
 * @param FormBuilderInterface $builder
 * @param array $options
*/
public function buildForm(FormBuilderInterface $builder, array $options) {
	$builder
		->add('parametre', 'text')
		->add('valeur', 'text')
		->add('designation','text')
		->add('parametreAdmin', 'checkbox', array( 'label' => 'Paramètre administrateur',)
                                                   'required'=> false))
		->add('save',   'submit');
}
    
/**
 * @param OptionsResolverInterface $resolver
*/
public function setDefaultOptions(OptionsResolverInterface $resolver) {
	$resolver->setDefaults(array('data_class' => 'Ipc\ProgBundle\Entity\Configuration'));
}

/**
 * @return string
*/
public function getName() {
	return 'ipc_progbundle_configuration';
}

}
