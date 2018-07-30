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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SetOrderFieldCommandTest extends TransactionalTestCase
{
    /** @var \eTraxis\TemplatesDomain\Model\Repository\FieldRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Field::class);
    }

    public function testSuccessUp()
    {
        $expected = [
            'Commit ID',
            'Effort',
            'Delta',
            'Test coverage',
        ];

        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Effort'], ['id' => 'ASC']);

        $command = new SetFieldPositionCommand([
            'field'    => $field->id,
            'position' => $field->position - 1,
        ]);

        $this->commandbus->handle($command);

        self::assertSame($expected, $this->getFields($field->state));
    }

    public function testSuccessDown()
    {
        $expected = [
            'Commit ID',
            'Effort',
            'Delta',
            'Test coverage',
        ];

        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Delta'], ['id' => 'ASC']);

        $command = new SetFieldPositionCommand([
            'field'    => $field->id,
            'position' => $field->position + 1,
        ]);

        $this->commandbus->handle($command);

        self::assertSame($expected, $this->getFields($field->state));
    }

    public function testSuccessTop()
    {
        $expected = [
            'Effort',
            'Commit ID',
            'Delta',
            'Test coverage',
        ];

        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Effort'], ['id' => 'ASC']);

        $command = new SetFieldPositionCommand([
            'field'    => $field->id,
            'position' => 1,
        ]);

        $this->commandbus->handle($command);

        self::assertSame($expected, $this->getFields($field->state));
    }

    public function testSuccessBottom()
    {
        $expected = [
            'Commit ID',
            'Effort',
            'Test coverage',
            'Delta',
        ];

        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Delta'], ['id' => 'ASC']);

        $command = new SetFieldPositionCommand([
            'field'    => $field->id,
            'position' => PHP_INT_MAX,
        ]);

        $this->commandbus->handle($command);

        self::assertSame($expected, $this->getFields($field->state));
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Effort'], ['id' => 'ASC']);

        $command = new SetFieldPositionCommand([
            'field'    => $field->id,
            'position' => 1,
        ]);

        $this->commandbus->handle($command);
    }

    public function testUnlockedTemplate()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'Effort'], ['id' => 'ASC']);

        $command = new SetFieldPositionCommand([
            'field'    => $field->id,
            'position' => 1,
        ]);

        $this->commandbus->handle($command);
    }

    public function testUnknownField()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        $command = new SetFieldPositionCommand([
            'field'    => self::UNKNOWN_ENTITY_ID,
            'position' => 1,
        ]);

        $this->commandbus->handle($command);
    }

    public function testRemovedField()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var Field $field */
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Task ID'], ['id' => 'ASC']);

        $command = new SetFieldPositionCommand([
            'field'    => $field->id,
            'position' => 1,
        ]);

        $this->commandbus->handle($command);
    }

    /**
     * @param State $state
     *
     * @return array
     */
    private function getFields(State $state)
    {
        /** @var Field[] $fields */
        $fields = $this->repository->findBy([
            'state'     => $state,
            'removedAt' => null,
        ], ['position' => 'ASC']);

        return array_map(function (Field $field) {
            return $field->name;
        }, $fields);
    }
}
