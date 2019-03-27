<?php
namespace Ipc\ProgBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class moduleEnteteLiveType extends AbstractType {

/**
 * @param FormBuilderInterface $builder
 * @param array $options
*/
public function buildForm(FormBuilderInterface $builder, array $options) {
	$builder
		->add('designation', 'text', array(
										'label' => 'Designation',
										'trim' => true,
										'attr' => array('maxlength' => '255'))
		)
		->add('description', 'text', array(
										'label' => 'Description',
										'trim' => true,
										'attr' => array('maxlength' => '255'))
		)
		->add('enregistrer', 'submit');
}
    
/**
 * @param OptionsResolverInterface $resolver
*/
public function setDefaultOptions(OptionsResolverInterface $resolver) {
	$resolver->setDefaults(array('data_class' => 'Ipc\ProgBundle\Entity\ModuleEnteteLive'));
}

/**
 * @return string
*/
public function getName() {
	return 'ipc_progbundle_moduleentetelive';
}

}
