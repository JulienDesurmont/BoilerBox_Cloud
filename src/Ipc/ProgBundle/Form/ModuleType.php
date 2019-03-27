<?php
namespace Ipc\ProgBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Doctrine\ORM\EntityRepository;

class ModuleType extends AbstractType {
/**
 * @param FormBuilderInterface $builder
 * @param array $options
*/
public function buildForm(FormBuilderInterface $builder, array $options) {
	$builder ->add(
		'genre', 'entity', array(
		'class' 	=> 'IpcProgBundle:Genre',
		'query_builder'	=> function(EntityRepository $er){
			return $er->getTagAll();
		},
		'property'	=> 'intituleGenre')
	);
}

/**
 * @param OptionsResolverInterface $resolver
*/
public function setDefaultOptions(OptionsResolverInterface $resolver) {
	$resolver->setDefaults(array('data_class' => 'Ipc\ProgBundle\Entity\Module'));
}

/**
 * @return string
*/
public function getName() {
	return 'ipc_progbundle_module';
}

}
