<?php
# src/Ipc/ProgBundle/Form/SiteType.php

namespace Ipc\ProgBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Ipc\ProgBundle\Form\Type\LocalisationType;


class SiteType extends AbstractType {
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder->add('intitule', 'text', array('max_length' => 20,
					  							'label' => 'Intitulé du Site',
					  							'trim' => true))
                ->add('affaire', 'text', array(	'max_length' => 10,
                                         		'label' => 'Code affaire',
                                         		'trim' => true))
                ->add('siteCourant', 'checkbox', array(	'label' => 'Définir comme site courant',
														'required'=> false))
				->add('login_ftp', 'text', array('max_length' => 50,
							 					'label'	=> 'Login Ftp',
												'trim' => true))
				->add('password_ftp', 'repeated', array('type' => 'password',
														'options' => array('required' => true),
														'first_options' => array('label' => 'Mot de passe Ftp'),
														'second_options' => array('label' => 'Confirmation du mot de passe')
							));
		$builder->add('localisations', 'collection', array(	'label' => 'Localisations',
															'type' => new LocalisationType(), 
															'allow_add' => true, 
															'allow_delete' => true));
	}

	public function setDefaultsOptions(OptionsResolverInterface $resolver) {
		$resolver->setDefaults(array('data_class' => 'Ipc\ProgBundle\Entity\Site'));
	}

  	public function getName() {
       	return 'Site';
   	}
}
?>
