<?php
namespace Ipc\UserBundle\Redirection;


use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class UserRedirection implements AuthenticationSuccessHandlerInterface
{
    private $router;

    public function __construct(RouterInterface $router){
        $this->router = $router;
    }
    
    public function onAuthenticationSuccess(Request $request, TokenInterface $token){
        $rolesTab = $token->getRoles();
        $redirection = new RedirectResponse($this->router->generate('contact_homepage'));
        if (in_array("ROLE_ADMIN", $rolesTab))
            $redirection = new RedirectResponse($this->router->generate('admin_homepage'));
        return $redirection;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        if ($request->isXmlHttpRequest()) {
            $result = array('success' => false);
            return new Response(json_encode($result));
        } else {
            // Handle non XmlHttp request here
        }
    }
}
