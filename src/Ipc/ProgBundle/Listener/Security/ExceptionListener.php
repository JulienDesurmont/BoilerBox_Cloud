<?php
namespace Ipc\ProgBundle\Listener\Security;

//	Ajout pour la réponse personnalisée
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Templating\EngineInterface;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;


class ExceptionListener
{
    private $templateEngine;


    public function __construct(EngineInterface $templateEngine)
    {
        $this->templateEngine = $templateEngine;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
	//	Récupération de l'objet exception depuis l'événement reçu
        $exception = $event->getException();

        // HttpExceptionInterface est un type d'exception spécial qui
        // contient le code statut et les détails de l'entête
	$response = new Response();
        if ($exception instanceof HttpExceptionInterface) {
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->replace($exception->getHeaders());
        } else {
            $response->setStatusCode(500);
        }

        $response = $this->templateEngine->render(
            'TwigBundle:Exception:exception.html.twig',array(
		'status_text' => $event->getException()->getMessage(),
		'status_code' => $event->getException()->getCode(),
		'exception' =>  $exception
	    )
        );
	//$event->setResponse($response);
    }
}
