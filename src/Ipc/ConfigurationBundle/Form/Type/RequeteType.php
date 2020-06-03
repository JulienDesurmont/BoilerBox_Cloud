<?php
# src/Ipc/ConfigurationBundle/Form/Type/RequeteType.php
namespace Ipc\ConfigurationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


class RequeteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('appellation', 'text', array(
                        'label' => 'Appellation de la requête',
                        'trim'  => true,
                        'attr'  => array(
                            'class'         => 'inputText',
                            'placeholder'   => 'Intitulé de la requête'
                        )
                    )
                )
                ->add('description', 'textarea', array(
                    'required'  => false,
                    'attr'      => array(
                        'rows' => '4',
                        'cols' => '80'
                    ),
                    'label'     => 'Description',
                    'trim'      => true
                    )
                )
                ->add('type', 'hidden', array(
                    'mapped' => false
                ))
                ->add('createur', 'hidden')
                ->add('compte', 'choice', array(
                        'label' => 'Compte utilisateur : ',
                        //'placeholder' => "Requête personnelle",
                        'required' => false,
                        'choices' => array(
                            'Admin'         => 'Admin',
                            'Technicien'    => 'Technicien',
                            'Client'        => 'Client'
                        )
                    )
                )
                ->add('Enregistrer', 'submit')
                ->add('Annuler', 'reset');
    }


   /**
    * @param OptionsResolverInterface $resolver
   */
    public function setDefaultsOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array('data_class' => 'Ipc\ConfigurationBundle\Entity\Requete'));
    }


    /**
     * @return string
     */
    public function getName()
    {
        return 'ipc_configurationbundle_requete';
    }

}

