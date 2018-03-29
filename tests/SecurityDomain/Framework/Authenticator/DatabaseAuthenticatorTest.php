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

use eTraxis\SecurityDomain\Model\Dictionary\AccountProvider;
use eTraxis\SecurityDomain\Model\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class DatabaseAuthenticatorTest extends TestCase
{
    /** @var DatabaseAuthenticator */
    protected $authenticator;

    /** @var User */
    protected $user;

    protected function setUp()
    {
        parent::setUp();

        $this->user = new User();

        $this->user->password = 'secret';

        $router = $this->createMock(RouterInterface::class);
        $router
            ->method('generate')
            ->willReturnMap([
                ['homepage', [], UrlGeneratorInterface::ABSOLUTE_PATH, '/'],
                ['login', [], UrlGeneratorInterface::ABSOLUTE_PATH, '/login'],
            ]);

        $session = $this->createMock(SessionInterface::class);
        $session
            ->method('get')
            ->willReturnMap([
                [Security::AUTHENTICATION_ERROR, null, null],
                ['_security.main.target_path', null, 'http://localhost/profile'],
            ]);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnMap([
                ['Authentication required.', [], null, null, 'You are not authenticated.'],
                ['Bad credentials.', [], null, null, 'Unknown user or wrong password.'],
            ]);

        $encoder = $this->createMock(UserPasswordEncoderInterface::class);
        $encoder
            ->method('isPasswordValid')
            ->willReturnMap([
                [$this->user, 'secret', true],
                [$this->user, 'wrong', false],
            ]);

        /** @var RouterInterface $router */
        /** @var SessionInterface $session */
        /** @var TranslatorInterface $translator */
        /** @var UserPasswordEncoderInterface $encoder */
        $this->authenticator = new DatabaseAuthenticator(
            $router,
            $session,
            $translator,
            $encoder
        );
    }

    public function testSupportsSuccess()
    {
        $request = new Request([], [
            '_username' => 'admin',
            '_password' => 'secret',
        ]);

        self::assertTrue($this->authenticator->supports($request));
    }

    public function testSupportsMissing()
    {
        $request = new Request();

        self::assertFalse($this->authenticator->supports($request));
    }

    public function testGetCredentials()
    {
        $expected = [
            'username' => 'admin',
            'password' => 'secret',
        ];

        $request = new Request([], [
            '_username' => 'admin',
            '_password' => 'secret',
        ]);

        self::assertSame($expected, $this->authenticator->getCredentials($request));
    }

    public function testGetUserSuccess()
    {
        $credentials = [
            'username' => 'admin',
            'password' => 'secret',
        ];

        $provider = $this->createMock(UserProviderInterface::class);
        $provider
            ->method('loadUserByUsername')
            ->with('admin')
            ->willReturn($this->user);

        /** @var UserProviderInterface $provider */
        self::assertSame($this->user, $this->authenticator->getUser($credentials, $provider));
    }

    public function testGetUserExternal()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Bad credentials.');

        $credentials = [
            'username' => 'admin',
            'password' => 'secret',
        ];

        $this->user->account->provider = AccountProvider::LDAP;

        $provider = $this->createMock(UserProviderInterface::class);
        $provider
            ->method('loadUserByUsername')
            ->with('admin')
            ->willReturn($this->user);

        /** @var UserProviderInterface $provider */
        $this->authenticator->getUser($credentials, $provider);
    }

    public function testGetUserNotFound()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Bad credentials.');

        $credentials = [
            'username' => 'unknown',
            'password' => 'secret',
        ];

        $provider = $this->createMock(UserProviderInterface::class);
        $provider
            ->method('loadUserByUsername')
            ->with('unknown')
            ->willThrowException(new UsernameNotFoundException('Not found.'));

        /** @var UserProviderInterface $provider */
        $this->authenticator->getUser($credentials, $provider);
    }

    public function testCheckCredentialsSuccess()
    {
        $credentials = [
            'username' => 'admin',
            'password' => 'secret',
        ];

        self::assertTrue($this->authenticator->checkCredentials($credentials, $this->user));
    }

    public function testCheckCredentialsFailure()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Bad credentials.');

        $credentials = [
            'username' => 'admin',
            'password' => 'wrong',
        ];

        $this->authenticator->checkCredentials($credentials, $this->user);
    }

    public function testOnAuthenticationSuccess()
    {
        $token = $this->authenticator->createAuthenticatedToken(new User(), 'main');

        $request  = new Request();
        $response = $this->authenticator->onAuthenticationSuccess($request, $token, 'main');

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('http://localhost/profile', $response->headers->get('Location'));
    }

    public function testOnAuthenticationSuccessAjax()
    {
        $token = $this->authenticator->createAuthenticatedToken(new User(), 'main');

        $request = new Request();
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = $this->authenticator->onAuthenticationSuccess($request, $token, 'main');

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame([], json_decode($response->getContent(), true));
    }

    public function testOnAuthenticationFailure()
    {
        $request   = new Request();
        $exception = new AuthenticationException('Bad credentials.');

        $response = $this->authenticator->onAuthenticationFailure($request, $exception);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/login', $response->headers->get('location'));
    }

    public function testOnAuthenticationFailureAjax()
    {
        $exception = new AuthenticationException('Bad credentials.');

        $session = $this->createMock(SessionInterface::class);
        $session
            ->method('get')
            ->willReturnMap([
                [Security::AUTHENTICATION_ERROR, null, $exception],
                ['_security.main.target_path', null, 'http://localhost/profile'],
            ]);

        /** @noinspection PhpUnhandledExceptionInspection */
        $reflection = new \ReflectionProperty(DatabaseAuthenticator::class, 'session');
        $reflection->setAccessible(true);
        $reflection->setValue($this->authenticator, $session);

        $request = new Request();
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = $this->authenticator->onAuthenticationFailure($request, $exception);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame('Bad credentials.', json_decode($response->getContent(), true));
    }

    public function testStart()
    {
        $request = new Request();

        $response = $this->authenticator->start($request);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/login', $response->headers->get('location'));
    }

    public function testStartAjax()
    {
        $request = new Request();
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = $this->authenticator->start($request);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame('You are not authenticated.', json_decode($response->getContent(), true));
    }

    public function testSupportsRememberMe()
    {
        self::assertTrue($this->authenticator->supportsRememberMe());
    }
}
