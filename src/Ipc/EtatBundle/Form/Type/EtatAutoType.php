<?php
#src/Ipc/EtatBundle/Form/Type/EtatAutoType.php
namespace Ipc\EtatBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class EtatAutoType extends AbstractType {

public function buildForm(FormBuilderInterface $builder, array $options) {
    $builder
        ->add('calcul', 'entity', array('label' => "Choix de l'état",
                                  		'class' => 'Ipc\ProgBundle\Entity\Calcul',
                                    	'property' => 'description'))
		->add('file', 'file', array('label'=>'Fichier csv (encodé en utf-8)'));
}

public function setDefaultsOptions(OptionResolverInterface $resolver) {
    $resolver->setDefaults(array('data_class' => 'Ipc\ProgBundle\Entity\EtatAuto'));
}

public function getName() {
    return 'Etat_automatique';
}

}
