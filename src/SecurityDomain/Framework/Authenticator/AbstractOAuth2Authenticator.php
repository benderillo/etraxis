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

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Authenticates user against OAuth2 server.
 */
abstract class AbstractOAuth2Authenticator extends AbstractGuardAuthenticator
{
    use TargetPathTrait;

    // ID to distinguish states of several providers.
    protected const PROVIDER_ID = 'abstract';

    protected $router;
    protected $session;

    /**
     * Dependency Injection constructor.
     *
     * @param RouterInterface  $router
     * @param SessionInterface $session
     */
    public function __construct(RouterInterface $router, SessionInterface $session)
    {
        $this->router  = $router;
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        // Do not redirect the user if it's an AJAX request.
        if ($request->isXmlHttpRequest() || $request->getContentType() === 'json') {
            return new JsonResponse('Authentication required.', Response::HTTP_UNAUTHORIZED);
        }

        $exception = $this->session->get(Security::AUTHENTICATION_ERROR);
        $this->session->remove(Security::AUTHENTICATION_ERROR);

        if ($exception !== null) {
            return new Response($exception->getMessage(), Response::HTTP_UNAUTHORIZED);
        }

        $provider = $this->getProvider();

        if ($provider === null) {
            return new Response('Authentication required.', Response::HTTP_UNAUTHORIZED);
        }

        $statevar = 'oauth@' . static::PROVIDER_ID;
        $authUrl  = $provider->getAuthorizationUrl();

        $this->session->set($statevar, $provider->getState());

        return new RedirectResponse($authUrl);
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Request $request)
    {
        return $this->getProvider() !== null
            && $request->query->has('code')
            && $request->query->has('state');
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials(Request $request)
    {
        $code  = $request->query->get('code');
        $state = $request->query->get('state');

        $statevar = 'oauth@' . static::PROVIDER_ID;

        if ($state !== $this->session->get($statevar)) {
            $this->session->remove($statevar);

            return false;
        }

        return [
            'code' => $code,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        try {
            $provider = $this->getProvider();

            $token = $provider->getAccessToken('authorization_code', [
                'code' => $credentials['code'],
            ]);

            $user = $this->getUserFromToken($token);

            if ($user === null) {
                throw new AuthenticationException('Bad credentials.');
            }

            return $user;
        }
        catch (\Exception $e) {
            throw new AuthenticationException($e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $this->session->remove(Security::AUTHENTICATION_ERROR);

        // An URL the user was trying to reach before authentication.
        $targetPath = $this->getTargetPath($this->session, $providerKey);

        // Do not redirect the user if it's an AJAX request.
        return $request->isXmlHttpRequest() || $request->getContentType() === 'json'
            ? null
            : new RedirectResponse($targetPath ?? $this->router->generate('homepage'));
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $this->session->set(Security::AUTHENTICATION_ERROR, $exception);

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsRememberMe()
    {
        return false;
    }

    /**
     * Returns OAuth2 provider.
     *
     * @return null|AbstractProvider
     */
    abstract protected function getProvider(): ?AbstractProvider;

    /**
     * Returns user by the specified OAuth2 token.
     *
     * @param AccessToken $token
     *
     * @return null|UserInterface
     */
    abstract protected function getUserFromToken(AccessToken $token): ?UserInterface;
}
