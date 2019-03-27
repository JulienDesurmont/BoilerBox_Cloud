<?php
namespace Ipc\ConfigurationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Ipc\ProgBundle\Entity\LocalisationRepository;

class FichierRapportType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
    */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('file','file', array('label'=>' '));
    }


    public function setDefaultsOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Ipc\ProgBundleBundle\Entity\FichierRapport',
        ));
    }

    public function getName()
    {
        return 'FichierRapport';
    }


}

