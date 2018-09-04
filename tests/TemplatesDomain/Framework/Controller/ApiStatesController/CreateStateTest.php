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

namespace eTraxis\TemplatesDomain\Framework\Controller\ApiStatesController;

use eTraxis\TemplatesDomain\Model\Dictionary\StateResponsible;
use eTraxis\TemplatesDomain\Model\Dictionary\StateType;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\TemplatesDomain\Model\Entity\Template;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CreateStateTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        /** @var Template $template */
        [/* skipping */, $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        /** @var State $state */
        $state = $this->doctrine->getRepository(State::class)->findOneBy(['name' => 'Started']);
        self::assertNull($state);

        $data = [
            'template'    => $template->id,
            'name'        => 'Started',
            'type'        => StateType::INTERMEDIATE,
            'responsible' => StateResponsible::KEEP,
            'nextState'   => null,
        ];

        $this->loginAs('admin@example.com');

        $uri = '/api/states';

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        $state = $this->doctrine->getRepository(State::class)->findOneBy(['name' => 'Started']);
        self::assertNotNull($state);

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertTrue($response->isRedirect("http://localhost/api/states/{$state->id}"));
    }

    public function test400()
    {
        $this->loginAs('admin@example.com');

        $uri = '/api/states';

        $response = $this->json(Request::METHOD_POST, $uri);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function test401()
    {
        /** @var Template $template */
        [/* skipping */, $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $data = [
            'template'    => $template->id,
            'name'        => 'Started',
            'type'        => StateType::INTERMEDIATE,
            'responsible' => StateResponsible::KEEP,
            'nextState'   => null,
        ];

        $uri = '/api/states';

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        /** @var Template $template */
        [/* skipping */, $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $data = [
            'template'    => $template->id,
            'name'        => 'Started',
            'type'        => StateType::INTERMEDIATE,
            'responsible' => StateResponsible::KEEP,
            'nextState'   => null,
        ];

        $this->loginAs('artem@example.com');

        $uri = '/api/states';

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test404()
    {
        $data = [
            'template'    => self::UNKNOWN_ENTITY_ID,
            'name'        => 'Started',
            'type'        => StateType::INTERMEDIATE,
            'responsible' => StateResponsible::KEEP,
            'nextState'   => null,
        ];

        $this->loginAs('admin@example.com');

        $uri = '/api/states';

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function test409()
    {
        /** @var Template $template */
        [/* skipping */, $template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $data = [
            'template'    => $template->id,
            'name'        => 'Completed',
            'type'        => StateType::INTERMEDIATE,
            'responsible' => StateResponsible::KEEP,
            'nextState'   => null,
        ];

        $this->loginAs('admin@example.com');

        $uri = '/api/states';

        $response = $this->json(Request::METHOD_POST, $uri, $data);

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
    }
}
