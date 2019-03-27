<?php
namespace Ipc\ProgBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Doctrine\ORM\EntityRepository;

class DonneeType extends AbstractType {
/**
 * @param FormBuilderInterface $builder
 * @param array $options
*/
public function buildForm(FormBuilderInterface $builder, array $options) {
	$builder
		->add('horodatage', 'date')
		->add('module', new ModuleType())
		->add('save',	'submit');
}
    
/**
 * @param OptionsResolverInterface $resolver
*/
public function setDefaultOptions(OptionsResolverInterface $resolver) {
	$resolver->setDefaults(array('data_class' => 'Ipc\ProgBundle\Entity\Donnee'));
}

/**
 * @return string
*/
public function getName() {
	return 'ipc_progbundle_donnee';
}

}
