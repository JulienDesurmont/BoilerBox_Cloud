<?php
namespace Ipc\ConfigurationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Ipc\ProgBundle\Entity\LocalisationRepository;
use Ipc\ProgBundle\Entity\CategorieFamilleLiveRepository;
use Ipc\ProgBundle\Entity\TypeFamilleLiveRepository;
use Ipc\ProgBundle\Entity\IconeRepository;

class ModifDonneeLiveType extends AbstractType {

public function buildForm(FormBuilderInterface $builder, array $options) {
    $builder->add('id','integer',array(			'label'         => 'Identifiant'))
        	->add('suppression','checkbox',array(   
												'label'    => 'Suppression',
                            					'required' => false,
                            					'mapped'   => false))
			->add('famille','text',array(       'label'         => 'Famille',
                                                'trim'          => true,
                                                'attr'          => array(   'maxlength' => '50')))
            ->add('label','text',array(         'label'         => 'Label',
                                                'trim'          => true,
                                                'attr'          => array(   'maxlength' => '255')))
            ->add('type','choice',array(        'label'         => 'Type',
                                                'choices'       => array(   'BOOL'      => 'BOOL',
                                                                            'INT'       => 'INT',
                                                                            'REAL'      => 'REAL')))
            ->add('placement','choice',array(   'label'         => 'Placement',
                                                'choices'       => array(   'Corps'     => 'Corps',
                                                                            'EnTete1'   => 'En-tête 1',
                                                                            'EnTete2'   => 'En-tête 2',
                                                                            'EnTete3'   => 'En-tête 3',
                                                                            'EnTete4'   => 'En-tête 4',
                                                                            'EnTete5'   => 'En-tête 5',
                                                                            'EnTete6'   => 'En-tête 6',
                                                                            'EnTete7'   => 'En-tête 7',
                                                                            'EnTete8'   => 'En-tête 8')))
            ->add('adresse','text',array(       'label'         => 'Adresse du mot (Format booléen : **X**)',
                                                'trim'          => true,
                                                'attr'          => array(   'maxlength' => '255')))
            ->add('unite','text',array(         'label'         => 'Unité',
                                                'trim'          => true,
                                                'attr'          => array(   'maxlength' => '20'),
                                                'required'      => false))
            ->add('couleur','text',array(       'label'         => 'Couleur',
                                                'trim'          => true,
                                                'attr'          => array(   'maxlength' => '20')))
            ->add('valeurEntreeVrai','integer',array(
                                                'label'         => 'Valeur en entrée OK',
                                                'trim'          => true,
                                                'required'      => false))
            ->add('valeurSortieVrai','text',array(
                                                'label'         => 'Valeur en sortie OK',
                                                'trim'          => true,
                                                'attr'          => array(   'maxlength' => '255'),
                                                'required'      => false))
            ->add('valeurSortieFaux','text',array(
                                                'label'         => 'Valeur en sortie NOK',
                                                'trim'          => true,
                                                'attr'          => array('maxlength'    => '255'),
                                                'required'      => false))
            ->add('localisation', 'entity', array(
                                                'class'         => 'IpcProgBundle:Localisation',
                                                'property'      => 'designation',
                                                'query_builder' => function(LocalisationRepository $local){
                                                                            return $local->getLocalisationsCourantes();
                                                }))
            ->add('categorie', 'entity', array( 'label'         => 'Catégorie',
                                                'class'         => 'IpcProgBundle:CategorieFamilleLive',
                                                'property'      => 'designation',
                                                'query_builder' => function(CategorieFamilleLiveRepository $cr) {
                                                                            return $cr  ->createQueryBuilder('c')
                                                                                        ->orderBy('c.designation', 'ASC');
                                                }))
			->add('icone', 'entity', array( 	'label'   		=> 'Icone',
                                                'class'         => 'IpcProgBundle:Icone',
												'property'      => 'iconeForSelect',
                                                'query_builder' => function(IconeRepository $ir) {
                                                    						return $ir	->createQueryBuilder('i')
                                                    									->orderBy('i.designation', 'ASC');
                                                }))
			->add('enregistrer','submit');
    }

    public function setDefaultsOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Ipc\ProgBundle\Entity\DonneeLive',
        ));
    }

    public function getName()
    {
        return 'DonneeLive';
    }

}
