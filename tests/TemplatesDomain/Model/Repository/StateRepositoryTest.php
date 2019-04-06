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

use eTraxis\TemplatesDomain\Model\Dictionary\StateResponsible;
use eTraxis\TemplatesDomain\Model\Dictionary\StateType;
use eTraxis\TemplatesDomain\Model\Entity\Project;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\TemplatesDomain\Model\Entity\Template;
use eTraxis\Tests\WebTestCase;

/**
 * @coversDefaultClass \eTraxis\TemplatesDomain\Model\Repository\StateRepository
 */
class StateRepositoryTest extends WebTestCase
{
    /** @var StateRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(State::class);
    }

    /**
     * @covers ::__construct
     */
    public function testRepository()
    {
        self::assertInstanceOf(StateRepository::class, $this->repository);
    }

    /**
     * @covers ::getCollection
     */
    public function testGetCollectionDefault()
    {
        $collection = $this->repository->getCollection();

        self::assertSame(0, $collection->from);
        self::assertSame(27, $collection->to);
        self::assertSame(28, $collection->total);

        $expected = array_map(function (State $state) {
            return [$state->name, $state->template->project->name];
        }, $this->repository->findAll());

        $actual = array_map(function (State $state) {
            return [$state->name, $state->template->project->name];
        }, $collection->data);

        sort($expected);
        sort($actual);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     */
    public function testGetCollectionOffset()
    {
        $expected = [
            'Opened',
            'Resolved',
            'Submitted',
        ];

        $collection = $this->repository->getCollection(25, StateRepository::MAX_LIMIT, null, [], [
            State::JSON_PROJECT => StateRepository::SORT_ASC,
            State::JSON_NAME    => StateRepository::SORT_ASC,
        ]);

        self::assertSame(25, $collection->from);
        self::assertSame(27, $collection->to);
        self::assertSame(28, $collection->total);

        $actual = array_map(function (State $state) {
            return $state->name;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     */
    public function testGetCollectionLimit()
    {
        $expected = [
            'Assigned',
            'Completed',
            'Duplicated',
            'New',
            'Opened',
        ];

        $collection = $this->repository->getCollection(0, 5, null, [], [
            State::JSON_PROJECT => StateRepository::SORT_ASC,
            State::JSON_NAME    => StateRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(4, $collection->to);
        self::assertSame(28, $collection->total);

        $actual = array_map(function (State $state) {
            return $state->name;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::querySearch
     */
    public function testGetCollectionSearch()
    {
        $expected = [
            ['Assigned', 'Distinctio'],
            ['Opened',   'Distinctio'],
            ['Assigned', 'Excepturi'],
            ['Opened',   'Excepturi'],
            ['Assigned', 'Molestiae'],
            ['Opened',   'Molestiae'],
            ['Assigned', 'Presto'],
            ['Opened',   'Presto'],
        ];

        $collection = $this->repository->getCollection(0, StateRepository::MAX_LIMIT, 'NEd', [], [
            State::JSON_PROJECT => StateRepository::SORT_ASC,
            State::JSON_NAME    => StateRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(7, $collection->to);
        self::assertSame(8, $collection->total);

        $actual = array_map(function (State $state) {
            return [$state->name, $state->template->project->name];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByProject()
    {
        $expected = [
            ['Assigned',   'Distinctio'],
            ['Completed',  'Distinctio'],
            ['Duplicated', 'Distinctio'],
            ['New',        'Distinctio'],
            ['Opened',     'Distinctio'],
            ['Resolved',   'Distinctio'],
            ['Submitted',  'Distinctio'],
        ];

        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $collection = $this->repository->getCollection(0, StateRepository::MAX_LIMIT, null, [
            State::JSON_PROJECT => $project->id,
        ], [
            State::JSON_PROJECT => StateRepository::SORT_ASC,
            State::JSON_NAME    => StateRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(6, $collection->to);
        self::assertSame(7, $collection->total);

        $actual = array_map(function (State $state) {
            return [$state->name, $state->template->project->name];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByProjectNull()
    {
        $collection = $this->repository->getCollection(0, StateRepository::MAX_LIMIT, null, [
            State::JSON_PROJECT => null,
        ], [
            State::JSON_PROJECT => StateRepository::SORT_ASC,
            State::JSON_NAME    => StateRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->total);
        self::assertCount(0, $collection->data);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByTemplate()
    {
        $expected = [
            ['Opened',    'Distinctio'],
            ['Resolved',  'Distinctio'],
            ['Submitted', 'Distinctio'],
        ];

        [$template] = $this->doctrine->getRepository(Template::class)->findBy(['name' => 'Support']);

        $collection = $this->repository->getCollection(0, StateRepository::MAX_LIMIT, null, [
            State::JSON_TEMPLATE => $template->id,
        ], [
            State::JSON_PROJECT => StateRepository::SORT_ASC,
            State::JSON_NAME    => StateRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(2, $collection->to);
        self::assertSame(3, $collection->total);

        $actual = array_map(function (State $state) {
            return [$state->name, $state->template->project->name];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByTemplateNull()
    {
        $collection = $this->repository->getCollection(0, StateRepository::MAX_LIMIT, null, [
            State::JSON_TEMPLATE => null,
        ], [
            State::JSON_PROJECT => StateRepository::SORT_ASC,
            State::JSON_NAME    => StateRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->total);
        self::assertCount(0, $collection->data);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByName()
    {
        $expected = [
            ['Assigned', 'Distinctio'],
            ['New',      'Distinctio'],
            ['Opened',   'Distinctio'],
            ['Assigned', 'Excepturi'],
            ['New',      'Excepturi'],
            ['Opened',   'Excepturi'],
            ['Assigned', 'Molestiae'],
            ['New',      'Molestiae'],
            ['Opened',   'Molestiae'],
            ['Assigned', 'Presto'],
            ['New',      'Presto'],
            ['Opened',   'Presto'],
        ];

        $collection = $this->repository->getCollection(0, StateRepository::MAX_LIMIT, null, [
            State::JSON_NAME => 'nE',
        ], [
            State::JSON_PROJECT => StateRepository::SORT_ASC,
            State::JSON_NAME    => StateRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(11, $collection->to);
        self::assertSame(12, $collection->total);

        $actual = array_map(function (State $state) {
            return [$state->name, $state->template->project->name];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByNameNull()
    {
        $collection = $this->repository->getCollection(0, StateRepository::MAX_LIMIT, null, [
            State::JSON_NAME => null,
        ], [
            State::JSON_PROJECT => StateRepository::SORT_ASC,
            State::JSON_NAME    => StateRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->total);
        self::assertCount(0, $collection->data);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByType()
    {
        $expected = [
            ['Completed',  'Distinctio'],
            ['Duplicated', 'Distinctio'],
            ['Resolved',   'Distinctio'],
            ['Completed',  'Excepturi'],
            ['Duplicated', 'Excepturi'],
            ['Resolved',   'Excepturi'],
            ['Completed',  'Molestiae'],
            ['Duplicated', 'Molestiae'],
            ['Resolved',   'Molestiae'],
        ];

        $collection = $this->repository->getCollection(0, StateRepository::MAX_LIMIT, null, [
            State::JSON_TYPE => StateType::FINAL,
        ], [
            State::JSON_PROJECT => StateRepository::SORT_ASC,
            State::JSON_NAME    => StateRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(8, $collection->to);
        self::assertSame(9, $collection->total);

        $actual = array_map(function (State $state) {
            return [$state->name, $state->template->project->name];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByTypeNull()
    {
        $collection = $this->repository->getCollection(0, StateRepository::MAX_LIMIT, null, [
            State::JSON_TYPE => null,
        ], [
            State::JSON_PROJECT => StateRepository::SORT_ASC,
            State::JSON_NAME    => StateRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->total);
        self::assertCount(0, $collection->data);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByResponsible()
    {
        $expected = [
            ['Submitted', 'Distinctio'],
            ['Submitted', 'Excepturi'],
            ['Submitted', 'Molestiae'],
            ['Submitted', 'Presto'],
        ];

        $collection = $this->repository->getCollection(0, StateRepository::MAX_LIMIT, null, [
            State::JSON_RESPONSIBLE => StateResponsible::KEEP,
        ], [
            State::JSON_PROJECT => StateRepository::SORT_ASC,
            State::JSON_NAME    => StateRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(3, $collection->to);
        self::assertSame(4, $collection->total);

        $actual = array_map(function (State $state) {
            return [$state->name, $state->template->project->name];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByResponsibleNull()
    {
        $collection = $this->repository->getCollection(0, StateRepository::MAX_LIMIT, null, [
            State::JSON_RESPONSIBLE => null,
        ], [
            State::JSON_PROJECT => StateRepository::SORT_ASC,
            State::JSON_NAME    => StateRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->total);
        self::assertCount(0, $collection->data);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryOrder
     */
    public function testGetCollectionSortByProject()
    {
        $expected = [
            ['Assigned',   'Distinctio'],
            ['Completed',  'Distinctio'],
            ['Duplicated', 'Distinctio'],
            ['New',        'Distinctio'],
            ['Opened',     'Distinctio'],
            ['Resolved',   'Distinctio'],
            ['Submitted',  'Distinctio'],
            ['Assigned',   'Excepturi'],
            ['Completed',  'Excepturi'],
            ['Duplicated', 'Excepturi'],
        ];

        $collection = $this->repository->getCollection(0, 10, null, [], [
            State::JSON_PROJECT  => StateRepository::SORT_ASC,
            State::JSON_TEMPLATE => StateRepository::SORT_ASC,
            State::JSON_NAME     => StateRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(9, $collection->to);
        self::assertSame(28, $collection->total);

        $actual = array_map(function (State $state) {
            return [$state->name, $state->template->project->name];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryOrder
     */
    public function testGetCollectionSortByTemplate()
    {
        $expected = [
            ['Assigned',   'Distinctio'],
            ['Assigned',   'Excepturi'],
            ['Assigned',   'Molestiae'],
            ['Assigned',   'Presto'],
            ['Completed',  'Distinctio'],
            ['Completed',  'Excepturi'],
            ['Completed',  'Molestiae'],
            ['Completed',  'Presto'],
            ['Duplicated', 'Distinctio'],
            ['Duplicated', 'Excepturi'],
        ];

        $collection = $this->repository->getCollection(0, 10, null, [], [
            State::JSON_TEMPLATE => StateRepository::SORT_ASC,
            State::JSON_NAME     => StateRepository::SORT_ASC,
            State::JSON_PROJECT  => StateRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(9, $collection->to);
        self::assertSame(28, $collection->total);

        $actual = array_map(function (State $state) {
            return [$state->name, $state->template->project->name];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryOrder
     */
    public function testGetCollectionSortByName()
    {
        $expected = [
            ['Assigned',   'Distinctio'],
            ['Assigned',   'Excepturi'],
            ['Assigned',   'Molestiae'],
            ['Assigned',   'Presto'],
            ['Completed',  'Distinctio'],
            ['Completed',  'Excepturi'],
            ['Completed',  'Molestiae'],
            ['Completed',  'Presto'],
            ['Duplicated', 'Distinctio'],
            ['Duplicated', 'Excepturi'],
        ];

        $collection = $this->repository->getCollection(0, 10, null, [], [
            State::JSON_NAME     => StateRepository::SORT_ASC,
            State::JSON_PROJECT  => StateRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(9, $collection->to);
        self::assertSame(28, $collection->total);

        $actual = array_map(function (State $state) {
            return [$state->name, $state->template->project->name];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryOrder
     */
    public function testGetCollectionSortByType()
    {
        $expected = [
            ['Completed',  'Distinctio'],
            ['Completed',  'Excepturi'],
            ['Completed',  'Molestiae'],
            ['Duplicated', 'Distinctio'],
            ['Duplicated', 'Excepturi'],
            ['Duplicated', 'Molestiae'],
            ['Resolved',   'Distinctio'],
            ['Resolved',   'Excepturi'],
            ['Resolved',   'Molestiae'],
            ['New',        'Distinctio'],
        ];

        $collection = $this->repository->getCollection(0, 10, null, [], [
            State::JSON_TYPE    => StateRepository::SORT_ASC,
            State::JSON_NAME    => StateRepository::SORT_ASC,
            State::JSON_PROJECT => StateRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(9, $collection->to);
        self::assertSame(28, $collection->total);

        $actual = array_map(function (State $state) {
            return [$state->name, $state->template->project->name];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryOrder
     */
    public function testGetCollectionSortByResponsible()
    {
        $expected = [
            ['Assigned',  'Distinctio'],
            ['Assigned',  'Excepturi'],
            ['Assigned',  'Molestiae'],
            ['Assigned',  'Presto'],
            ['Opened',    'Distinctio'],
            ['Opened',    'Excepturi'],
            ['Opened',    'Molestiae'],
            ['Opened',    'Presto'],
            ['Submitted', 'Distinctio'],
            ['Submitted', 'Excepturi'],
        ];

        $collection = $this->repository->getCollection(0, 10, null, [], [
            State::JSON_RESPONSIBLE => StateRepository::SORT_ASC,
            State::JSON_NAME        => StateRepository::SORT_ASC,
            State::JSON_PROJECT     => StateRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(9, $collection->to);
        self::assertSame(28, $collection->total);

        $actual = array_map(function (State $state) {
            return [$state->name, $state->template->project->name];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }
}
