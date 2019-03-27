<?php
//src/Ipc/UserBundle/Form/Type;

namespace Ipc\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$builder->add('roles','collection', array(
			'type' => 'choice',
			'options' => array(
				'choices' => array(
					'ROLE_USER' 	   => 'Client',
					'ROLE_ADMIN' 	   => 'Admin',
					'ROLE_SUPER_ADMIN' => 'SuperAdmin',
					'ROLE_SUPERVISEUR' => 'Superviseur',
					'ROLE_TECHNICIEN'  => 'Technicien Lci'
				)
			)
		));
    }

	public function getParent() 
	{
		return 'fos_user_registration';
	}

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Ipc\UserBundle\Entity\User',
            'intention'  => 'registration',
        ));
    }

    public function getName()
    {
        return 'ipc_user_registration';
    }
}
