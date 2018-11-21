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

class ResetPasswordControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $uri = '/reset/9d73b1c922794370903898dae9ee8ada';

        $this->client->request(Request::METHOD_GET, $uri);
        self::assertTrue($this->client->getResponse()->isOk());

        $this->loginAs('admin@example.com');

        $this->client->request(Request::METHOD_GET, $uri);
        self::assertTrue($this->client->getResponse()->isRedirect('/'));
    }

    public function testResetPassword()
    {
        $uri = '/reset/9d73b1c922794370903898dae9ee8ada';

        $response = $this->json(Request::METHOD_POST, $uri);
        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $this->loginAs('admin@example.com');

        $response = $this->json(Request::METHOD_POST, $uri);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }
}
