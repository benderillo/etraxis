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

use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\Tests\ReflectionTrait;
use eTraxis\Tests\WebTestCase;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @coversDefaultClass \eTraxis\SecurityDomain\Framework\Authenticator\AbstractOAuth2Authenticator
 */
class AbstractOAuth2AuthenticatorTest extends WebTestCase
{
    use ReflectionTrait;

    /** @var RouterInterface */
    protected $router;

    /** @var SessionInterface */
    protected $session;

    protected function setUp()
    {
        parent::setUp();

        $this->router  = $this->client->getContainer()->get('router');
        $this->session = $this->client->getContainer()->get('session');
    }

    /**
     * @covers ::start
     */
    public function testStart()
    {
        $provider = $this->createMock(AbstractProvider::class);
        $provider
            ->method('getAuthorizationUrl')
            ->willReturn('https://oauth.example.com/authorize');

        $authenticator = $this->getAuthenticator($provider);

        $request = new Request();

        $response = $authenticator->start($request);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('https://oauth.example.com/authorize', $response->headers->get('location'));
    }

    /**
     * @covers ::start
     */
    public function testStartAjax()
    {
        $provider = $this->createMock(AbstractProvider::class);
        $provider
            ->method('getAuthorizationUrl')
            ->willReturn('https://oauth.example.com/authorize');

        $authenticator = $this->getAuthenticator($provider);

        $request = new Request();
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = $authenticator->start($request);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame('Authentication required.', json_decode($response->getContent(), true));
    }

    /**
     * @covers ::start
     */
    public function testStartException()
    {
        $provider = $this->createMock(AbstractProvider::class);
        $provider
            ->method('getAuthorizationUrl')
            ->willReturn('https://oauth.example.com/authorize');

        $authenticator = $this->getAuthenticator($provider);

        $this->session->set(Security::AUTHENTICATION_ERROR, new \Exception('Security exception.'));
        $request = new Request();

        $response = $authenticator->start($request);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame('Security exception.', $response->getContent());
    }

    /**
     * @covers ::start
     */
    public function testStartNoProvider()
    {
        $authenticator = $this->getAuthenticator(null);

        $request = new Request();

        $response = $authenticator->start($request);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame('Authentication required.', $response->getContent());
    }

    /**
     * @covers ::supports
     */
    public function testSupportsSuccess()
    {
        $authenticator = $this->getAuthenticator($this->createMock(AbstractProvider::class));

        $request = new Request([
            'code'  => 'valid-code',
            'state' => 'secret',
        ]);

        self::assertTrue($authenticator->supports($request));
    }

    /**
     * @covers ::supports
     */
    public function testSupportsMissing()
    {
        $authenticator = $this->getAuthenticator($this->createMock(AbstractProvider::class));

        $request = new Request();

        self::assertFalse($authenticator->supports($request));
    }

    /**
     * @covers ::supports
     */
    public function testSupportsNoProvider()
    {
        $authenticator = $this->getAuthenticator(null);

        $request = new Request([
            'code'  => 'valid-code',
            'state' => 'secret',
        ]);

        self::assertFalse($authenticator->supports($request));
    }

    /**
     * @covers ::getCredentials
     */
    public function testGetCredentials()
    {
        $expected = [
            'code' => 'valid-code',
        ];

        $authenticator = $this->getAuthenticator($this->createMock(AbstractProvider::class));

        $this->session->set('oauth@abstract', 'secret');

        $request = new Request([
            'code'  => 'valid-code',
            'state' => 'secret',
        ]);

        self::assertSame($expected, $authenticator->getCredentials($request));
    }

    /**
     * @covers ::getCredentials
     */
    public function testGetCredentialsWrongState()
    {
        $authenticator = $this->getAuthenticator($this->createMock(AbstractProvider::class));

        $this->session->set('oauth@abstract', 'secret');

        $request = new Request([
            'code'  => 'valid-code',
            'state' => 'wrong',
        ]);

        self::assertFalse($authenticator->getCredentials($request));
    }

