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

namespace eTraxis\TemplatesDomain\Model\Repository;

use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\Tests\ReflectionTrait;
use eTraxis\Tests\WebTestCase;

class FieldRepositoryTest extends WebTestCase
{
    use ReflectionTrait;

    /** @var FieldRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Field::class);
    }

    public function testRepository()
    {
        self::assertInstanceOf(FieldRepository::class, $this->repository);
    }

    public function testGetValidationConstraints()
    {
        $names = [
            FieldType::CHECKBOX => 'New feature',
            FieldType::DATE     => 'Due date',
            FieldType::DECIMAL  => 'Test coverage',
            FieldType::DURATION => 'Effort',
            FieldType::ISSUE    => 'Issue ID',
            FieldType::LIST     => 'Priority',
            FieldType::NUMBER   => 'Delta',
            FieldType::STRING   => 'Commit ID',
            FieldType::TEXT     => 'Description',
        ];

        foreach ($names as $name) {
            /** @var Field $field */
            [$field] = $this->repository->findBy(['name' => $name], ['id' => 'ASC']);
            self::assertNotEmpty($this->repository->getValidationConstraints($field));
        }

        /** @var Field $field */
        [$field] = $this->repository->findBy(['name' => 'New feature'], ['id' => 'ASC']);
        $this->setProperty($field, 'type', 'unknown');
        self::assertEmpty($this->repository->getValidationConstraints($field));
    }
}
