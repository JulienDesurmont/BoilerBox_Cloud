<?php
namespace Ipc\ProgBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Ipc\ProgBundle\Entity\LocalisationRepository;

class readLocalisationType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
	$builder->add('localisation', 'entity', array(
						'label'		=> 'Localisation',
                                                'class'         => 'IpcProgBundle:Localisation',
                                                'property'      => 'designation',
                                                'query_builder' => function(LocalisationRepository $local){
                                                        return $local->getLocalisationsCourantes();
                                                }))
                ->add('enregistrer','submit', array('label'=>'Valider'));
    }

    public function setDefaultsOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Ipc\ProgBundle\Entity\Localisation',
        ));
    }

    public function getName()
    {
        return 'ReadLocalisation';
    }

}
