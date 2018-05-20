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

namespace eTraxis\TemplatesDomain\Application\Command\Fields;

use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DeleteFieldCommandTest extends TransactionalTestCase
{
    /** @var \eTraxis\TemplatesDomain\Model\Repository\FieldRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Field::class);
    }

    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'DESC']);

        self::assertCount(3, $state->fields);

        [$field1, $field2, $field3] = $state->fields;

        self::assertSame(1, $field1->position);
        self::assertSame(2, $field2->position);
        self::assertSame(3, $field3->position);

        $command = new DeleteFieldCommand([
            'field' => $field1->id,
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->clear();

        $field = $this->repository->find($command->field);
        self::assertNull($field);

        /** @var State $state */
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'DESC']);

        self::assertCount(2, $state->fields);

        [$field1, $field2] = $state->fields;

        self::assertSame(1, $field1->position);
        self::assertSame(2, $field2->position);
    }

    public function testUnknownField()
    {
        $this->loginAs('admin@example.com');

        $command = new DeleteFieldCommand([
            'field' => self::UNKNOWN_ENTITY_ID,
        ]);

        $this->commandbus->handle($command);

        self::assertTrue(true);
    }

    public function testRemovedField()
    {
        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Task ID'], ['id' => 'DESC']);

        self::assertCount(1, $field->state->fields);

        $command = new DeleteFieldCommand([
            'field' => $field->id,
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->clear();

        $field = $this->repository->find($command->field);

        self::assertNotNull($field);
        self::assertTrue($field->isRemoved);
        self::assertCount(1, $field->state->fields);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'DESC']);

        $command = new DeleteFieldCommand([
            'field' => $field->id,
        ]);

        $this->commandbus->handle($command);
    }

    public function testUnlockedTemplate()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'DESC']);

        $field->state->template->isLocked = false;

        $command = new DeleteFieldCommand([
            'field' => $field->id,
        ]);

        $this->commandbus->handle($command);
    }
}
