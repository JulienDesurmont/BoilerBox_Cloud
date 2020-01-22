<?php
#src/Ipc/ProgBundle/Form/Type/LocalisationType.php
namespace Ipc\ProgBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class LocalisationType extends AbstractType {

public function buildForm(FormBuilderInterface $builder, array $options) { 
	$builder 
		->add('numeroLocalisation', 'text', array(
											'label' => 'Numéro de la localisation')
		)
		->add('adresseIp', 'text', array(
											'label' => 'Adresse IP')
		)
		->add('designation', 'text', array(
											'label' => 'Désignation')
		)
		->add('adresseModbus', 'text', array(
											'label' => 'Adresse modbus pour la clotûre des fichiers')
		)
		->add('login_ftp', 'text',	array(
											'max_length' => 50,
											'label' => 'Login Ftp',
											'trim' => true)
		)
		->add('password_ftp', 'repeated', array(
											'type' => 'password',
											'options' => array('required' => true),
											'first_options'	=> array('label' => 'Mot de passe Ftp'),
											'second_options' => array('label' => 'Confirmation du mot de passe'))
		)
		->add('typeGenerateur',	'entity', array(
											'label' => 'Type de générateur',
											'class'	=> 'Ipc\ProgBundle\Entity\TypeGenerateur',
											'property' => 'description')
		);
}

public function setDefaultsOptions(OptionResolverInterface $resolver) {
	$resolver->setDefaults(array('data_class' => 'Ipc\ProgBundle\Entity\Localisation'));
}

public function getName() {
	return 'Localisation';
}

}
