<?php

namespace Ipc\ConfigurationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FichierRapport2Type extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom','hidden',array('label'=>'Nom du fichier'))
	    ->add('idRapport','hidden',array('label'=>'Identifiant du rapport associé','mapped'=>false))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Ipc\ProgBundle\Entity\FichierRapport'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'ipc_configurationbundle_fichierrapport';
    }
}
