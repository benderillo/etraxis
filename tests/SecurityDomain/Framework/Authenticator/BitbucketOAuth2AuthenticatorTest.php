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
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Stevenmaguire\OAuth2\Client\Provider\Bitbucket;
use Stevenmaguire\OAuth2\Client\Provider\BitbucketResourceOwner;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;

class BitbucketOAuth2AuthenticatorTest extends TransactionalTestCase
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
        $authenticator = new BitbucketOAuth2Authenticator($this->router, $this->session, $this->commandBus, 'id', 'secret');
        self::assertInstanceOf(Bitbucket::class, $this->callMethod($authenticator, 'getProvider'));

        $authenticator = new BitbucketOAuth2Authenticator($this->router, $this->session, $this->commandBus, '', 'secret');
        self::assertNull($this->callMethod($authenticator, 'getProvider'));
    }

    public function testGetScope()
    {
        $expected = [
            'account',
            'email',
        ];

        $authenticator = new BitbucketOAuth2Authenticator($this->router, $this->session, $this->commandBus, 'id', 'secret');
        self::assertSame($expected, $this->callMethod($authenticator, 'getScope'));
    }

    public function testGetUserFromToken()
    {
        $owner = new BitbucketResourceOwner([
            'uuid'         => '423729',
            'display_name' => 'Anna Rodygina',
        ]);

        $contents = [
            'pagelen' => 10,
            'values'  => [
                [
                    'is_primary'   => true,
                    'is_confirmed' => false,
                    'type'         => 'email',
                    'email'        => 'anna@example.com',
                    'links'        => [
                        'self' => [
                            'href' => 'https://api.bitbucket.org/2.0/user/emails/anna@example.com',
                        ],
                    ],
                ],
                [
                    'is_primary'   => false,
                    'is_confirmed' => true,
                    'type'         => 'email',
                    'email'        => 'anna.rodygina@example.com',
                    'links'        => [
                        'self' => [
                            'href' => 'https://api.bitbucket.org/2.0/user/emails/anna.rodygina@example.com',
                        ],
                    ],
                ],
            ],
            'page'    => 1,
            'size'    => 2,
        ];

        $body = $this->createMock(StreamInterface::class);
        $body
            ->method('getContents')
            ->willReturn(json_encode($contents));

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

        $authenticator = new BitbucketOAuth2Authenticator($this->router, $this->session, $this->commandBus, 'id', 'secret');
        $this->setProperty($authenticator, 'provider', $provider);

        $entity = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'anna@example.com']);
        self::assertNull($entity);

        $user = $this->callMethod($authenticator, 'getUserFromToken', ['token' => $this->createMock(AccessToken::class)]);

        $entity = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'anna@example.com']);
        self::assertNotNull($entity);

        self::assertSame($entity, $user);
    }
}
