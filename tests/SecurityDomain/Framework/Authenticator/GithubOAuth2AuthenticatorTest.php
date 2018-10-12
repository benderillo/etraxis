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
use eTraxis\Tests\TransactionalTestCase;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Github;
use League\OAuth2\Client\Provider\GithubResourceOwner;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;

class GithubOAuth2AuthenticatorTest extends TransactionalTestCase
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

    public function testGetProvider()
    {
        $authenticator = new GithubOAuth2Authenticator($this->router, $this->session, $this->commandBus, 'id', 'secret');
        self::assertInstanceOf(Github::class, $this->callMethod($authenticator, 'getProvider'));

        $authenticator = new GithubOAuth2Authenticator($this->router, $this->session, $this->commandBus, '', 'secret');
        self::assertNull($this->callMethod($authenticator, 'getProvider'));
    }

    public function testGetScope()
    {
        $expected = [
            'user:email',
        ];

        $authenticator = new GithubOAuth2Authenticator($this->router, $this->session, $this->commandBus, 'id', 'secret');
        self::assertSame($expected, $this->callMethod($authenticator, 'getScope'));
    }

    public function testGetUserFromTokenWithPublicEmail()
    {
        $owner = new GithubResourceOwner([
            'id'    => '423729',
            'email' => 'anna@example.com',
            'name'  => 'Anna Rodygina',
        ]);

        $provider = $this->createMock(AbstractProvider::class);
        $provider
            ->method('getResourceOwner')
            ->willReturn($owner);

        $authenticator = new GithubOAuth2Authenticator($this->router, $this->session, $this->commandBus, 'id', 'secret');
        $this->setProperty($authenticator, 'provider', $provider);

        $entity = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'anna@example.com']);
        self::assertNull($entity);

        $user = $this->callMethod($authenticator, 'getUserFromToken', ['token' => $this->createMock(AccessToken::class)]);

        $entity = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'anna@example.com']);
        self::assertNotNull($entity);

        self::assertSame($entity, $user);
    }

    public function testGetUserFromTokenWithPrivateEmail()
    {
        $owner = new GithubResourceOwner([
            'id'    => '423729',
            'email' => null,
            'name'  => 'Anna Rodygina',
        ]);

        $emails = [
            [
                'email'      => 'anna@example.com',
                'primary'    => true,
                'verified'   => true,
                'visibility' => 'private',
            ],
            [
                'email'      => 'anna@users.noreply.github.com',
                'primary'    => false,
                'verified'   => true,
                'visibility' => null,
            ],
        ];

        $body = $this->createMock(StreamInterface::class);
        $body
            ->method('getContents')
            ->willReturn(json_encode($emails));

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('getBody')
            ->willReturn($body);

        $provider = $this->createMock(AbstractProvider::class);
        $provider
            ->method('getResourceOwner')
            ->willReturn($owner);
        $provider
            ->method('getAuthenticatedRequest')
            ->willReturn($this->createMock(RequestInterface::class));
        $provider
            ->method('getResponse')
            ->willReturn($response);

        $authenticator = new GithubOAuth2Authenticator($this->router, $this->session, $this->commandBus, 'id', 'secret');
        $this->setProperty($authenticator, 'provider', $provider);

        $entity = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'anna@example.com']);
        self::assertNull($entity);

        $user = $this->callMethod($authenticator, 'getUserFromToken', ['token' => $this->createMock(AccessToken::class)]);

        $entity = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'anna@example.com']);
        self::assertNotNull($entity);

        self::assertSame($entity, $user);
    }
}
