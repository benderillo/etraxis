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

class StateRepositoryTest extends WebTestCase
{
    /** @var StateRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(State::class);
    }

    public function testRepository()
    {
        self::assertInstanceOf(StateRepository::class, $this->repository);
    }

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

    public function testGetCollectionSort()
    {
        $expected = [
            ['Opened',     'Presto'],
            ['Resolved',   'Presto'],
            ['Submitted',  'Presto'],
            ['Assigned',   'Presto'],
            ['Completed',  'Presto'],
            ['Duplicated', 'Presto'],
            ['New',        'Presto'],
            ['Opened',     'Molestiae'],
            ['Resolved',   'Molestiae'],
            ['Submitted',  'Molestiae'],
        ];

        $collection = $this->repository->getCollection(0, 10, null, [], [
            State::JSON_PROJECT  => StateRepository::SORT_DESC,
            State::JSON_TEMPLATE => StateRepository::SORT_DESC,
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
}
