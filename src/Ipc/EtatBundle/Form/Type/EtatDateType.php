<?php
#src/Ipc/EtatBundle/Form/Type/EtatDateType.php
namespace Ipc\EtatBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class EtatDateType extends AbstractType {
	public function buildForm(FormBuilderInterface $builder, array $option) {
		$builder->add('champsDateDebut', 'date', array(
											'widget' => 'single_text',
											'input' => 'datetime',
											'format' => 'dd/MM/yyyy',
                                            'attr' => array('class' => 'champsDate')
											))
				->add('champsDateFin', 'date', array(
                                            'widget' => 'single_text',
                                            'input' => 'datetime',
                                            'format' => 'dd/MM/yyyy',
											'attr' => array('class' => 'champsDate')
                                            ))
				->add('id', 'integer', array(
											'attr' => array('class' => 'cacher')
											));
	}	

	public function getName() {
		return 'ipcEtatBundle_dateType';
	}

	public function getDefaultOptions(array $option) {
		return array('data_class' => 'Ipc\ProgBundle\Entity\EtatDate');
	}
}
