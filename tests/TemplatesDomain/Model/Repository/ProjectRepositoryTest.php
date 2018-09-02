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

use eTraxis\TemplatesDomain\Model\Entity\Project;
use eTraxis\Tests\WebTestCase;

class ProjectRepositoryTest extends WebTestCase
{
    /** @var ProjectRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Project::class);
    }

    public function testRepository()
    {
        self::assertInstanceOf(ProjectRepository::class, $this->repository);
    }

    public function testGetCollectionDefault()
    {
        $expected = [
            'Distinctio',
            'Molestiae',
            'Excepturi',
            'Presto',
        ];

        $collection = $this->repository->getCollection();

        self::assertSame(0, $collection->from);
        self::assertSame(3, $collection->to);
        self::assertSame(4, $collection->total);

        $actual = array_map(function (Project $project) {
            return $project->name;
        }, $collection->data);

        sort($expected);
        sort($actual);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionOffset()
    {
        $expected = [
            'Molestiae',
            'Presto',
        ];

        $collection = $this->repository->getCollection(2, ProjectRepository::MAX_LIMIT, null, [], [
            Project::JSON_NAME => ProjectRepository::SORT_ASC,
        ]);

        self::assertSame(2, $collection->from);
        self::assertSame(3, $collection->to);
        self::assertSame(4, $collection->total);

        $actual = array_map(function (Project $project) {
            return $project->name;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionLimit()
    {
        $expected = [
            'Distinctio',
            'Excepturi',
            'Molestiae',
        ];

        $collection = $this->repository->getCollection(0, 3, null, [], [
            Project::JSON_NAME => ProjectRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(2, $collection->to);
        self::assertSame(4, $collection->total);

        $actual = array_map(function (Project $project) {
            return $project->name;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionSearch()
    {
        $expected = [
            'Molestiae',
            'Presto',
        ];

        $collection = $this->repository->getCollection(0, ProjectRepository::MAX_LIMIT, 'eSt', [], [
            Project::JSON_NAME => ProjectRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(1, $collection->to);
        self::assertSame(2, $collection->total);

        $actual = array_map(function (Project $project) {
            return $project->name;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionFilterByName()
    {
        $expected = [
            'Distinctio',
            'Molestiae',
        ];

        $collection = $this->repository->getCollection(0, ProjectRepository::MAX_LIMIT, '', [
            Project::JSON_NAME => 'Ti',
        ], [
            Project::JSON_NAME => ProjectRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(1, $collection->to);
        self::assertSame(2, $collection->total);

        $actual = array_map(function (Project $project) {
            return $project->name;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionFilterByDescription()
    {
        $expected = [
            'Presto',
        ];

        $collection = $this->repository->getCollection(0, ProjectRepository::MAX_LIMIT, '', [
            Project::JSON_DESCRIPTION => ' d',
        ], [
            Project::JSON_NAME => ProjectRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(0, $collection->to);
        self::assertSame(1, $collection->total);

        $actual = array_map(function (Project $project) {
            return $project->name;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionFilterBySuspended()
    {
        $expected = [
            'Excepturi',
            'Molestiae',
            'Presto',
        ];

        $collection = $this->repository->getCollection(0, ProjectRepository::MAX_LIMIT, '', [
            Project::JSON_SUSPENDED => false,
        ], [
            Project::JSON_NAME => ProjectRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(2, $collection->to);
        self::assertSame(3, $collection->total);

        $actual = array_map(function (Project $project) {
            return $project->name;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionCombinedFilter()
    {
        $expected = [
            'Excepturi',
            'Presto',
        ];

        $collection = $this->repository->getCollection(0, ProjectRepository::MAX_LIMIT, '', [
            Project::JSON_NAME      => 'R',
            Project::JSON_SUSPENDED => false,
        ], [
            Project::JSON_NAME => ProjectRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(1, $collection->to);
        self::assertSame(2, $collection->total);

        $actual = array_map(function (Project $project) {
            return $project->name;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionSort()
    {
        $expected = [
            'Excepturi',
            'Molestiae',
            'Presto',
            'Distinctio',
        ];

        $collection = $this->repository->getCollection(0, ProjectRepository::MAX_LIMIT, '', [], [
            Project::JSON_SUSPENDED => ProjectRepository::SORT_ASC,
            Project::JSON_NAME      => ProjectRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(3, $collection->to);
        self::assertSame(4, $collection->total);

        $actual = array_map(function (Project $project) {
            return $project->name;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }
}
