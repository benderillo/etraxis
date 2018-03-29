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

namespace eTraxis\SharedDomain\Framework\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

class DefaultControllerTest extends WebTestCase
{
    public function testHomepage()
    {
        $uri = '/';

        $client = self::createClient();

        $client->request(Request::METHOD_GET, $uri);
        self::assertTrue($client->getResponse()->isOk());
    }

    public function testAdmin()
    {
        $uri = '/admin/';

        $client = self::createClient();

        $client->request(Request::METHOD_GET, $uri);
        self::assertTrue($client->getResponse()->isOk());
    }
}
