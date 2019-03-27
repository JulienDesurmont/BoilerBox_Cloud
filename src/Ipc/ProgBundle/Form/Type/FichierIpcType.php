<?php
namespace Ipc\ProgBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FichierIpcType extends AbstractType {

/**
 * @param FormBuilderInterface $builder
 * @param array $options
*/
public function buildForm(FormBuilderInterface $builder, array $options) {
	$builder ->add('file','file', array('label'=>'Choix du fichier'));
}
    
/**
 * @param OptionsResolverInterface $resolver
*/
public function setDefaultOptions(OptionsResolverInterface $resolver) {
	$resolver->setDefaults(array('data_class' => 'Ipc\ProgBundle\Entity\FichierIpc'));
}

/**
 * @return string
*/
public function getName() {
	return 'ipc_progbundle_fichieripc';
}

}
