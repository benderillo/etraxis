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

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);
        self::assertNotNull($field);

        $command = new DeleteFieldCommand([
            'field' => $field->id,
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->clear();

        $field = $this->repository->find($command->field);
        self::assertNull($field);
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
        [$field] = $this->repository->findBy(['name' => 'Task ID'], ['id' => 'ASC']);

        $command = new DeleteFieldCommand([
            'field' => $field->id,
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->clear();

        $field = $this->repository->find($command->field);

        self::assertNotNull($field);
        self::assertTrue($field->isRemoved);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

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

        $command = new DeleteFieldCommand([
            'field' => $field->id,
        ]);

        $this->commandbus->handle($command);
    }
}
