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

namespace eTraxis\TemplatesDomain\Framework\Controller\ApiTemplatesController;

use eTraxis\TemplatesDomain\Model\Entity\Template;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UnlockTemplateTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['id' => 'ASC']);
        self::assertTrue($template->isLocked);

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/templates/%s/unlock', $template->id);

        $response = $this->json(Request::METHOD_POST, $uri);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->doctrine->getManager()->refresh($template);

        self::assertFalse($template->isLocked);
    }

    public function test401()
    {
        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['id' => 'ASC']);

        $uri = sprintf('/api/templates/%s/unlock', $template->id);

        $response = $this->json(Request::METHOD_POST, $uri);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        /** @var Template $template */
        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support'], ['id' => 'ASC']);

        $this->loginAs('artem@example.com');

        $uri = sprintf('/api/templates/%s/unlock', $template->id);

        $response = $this->json(Request::METHOD_POST, $uri);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test404()
    {
        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/templates/%s/unlock', self::UNKNOWN_ENTITY_ID);

        $response = $this->json(Request::METHOD_POST, $uri);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
