<?php
# src/Ipc/ConfigurationBundle/Form/Handler/RequeteHandler.php
namespace Ipc\ConfigurationBundle\Form\Handler;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

use Ipc\ConfigurationBundle\Entity\Requete;


class RequeteHandler {
  protected $request;
  protected $form;
  protected $srv_session;
  protected $ent_utilisateur;
  protected $type_requete;

    public function __construct(Form $form, Request $request)
    {
        $this->form = $form;
        $this->request = $request;
    }

    // Soumission du formulaire
   /**
    * Process form
    *
    * return boolean
   */
    public function process(EntityManagerInterface $em, $type, $ent_utilisateur, $srv_session)
    {
        $this->ent_utilisateur  = $ent_utilisateur;
        $this->srv_session      = $srv_session;
        $this->type_requete     = $type;

        if ($this->request->isMethod('POST'))
        {
            $this->form->handleRequest($this->request);
            if ($this->form->isSubmitted())
            {
                $entity = $this->form->getData();
                if ($this->form->isValid())
                {
                    $this->onSuccess($em, $this->form->getData());
                    return true;
                }
            }
        }
        return false;
    }


    public function onSuccess(EntityManagerInterface $em, Requete $ent_requete)
    {
        $ent_requete->setUtilisateur($this->ent_utilisateur);
        $ent_requete->setType($this->type_requete);
        if ($this->type_requete == 'listing')
        {
            $ent_requete->setRequete(json_encode($this->srv_session->get('liste_req')));
        } else {
            $ent_requete->setRequete(json_encode($this->srv_session->get('liste_req_pour_graphique')));
        }

        $ent_requete->setCreateur($this->srv_session->get('label'));
        $em->persist($ent_requete);
        $em->flush();
        if ($this->type_requete == 'listing')
        {
            $this->srv_session->set('listing_requete_selected',$ent_requete->getId());
        } else {
            $this->srv_session->set('graphique_requete_selected',$ent_requete->getId());
        }
    }

}

