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

namespace eTraxis\SecurityDomain\Framework\Controller;

use eTraxis\Tests\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @coversDefaultClass \eTraxis\SecurityDomain\Framework\Controller\OAuth2Controller
 */
class OAuth2ControllerTest extends WebTestCase
{
    /**
     * @covers ::callbackGoogle
     */
    public function testCallbackGoogle()
    {
        $uri = '/oauth/google';

        $this->client->request(Request::METHOD_GET, $uri);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());

        $this->loginAs('admin@example.com');

        $this->client->request(Request::METHOD_GET, $uri);
        self::assertTrue($this->client->getResponse()->isRedirect('/'));
    }

    /**
     * @covers ::callbackGithub
     */
    public function testCallbackGithub()
    {
        $uri = '/oauth/github';

        $this->client->request(Request::METHOD_GET, $uri);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());

        $this->loginAs('admin@example.com');

        $this->client->request(Request::METHOD_GET, $uri);
        self::assertTrue($this->client->getResponse()->isRedirect('/'));
    }

    /**
     * @covers ::callbackBitbucket
     */
    public function testCallbackBitbucket()
    {
        $uri = '/oauth/bitbucket';

        $this->client->request(Request::METHOD_GET, $uri);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());

        $this->loginAs('admin@example.com');

        $this->client->request(Request::METHOD_GET, $uri);
        self::assertTrue($this->client->getResponse()->isRedirect('/'));
    }
}
