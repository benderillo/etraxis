<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2018 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <http://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace eTraxis\SecurityDomain\Framework\Authenticator;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Authenticates a user via eTraxis database.
 */
class DatabaseAuthenticator extends AbstractGuardAuthenticator
{
    use TargetPathTrait;

    protected $router;
    protected $session;
    protected $translator;
    protected $encoder;

    /**
     * Dependency Injection constructor.
     *
     * @param RouterInterface              $router
     * @param SessionInterface             $session
     * @param TranslatorInterface          $translator
     * @param UserPasswordEncoderInterface $encoder
     */
    public function __construct(
        RouterInterface              $router,
        SessionInterface             $session,
        TranslatorInterface          $translator,
        UserPasswordEncoderInterface $encoder
    )
    {
        $this->router     = $router;
        $this->session    = $session;
        $this->translator = $translator;
        $this->encoder    = $encoder;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Request $request)
    {
        return $request->request->has('_username') && $request->request->has('_password');
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials(Request $request)
    {
        $this->session->set(Security::LAST_USERNAME, $request->request->get('_username'));

        return [
            'username' => $request->request->get('_username'),
            'password' => $request->request->get('_password'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        try {
            return $userProvider->loadUserByUsername($credentials['username']);
        }
        catch (UsernameNotFoundException $e) {
            throw new AuthenticationException('Bad credentials.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        if (!$this->encoder->isPasswordValid($user, $credentials['password'])) {
            throw new AuthenticationException('Bad credentials.');
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $this->session->remove(Security::AUTHENTICATION_ERROR);
        $this->session->remove(Security::LAST_USERNAME);

        // An URL the user was trying to reach before authentication.
        $targetPath = $this->getTargetPath($this->session, $providerKey);

        // Do not redirect the user if it's an AJAX request.
        return $request->isXmlHttpRequest() || $request->getContentType() === 'json'
            ? new JsonResponse()
            : new RedirectResponse($targetPath ?? $this->router->generate('homepage'));
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $this->session->set(Security::AUTHENTICATION_ERROR, $exception);

        return $this->start($request, $exception);
    }

    /**
     * {@inheritdoc}
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        // Do not redirect the user if it's an AJAX request.
        if ($request->isXmlHttpRequest() || $request->getContentType() === 'json') {

            $exception = $this->session->get(Security::AUTHENTICATION_ERROR);

            $message = $exception instanceof AuthenticationException
                ? $exception->getMessage()
                : $this->translator->trans('Authentication required.');

            return new JsonResponse($message, JsonResponse::HTTP_UNAUTHORIZED);
        }

        return new RedirectResponse($this->router->generate('login'));
    }

    /**
     * {@inheritdoc}
     */
    public function supportsRememberMe()
    {
        return true;
    }
}