    /**
     * @covers ::getUser
     */
    public function testGetUser()
    {
        $credentials = [
            'code' => 'valid-code',
        ];

        /** @var UserProviderInterface $userProvider */
        $userProvider = $this->createMock(UserProviderInterface::class);

        $user = new User();
        $this->setProperty($user, 'id', 123);

        $provider = $this->createMock(AbstractProvider::class);
        $provider
            ->method('getAuthorizationUrl')
            ->willReturn('https://oauth.example.com/authorize');
        $provider
            ->method('getAccessToken')
            ->willReturn($this->createMock(AccessToken::class));

        $authenticator = $this->getAuthenticator($provider, $user);

        self::assertSame($user, $authenticator->getUser($credentials, $userProvider));
    }

    /**
     * @covers ::getUser
     */
    public function testGetUserException()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Bad credentials.');

        $credentials = [
            'code' => 'valid-code',
        ];

        /** @var UserProviderInterface $userProvider */
        $userProvider = $this->createMock(UserProviderInterface::class);

        $provider = $this->createMock(AbstractProvider::class);
        $provider
            ->method('getAuthorizationUrl')
            ->willReturn('https://oauth.example.com/authorize');
        $provider
            ->method('getAccessToken')
            ->willReturn($this->createMock(AccessToken::class));

        $authenticator = $this->getAuthenticator($provider);

        $authenticator->getUser($credentials, $userProvider);
    }

    /**
     * @covers ::checkCredentials
     */
    public function testCheckCredentials()
    {
        $authenticator = $this->getAuthenticator($this->createMock(AbstractProvider::class));

        self::assertTrue($authenticator->checkCredentials([], new User()));
    }

    /**
     * @covers ::onAuthenticationSuccess
     */
    public function testOnAuthenticationSuccess()
    {
        $authenticator = $this->getAuthenticator($this->createMock(AbstractProvider::class));

        $token = $authenticator->createAuthenticatedToken(new User(), 'main');

        $request  = new Request();
        $response = $authenticator->onAuthenticationSuccess($request, $token, 'main');

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/', $response->headers->get('Location'));
    }

    /**
     * @covers ::onAuthenticationSuccess
     */
    public function testOnAuthenticationSuccessAjax()
    {
        $authenticator = $this->getAuthenticator($this->createMock(AbstractProvider::class));

        $token = $authenticator->createAuthenticatedToken(new User(), 'main');

        $request = new Request();
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = $authenticator->onAuthenticationSuccess($request, $token, 'main');

        self::assertNull($response);
    }

    /**
     * @covers ::onAuthenticationFailure
     */
    public function testOnAuthenticationFailure()
    {
        $authenticator = $this->getAuthenticator($this->createMock(AbstractProvider::class));

        $request   = new Request();
        $exception = new AuthenticationException('Bad credentials.');

        self::assertFalse($this->session->has(Security::AUTHENTICATION_ERROR));

        $response = $authenticator->onAuthenticationFailure($request, $exception);

        self::assertNull($response);
        self::assertTrue($this->session->has(Security::AUTHENTICATION_ERROR));
    }

    /**
     * @covers ::supportsRememberMe
     */
    public function testSupportsRememberMe()
    {
        $authenticator = $this->getAuthenticator($this->createMock(AbstractProvider::class));

        self::assertFalse($authenticator->supportsRememberMe());
    }

    protected function getAuthenticator($provider, $user = null): AbstractOAuth2Authenticator
    {
        return new class($this->router, $this->session, $provider, $user) extends AbstractOAuth2Authenticator {
            protected $provider;
            protected $user;

            public function __construct(RouterInterface $router, SessionInterface $session, ?AbstractProvider $provider, ?UserInterface $user)
            {
                parent::__construct($router, $session);

                $this->provider = $provider;
                $this->user     = $user;
            }

            protected function getProvider(): ?AbstractProvider
            {
                return $this->provider;
            }

            protected function getScope(): array
            {
                return [];
            }

            protected function getUserFromToken(AccessToken $token): ?UserInterface
            {
                return $this->user;
            }
        };
    }
}
