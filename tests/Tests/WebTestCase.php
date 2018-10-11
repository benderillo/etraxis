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

namespace eTraxis\Tests;

use eTraxis\SecurityDomain\Model\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as SymfonyWebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Extended web test case with an autoboot kernel and few helpers.
 */
class WebTestCase extends SymfonyWebTestCase
{
    /** @var \Symfony\Bundle\FrameworkBundle\Client */
    protected $client;

    /** @var \Symfony\Bridge\Doctrine\RegistryInterface */
    protected $doctrine;

    /** @var \League\Tactician\CommandBus */
    protected $commandBus;

    /**
     * Boots the kernel and retrieve most often used services.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->client = static::createClient();

        $this->doctrine   = $this->client->getContainer()->get('doctrine');
        $this->commandBus = $this->client->getContainer()->get('tactician.commandbus.default');
    }

    /**
     * Emulates authentication of specified user.
     *
     * @param string $email Login.
     *
     * @return null|User Whether user was authenticated.
     */
    protected function loginAs(string $email): ?User
    {
        /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $session */
        $session = $this->client->getContainer()->get('session');

        /** @var \eTraxis\SecurityDomain\Model\Repository\UserRepository $repository */
        $repository = $this->client->getContainer()->get('doctrine')->getRepository(User::class);

        /** @var User $user */
        if ($user = $repository->findOneByUsername($email)) {

            $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
            $this->client->getContainer()->get('security.token_storage')->setToken($token);

            $session->set('_security_main', serialize($token));
            $session->save();

            $cookie = new Cookie($session->getName(), $session->getId());
            $this->client->getCookieJar()->set($cookie);
        }

        return $user;
    }

    /**
     * Makes JSON request to specified URI.
     *
     * @param string $method  Request method.
     * @param string $uri     Request URI.
     * @param array  $data    Request parameters (for GET) or data payload (for POST/PUT/PATCH).
     * @param array  $headers Additional headers to pass with request.
     *
     * @return Response
     */
    protected function json(string $method, string $uri, array $data = [], array $headers = []): Response
    {
        $headers['CONTENT_TYPE'] = 'application/json';

        $parameters = $method === Request::METHOD_GET ? $data : [];

        $content = $method === Request::METHOD_POST || $method === Request::METHOD_PUT || $method === Request::METHOD_PATCH
            ? json_encode($data)
            : null;

        $this->client->request($method, $uri, $parameters, [], $headers, $content);

        return $this->client->getResponse();
    }
}
