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

/**
 * @coversDefaultClass \eTraxis\TemplatesDomain\Application\CommandHandler\Fields\SetFieldPositionHandler
 */
class SetFieldPositionCommandTest extends TransactionalTestCase
{
    /** @var \eTraxis\TemplatesDomain\Model\Repository\FieldRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Field::class);
    }

    /**
     * @covers ::handle
     * @covers ::setPosition
     */
    public function testSuccessUp()
    {
        $this->loginAs('admin@example.com');

        $expected = [
            'Commit ID',
            'Effort',
            'Delta',
            'Test coverage',
        ];

        /** @var Field $field */
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Effort'], ['id' => 'ASC']);

        $command = new SetFieldPositionCommand([
            'field'    => $field->id,
            'position' => $field->position - 1,
        ]);

        $this->commandBus->handle($command);

        self::assertSame($expected, $this->getFields($field->state));
    }

    /**
     * @covers ::handle
     * @covers ::setPosition
     */
    public function testSuccessDown()
    {
        $this->loginAs('admin@example.com');

        $expected = [
            'Commit ID',
            'Effort',
            'Delta',
            'Test coverage',
        ];

        /** @var Field $field */
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Delta'], ['id' => 'ASC']);

        $command = new SetFieldPositionCommand([
            'field'    => $field->id,
            'position' => $field->position + 1,
        ]);

        $this->commandBus->handle($command);

        self::assertSame($expected, $this->getFields($field->state));
    }

    /**
     * @covers ::handle
     * @covers ::setPosition
     */
    public function testSuccessTop()
    {
        $this->loginAs('admin@example.com');

        $expected = [
            'Effort',
            'Commit ID',
            'Delta',
            'Test coverage',
        ];

        /** @var Field $field */
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Effort'], ['id' => 'ASC']);

        $command = new SetFieldPositionCommand([
            'field'    => $field->id,
            'position' => 1,
        ]);

        $this->commandBus->handle($command);

        self::assertSame($expected, $this->getFields($field->state));
    }

    /**
     * @covers ::handle
     * @covers ::setPosition
     */
    public function testSuccessBottom()
    {
        $this->loginAs('admin@example.com');

        $expected = [
            'Commit ID',
            'Effort',
            'Test coverage',
            'Delta',
        ];

        /** @var Field $field */
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Delta'], ['id' => 'ASC']);

        $command = new SetFieldPositionCommand([
            'field'    => $field->id,
            'position' => PHP_INT_MAX,
        ]);

        $this->commandBus->handle($command);

        self::assertSame($expected, $this->getFields($field->state));
    }

    /**
     * @covers ::handle
     */
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

        $this->commandBus->handle($command);
    }

    /**
     * @covers ::handle
     */
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

        $this->commandBus->handle($command);
    }

    /**
     * @covers ::handle
     */
    public function testUnknownField()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        $command = new SetFieldPositionCommand([
            'field'    => self::UNKNOWN_ENTITY_ID,
            'position' => 1,
        ]);

        $this->commandBus->handle($command);
    }

    /**
     * @covers ::handle
     */
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

        $this->commandBus->handle($command);
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
