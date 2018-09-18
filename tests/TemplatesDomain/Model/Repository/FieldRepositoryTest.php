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
use eTraxis\TemplatesDomain\Model\Entity\DecimalValue;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Entity\ListItem;
use eTraxis\TemplatesDomain\Model\Entity\Project;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\TemplatesDomain\Model\Entity\StringValue;
use eTraxis\TemplatesDomain\Model\Entity\Template;
use eTraxis\TemplatesDomain\Model\Entity\TextValue;
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

    public function testFind()
    {
        [$field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        self::assertSame($field, $this->repository->find($field->id));
    }

    public function testFindAll()
    {
        $fields = $this->repository->findAll();

        self::assertCount(48, $fields);
    }

    public function testFindBy()
    {
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Duplicated'], ['id' => 'ASC']);

        /** @var Field[] $fields */
        $fields = $this->repository->findBy(['state' => $state], ['name' => 'ASC']);

        self::assertCount(2, $fields);
        self::assertSame('Issue ID', $fields[0]->name);
        self::assertSame('Task ID', $fields[1]->name);
    }

    public function testFindOneBy()
    {
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Duplicated'], ['id' => 'ASC']);

        /** @var Field $field */
        $field = $this->repository->findOneBy(['state' => $state], ['name' => 'ASC']);

        self::assertSame('Issue ID', $field->name);
    }

    public function testGetCollectionDefault()
    {
        $collection = $this->repository->getCollection();

        self::assertSame(0, $collection->from);
        self::assertSame(39, $collection->to);
        self::assertSame(40, $collection->total);

        $expected = array_map(function (Field $field) {
            return [$field->name, $field->state->template->project->name];
        }, $this->repository->findBy(['removedAt' => null]));

        $actual = array_map(function (Field $field) {
            return [$field->name, $field->state->template->project->name];
        }, $collection->data);

        sort($expected);
        sort($actual);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionOffset()
    {
        $expected = [
            'Effort',
            'Issue ID',
            'New feature',
            'Priority',
            'Test coverage',
        ];

        $collection = $this->repository->getCollection(35, FieldRepository::MAX_LIMIT, null, [], [
            Field::JSON_PROJECT => FieldRepository::SORT_ASC,
            Field::JSON_NAME    => FieldRepository::SORT_ASC,
        ]);

        self::assertSame(35, $collection->from);
        self::assertSame(39, $collection->to);
        self::assertSame(40, $collection->total);

        $actual = array_map(function (Field $field) {
            return $field->name;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionLimit()
    {
        $expected = [
            'Commit ID',
            'Delta',
            'Description',
            'Details',
            'Due date',
        ];

        $collection = $this->repository->getCollection(0, 5, null, [], [
            Field::JSON_PROJECT => FieldRepository::SORT_ASC,
            Field::JSON_NAME    => FieldRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(4, $collection->to);
        self::assertSame(40, $collection->total);

        $actual = array_map(function (Field $field) {
            return $field->name;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionSearch()
    {
        $expected = [
            ['Effort',   'Distinctio'],
            ['Priority', 'Distinctio'],
            ['Effort',   'Excepturi'],
            ['Priority', 'Excepturi'],
            ['Effort',   'Molestiae'],
            ['Priority', 'Molestiae'],
            ['Effort',   'Presto'],
            ['Priority', 'Presto'],
        ];

        $collection = $this->repository->getCollection(0, FieldRepository::MAX_LIMIT, 'oR', [], [
            Field::JSON_PROJECT => FieldRepository::SORT_ASC,
            Field::JSON_NAME    => FieldRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(7, $collection->to);
        self::assertSame(8, $collection->total);

        $actual = array_map(function (Field $field) {
            return [$field->name, $field->state->template->project->name];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionFilterByProject()
    {
        $expected = [
            ['Commit ID',     'Distinctio'],
            ['Delta',         'Distinctio'],
            ['Description',   'Distinctio'],
            ['Details',       'Distinctio'],
            ['Due date',      'Distinctio'],
            ['Effort',        'Distinctio'],
            ['Issue ID',      'Distinctio'],
            ['New feature',   'Distinctio'],
            ['Priority',      'Distinctio'],
            ['Test coverage', 'Distinctio'],
        ];

        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $collection = $this->repository->getCollection(0, FieldRepository::MAX_LIMIT, null, [
            Field::JSON_PROJECT => $project->id,
        ], [
            Field::JSON_PROJECT => FieldRepository::SORT_ASC,
            Field::JSON_NAME    => FieldRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(9, $collection->to);
        self::assertSame(10, $collection->total);

        $actual = array_map(function (Field $field) {
            return [$field->name, $field->state->template->project->name];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionFilterByProjectNull()
    {
        $collection = $this->repository->getCollection(0, FieldRepository::MAX_LIMIT, null, [
            Field::JSON_PROJECT => null,
        ], [
            Field::JSON_PROJECT => FieldRepository::SORT_ASC,
            Field::JSON_NAME    => FieldRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->total);
        self::assertCount(0, $collection->data);
    }

    public function testGetCollectionFilterByTemplate()
    {
        $expected = [
            ['Commit ID',     'Distinctio'],
            ['Delta',         'Distinctio'],
            ['Description',   'Distinctio'],
            ['Due date',      'Distinctio'],
            ['Effort',        'Distinctio'],
            ['Issue ID',      'Distinctio'],
            ['New feature',   'Distinctio'],
            ['Priority',      'Distinctio'],
            ['Test coverage', 'Distinctio'],
        ];

        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Development']);

        $collection = $this->repository->getCollection(0, FieldRepository::MAX_LIMIT, null, [
            Field::JSON_TEMPLATE => $template->id,
        ], [
            Field::JSON_PROJECT => FieldRepository::SORT_ASC,
            Field::JSON_NAME    => FieldRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(8, $collection->to);
        self::assertSame(9, $collection->total);

        $actual = array_map(function (Field $field) {
            return [$field->name, $field->state->template->project->name];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionFilterByTemplateNull()
    {
        $collection = $this->repository->getCollection(0, FieldRepository::MAX_LIMIT, null, [
            Field::JSON_TEMPLATE => null,
        ], [
            Field::JSON_PROJECT => FieldRepository::SORT_ASC,
            Field::JSON_NAME    => FieldRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->total);
        self::assertCount(0, $collection->data);
    }

    public function testGetCollectionFilterByState()
    {
        $expected = [
            ['Description', 'Distinctio'],
            ['New feature', 'Distinctio'],
            ['Priority',    'Distinctio'],
        ];

        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New']);

        $collection = $this->repository->getCollection(0, FieldRepository::MAX_LIMIT, null, [
            Field::JSON_STATE => $state->id,
        ], [
            Field::JSON_PROJECT => FieldRepository::SORT_ASC,
            Field::JSON_NAME    => FieldRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(2, $collection->to);
        self::assertSame(3, $collection->total);

        $actual = array_map(function (Field $field) {
            return [$field->name, $field->state->template->project->name];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionFilterByStateNull()
    {
        $collection = $this->repository->getCollection(0, FieldRepository::MAX_LIMIT, null, [
            Field::JSON_STATE => null,
        ], [
            Field::JSON_PROJECT => FieldRepository::SORT_ASC,
            Field::JSON_NAME    => FieldRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->total);
        self::assertCount(0, $collection->data);
    }

    public function testGetCollectionFilterByName()
    {
        $expected = [
            ['Due date',    'Distinctio'],
            ['New feature', 'Distinctio'],
            ['Due date',    'Excepturi'],
            ['New feature', 'Excepturi'],
            ['Due date',    'Molestiae'],
            ['New feature', 'Molestiae'],
            ['Due date',    'Presto'],
            ['New feature', 'Presto'],
        ];

        $collection = $this->repository->getCollection(0, FieldRepository::MAX_LIMIT, null, [
            Field::JSON_NAME => 'aT',
        ], [
            Field::JSON_PROJECT => FieldRepository::SORT_ASC,
            Field::JSON_NAME    => FieldRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(7, $collection->to);
        self::assertSame(8, $collection->total);

        $actual = array_map(function (Field $field) {
            return [$field->name, $field->state->template->project->name];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionFilterByNameNull()
    {
        $collection = $this->repository->getCollection(0, FieldRepository::MAX_LIMIT, null, [
            Field::JSON_NAME => null,
        ], [
            Field::JSON_PROJECT => FieldRepository::SORT_ASC,
            Field::JSON_NAME    => FieldRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->total);
        self::assertCount(0, $collection->data);
    }

    public function testGetCollectionFilterByType()
    {
        $expected = [
            ['Description', 'Distinctio'],
            ['Details',     'Distinctio'],
            ['Description', 'Excepturi'],
            ['Details',     'Excepturi'],
            ['Description', 'Molestiae'],
            ['Details',     'Molestiae'],
            ['Description', 'Presto'],
            ['Details',     'Presto'],
        ];

        $collection = $this->repository->getCollection(0, FieldRepository::MAX_LIMIT, null, [
            Field::JSON_TYPE => FieldType::TEXT,
        ], [
            Field::JSON_PROJECT => FieldRepository::SORT_ASC,
            Field::JSON_NAME    => FieldRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(7, $collection->to);
        self::assertSame(8, $collection->total);

        $actual = array_map(function (Field $field) {
            return [$field->name, $field->state->template->project->name];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionFilterByTypeNull()
    {
        $collection = $this->repository->getCollection(0, FieldRepository::MAX_LIMIT, null, [
            Field::JSON_TYPE => null,
        ], [
            Field::JSON_PROJECT => FieldRepository::SORT_ASC,
            Field::JSON_NAME    => FieldRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->total);
        self::assertCount(0, $collection->data);
    }

    public function testGetCollectionFilterByDescription()
    {
        $expected = [
            ['Delta', 'Distinctio'],
            ['Delta', 'Excepturi'],
            ['Delta', 'Molestiae'],
            ['Delta', 'Presto'],
        ];

        $collection = $this->repository->getCollection(0, FieldRepository::MAX_LIMIT, null, [
            Field::JSON_DESCRIPTION => 'LoC',
        ], [
            Field::JSON_PROJECT => FieldRepository::SORT_ASC,
            Field::JSON_NAME    => FieldRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(3, $collection->to);
        self::assertSame(4, $collection->total);

        $actual = array_map(function (Field $field) {
            return [$field->name, $field->state->template->project->name];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionFilterByDescriptionNull()
    {
        $expected = [
            ['Commit ID',     'Distinctio'],
            ['Description',   'Distinctio'],
            ['Details',       'Distinctio'],
            ['Due date',      'Distinctio'],
            ['Issue ID',      'Distinctio'],
            ['New feature',   'Distinctio'],
            ['Priority',      'Distinctio'],
            ['Test coverage', 'Distinctio'],
            ['Commit ID',     'Excepturi'],
            ['Description',   'Excepturi'],
            ['Details',       'Excepturi'],
            ['Due date',      'Excepturi'],
            ['Issue ID',      'Excepturi'],
            ['New feature',   'Excepturi'],
            ['Priority',      'Excepturi'],
            ['Test coverage', 'Excepturi'],
            ['Commit ID',     'Molestiae'],
            ['Description',   'Molestiae'],
            ['Details',       'Molestiae'],
            ['Due date',      'Molestiae'],
            ['Issue ID',      'Molestiae'],
            ['New feature',   'Molestiae'],
            ['Priority',      'Molestiae'],
            ['Test coverage', 'Molestiae'],
            ['Commit ID',     'Presto'],
            ['Description',   'Presto'],
            ['Details',       'Presto'],
            ['Due date',      'Presto'],
            ['Issue ID',      'Presto'],
            ['New feature',   'Presto'],
            ['Priority',      'Presto'],
            ['Test coverage', 'Presto'],
        ];

        $collection = $this->repository->getCollection(0, FieldRepository::MAX_LIMIT, null, [
            Field::JSON_DESCRIPTION => null,
        ], [
            Field::JSON_PROJECT => FieldRepository::SORT_ASC,
            Field::JSON_NAME    => FieldRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(31, $collection->to);
        self::assertSame(32, $collection->total);

        $actual = array_map(function (Field $field) {
            return [$field->name, $field->state->template->project->name];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionFilterByPosition()
    {
        $expected = [
            ['Effort',      'Distinctio'],
            ['New feature', 'Distinctio'],
            ['Effort',      'Excepturi'],
            ['New feature', 'Excepturi'],
            ['Effort',      'Molestiae'],
            ['New feature', 'Molestiae'],
            ['Effort',      'Presto'],
            ['New feature', 'Presto'],
        ];

        $collection = $this->repository->getCollection(0, FieldRepository::MAX_LIMIT, null, [
            Field::JSON_POSITION => 3,
        ], [
            Field::JSON_PROJECT => FieldRepository::SORT_ASC,
            Field::JSON_NAME    => FieldRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(7, $collection->to);
        self::assertSame(8, $collection->total);

        $actual = array_map(function (Field $field) {
            return [$field->name, $field->state->template->project->name];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionFilterByPositionNull()
    {
        $collection = $this->repository->getCollection(0, FieldRepository::MAX_LIMIT, null, [
            Field::JSON_POSITION => null,
        ], [
            Field::JSON_PROJECT => FieldRepository::SORT_ASC,
            Field::JSON_NAME    => FieldRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->total);
        self::assertCount(0, $collection->data);
    }

    public function testGetCollectionFilterByRequired()
    {
        $expected = [
            ['Delta',    'Distinctio'],
            ['Details',  'Distinctio'],
            ['Effort',   'Distinctio'],
            ['Issue ID', 'Distinctio'],
            ['Priority', 'Distinctio'],
            ['Delta',    'Excepturi'],
            ['Details',  'Excepturi'],
            ['Effort',   'Excepturi'],
            ['Issue ID', 'Excepturi'],
            ['Priority', 'Excepturi'],
            ['Delta',    'Molestiae'],
            ['Details',  'Molestiae'],
            ['Effort',   'Molestiae'],
            ['Issue ID', 'Molestiae'],
            ['Priority', 'Molestiae'],
            ['Delta',    'Presto'],
            ['Details',  'Presto'],
            ['Effort',   'Presto'],
            ['Issue ID', 'Presto'],
            ['Priority', 'Presto'],
        ];

        $collection = $this->repository->getCollection(0, FieldRepository::MAX_LIMIT, null, [
            Field::JSON_REQUIRED => true,
        ], [
            Field::JSON_PROJECT => FieldRepository::SORT_ASC,
            Field::JSON_NAME    => FieldRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(19, $collection->to);
        self::assertSame(20, $collection->total);

        $actual = array_map(function (Field $field) {
            return [$field->name, $field->state->template->project->name];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionSortByProject()
    {
        $expected = [
            ['Commit ID',     'Distinctio'],
            ['Delta',         'Distinctio'],
            ['Description',   'Distinctio'],
            ['Details',       'Distinctio'],
            ['Due date',      'Distinctio'],
            ['Effort',        'Distinctio'],
            ['Issue ID',      'Distinctio'],
            ['New feature',   'Distinctio'],
            ['Priority',      'Distinctio'],
            ['Test coverage', 'Distinctio'],
            ['Commit ID',     'Excepturi'],
            ['Delta',         'Excepturi'],
            ['Description',   'Excepturi'],
            ['Details',       'Excepturi'],
            ['Due date',      'Excepturi'],
        ];

        $collection = $this->repository->getCollection(0, 15, null, [], [
            Field::JSON_PROJECT => FieldRepository::SORT_ASC,
            Field::JSON_NAME    => FieldRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(14, $collection->to);
        self::assertSame(40, $collection->total);

        $actual = array_map(function (Field $field) {
            return [$field->name, $field->state->template->project->name];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionSortByTemplate()
    {
        $expected = [
            ['Details',     'Distinctio'],
            ['Details',     'Excepturi'],
            ['Details',     'Molestiae'],
            ['Details',     'Presto'],
            ['Commit ID',   'Distinctio'],
            ['Commit ID',   'Excepturi'],
            ['Commit ID',   'Molestiae'],
            ['Commit ID',   'Presto'],
            ['Delta',       'Distinctio'],
            ['Delta',       'Excepturi'],
            ['Delta',       'Molestiae'],
            ['Delta',       'Presto'],
            ['Description', 'Distinctio'],
            ['Description', 'Excepturi'],
            ['Description', 'Molestiae'],
        ];

        $collection = $this->repository->getCollection(0, 15, null, [], [
            Field::JSON_TEMPLATE => FieldRepository::SORT_DESC,
            Field::JSON_NAME     => FieldRepository::SORT_ASC,
            Field::JSON_PROJECT  => FieldRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(14, $collection->to);
        self::assertSame(40, $collection->total);

        $actual = array_map(function (Field $field) {
            return [$field->name, $field->state->template->project->name];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionSortByState()
    {
        $expected = [
            ['Due date',  'Distinctio'],
            ['Due date',  'Excepturi'],
            ['Due date',  'Molestiae'],
            ['Due date',  'Presto'],
            ['Commit ID', 'Distinctio'],
            ['Commit ID', 'Excepturi'],
            ['Commit ID', 'Molestiae'],
            ['Commit ID', 'Presto'],
            ['Delta',     'Distinctio'],
            ['Delta',     'Excepturi'],
            ['Delta',     'Molestiae'],
            ['Delta',     'Presto'],
            ['Effort',    'Distinctio'],
            ['Effort',    'Excepturi'],
            ['Effort',    'Molestiae'],
        ];

        $collection = $this->repository->getCollection(0, 15, null, [], [
            Field::JSON_STATE   => FieldRepository::SORT_ASC,
            Field::JSON_NAME    => FieldRepository::SORT_ASC,
            Field::JSON_PROJECT => FieldRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(14, $collection->to);
        self::assertSame(40, $collection->total);

        $actual = array_map(function (Field $field) {
            return [$field->name, $field->state->template->project->name];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionSortByName()
    {
        $expected = [
            ['Commit ID',   'Distinctio'],
            ['Commit ID',   'Excepturi'],
            ['Commit ID',   'Molestiae'],
            ['Commit ID',   'Presto'],
            ['Delta',       'Distinctio'],
            ['Delta',       'Excepturi'],
            ['Delta',       'Molestiae'],
            ['Delta',       'Presto'],
            ['Description', 'Distinctio'],
            ['Description', 'Excepturi'],
            ['Description', 'Molestiae'],
            ['Description', 'Presto'],
            ['Details',     'Distinctio'],
            ['Details',     'Excepturi'],
            ['Details',     'Molestiae'],
        ];

        $collection = $this->repository->getCollection(0, 15, null, [], [
            Field::JSON_NAME    => FieldRepository::SORT_ASC,
            Field::JSON_PROJECT => FieldRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(14, $collection->to);
        self::assertSame(40, $collection->total);

        $actual = array_map(function (Field $field) {
            return [$field->name, $field->state->template->project->name];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionSortByType()
    {
        $expected = [
            ['Description', 'Distinctio'],
            ['Description', 'Excepturi'],
            ['Description', 'Molestiae'],
            ['Description', 'Presto'],
            ['Details',     'Distinctio'],
            ['Details',     'Excepturi'],
            ['Details',     'Molestiae'],
            ['Details',     'Presto'],
            ['Commit ID',   'Distinctio'],
            ['Commit ID',   'Excepturi'],
            ['Commit ID',   'Molestiae'],
            ['Commit ID',   'Presto'],
            ['Delta',       'Distinctio'],
            ['Delta',       'Excepturi'],
            ['Delta',       'Molestiae'],
        ];

        $collection = $this->repository->getCollection(0, 15, null, [], [
            Field::JSON_TYPE    => FieldRepository::SORT_DESC,
            Field::JSON_NAME    => FieldRepository::SORT_ASC,
            Field::JSON_PROJECT => FieldRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(14, $collection->to);
        self::assertSame(40, $collection->total);

        $actual = array_map(function (Field $field) {
            return [$field->name, $field->state->template->project->name];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionSortByDescription()
    {
        $expected = [
            ['Delta',       'Distinctio'],
            ['Delta',       'Excepturi'],
            ['Delta',       'Molestiae'],
            ['Delta',       'Presto'],
            ['Effort',      'Distinctio'],
            ['Effort',      'Excepturi'],
            ['Effort',      'Molestiae'],
            ['Effort',      'Presto'],
            ['Commit ID',   'Distinctio'],
            ['Commit ID',   'Excepturi'],
            ['Commit ID',   'Molestiae'],
            ['Commit ID',   'Presto'],
            ['Description', 'Distinctio'],
            ['Description', 'Excepturi'],
            ['Description', 'Molestiae'],
        ];

        $collection = $this->repository->getCollection(0, 15, null, [], [
            Field::JSON_DESCRIPTION => FieldRepository::SORT_DESC,
            Field::JSON_NAME        => FieldRepository::SORT_ASC,
            Field::JSON_PROJECT     => FieldRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(14, $collection->to);
        self::assertSame(40, $collection->total);

        $actual = array_map(function (Field $field) {
            return [$field->name, $field->state->template->project->name];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionSortByPosition()
    {
        $expected = [
            ['Test coverage', 'Distinctio'],
            ['Test coverage', 'Excepturi'],
            ['Test coverage', 'Molestiae'],
            ['Test coverage', 'Presto'],
            ['Effort',        'Distinctio'],
            ['Effort',        'Excepturi'],
            ['Effort',        'Molestiae'],
            ['Effort',        'Presto'],
            ['New feature',   'Distinctio'],
            ['New feature',   'Excepturi'],
            ['New feature',   'Molestiae'],
            ['New feature',   'Presto'],
            ['Delta',         'Distinctio'],
            ['Delta',         'Excepturi'],
            ['Delta',         'Molestiae'],
        ];

        $collection = $this->repository->getCollection(0, 15, null, [], [
            Field::JSON_POSITION => FieldRepository::SORT_DESC,
            Field::JSON_NAME     => FieldRepository::SORT_ASC,
            Field::JSON_PROJECT  => FieldRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(14, $collection->to);
        self::assertSame(40, $collection->total);

        $actual = array_map(function (Field $field) {
            return [$field->name, $field->state->template->project->name];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionSortByRequired()
    {
        $expected = [
            ['Commit ID',   'Distinctio'],
            ['Commit ID',   'Excepturi'],
            ['Commit ID',   'Molestiae'],
            ['Commit ID',   'Presto'],
            ['Description', 'Distinctio'],
            ['Description', 'Excepturi'],
            ['Description', 'Molestiae'],
            ['Description', 'Presto'],
            ['Due date',    'Distinctio'],
            ['Due date',    'Excepturi'],
            ['Due date',    'Molestiae'],
            ['Due date',    'Presto'],
            ['New feature', 'Distinctio'],
            ['New feature', 'Excepturi'],
            ['New feature', 'Molestiae'],
        ];

        $collection = $this->repository->getCollection(0, 15, null, [], [
            Field::JSON_REQUIRED => FieldRepository::SORT_ASC,
            Field::JSON_NAME     => FieldRepository::SORT_ASC,
            Field::JSON_PROJECT  => FieldRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(14, $collection->to);
        self::assertSame(40, $collection->total);

        $actual = array_map(function (Field $field) {
            return [$field->name, $field->state->template->project->name];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testWarmupCache1()
    {
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'ASC']);

        $textRepository = $this->doctrine->getRepository(TextValue::class);
        $listRepository = $this->doctrine->getRepository(ListItem::class);

        /** @var \Psr\SimpleCache\CacheInterface $textCache */
        $textCache = $this->getProperty($textRepository, 'cache');

        /** @var \Psr\SimpleCache\CacheInterface $listCache */
        $listCache = $this->getProperty($listRepository, 'cache');

        $defaultText   = $textRepository->findOneBy(['value' => 'How to reproduce:']);
        [$defaultItem] = $listRepository->findBy(['value' => 2], ['id' => 'ASC']);

        self::assertFalse($textCache->has("{$defaultText->id}"));
        self::assertFalse($listCache->has("{$defaultItem->id}"));

        $this->repository->findBy(['state' => $state]);

        self::assertTrue($textCache->has("{$defaultText->id}"));
        self::assertTrue($listCache->has("{$defaultItem->id}"));
    }

    public function testWarmupCache2()
    {
        [$state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        $decimalRepository = $this->doctrine->getRepository(DecimalValue::class);
        $stringRepository  = $this->doctrine->getRepository(StringValue::class);

        /** @var \Psr\SimpleCache\CacheInterface $decimalCache */
        $decimalCache = $this->getProperty($decimalRepository, 'cache');

        /** @var \Psr\SimpleCache\CacheInterface $stringCache */
        $stringCache = $this->getProperty($stringRepository, 'cache');

        $minimum = $decimalRepository->findOneBy(['value' => '0']);
        $maximum = $decimalRepository->findOneBy(['value' => '100']);
        $default = $stringRepository->findOneBy(['value' => 'Git commit ID']);

        self::assertFalse($decimalCache->has("{$minimum->id}"));
        self::assertFalse($decimalCache->has("{$maximum->id}"));
        self::assertFalse($stringCache->has("{$default->id}"));

        $this->repository->findBy(['state' => $state]);

        self::assertTrue($decimalCache->has("{$minimum->id}"));
        self::assertTrue($decimalCache->has("{$maximum->id}"));
        self::assertTrue($stringCache->has("{$default->id}"));
    }
}
