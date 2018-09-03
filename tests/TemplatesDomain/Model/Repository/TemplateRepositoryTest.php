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
use eTraxis\TemplatesDomain\Model\Entity\Template;
use eTraxis\Tests\WebTestCase;

class TemplateRepositoryTest extends WebTestCase
{
    /** @var TemplateRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Template::class);
    }

    public function testRepository()
    {
        self::assertInstanceOf(TemplateRepository::class, $this->repository);
    }

    public function testGetCollectionDefault()
    {
        $collection = $this->repository->getCollection();

        self::assertSame(0, $collection->from);
        self::assertSame(7, $collection->to);
        self::assertSame(8, $collection->total);

        $expected = array_map(function (Template $template) {
            return [$template->name, $template->description];
        }, $this->repository->findAll());

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
        }, $collection->data);

        sort($expected);
        sort($actual);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionOffset()
    {
        $expected = [
            ['Support', 'Support Request B'],
            ['Support', 'Support Request C'],
            ['Support', 'Support Request D'],
        ];

        $collection = $this->repository->getCollection(5, TemplateRepository::MAX_LIMIT, null, [], [
            Template::JSON_NAME        => TemplateRepository::SORT_ASC,
            Template::JSON_DESCRIPTION => TemplateRepository::SORT_ASC,
        ]);

        self::assertSame(5, $collection->from);
        self::assertSame(7, $collection->to);
        self::assertSame(8, $collection->total);

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionLimit()
    {
        $expected = [
            ['Development', 'Development Task A'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task C'],
            ['Development', 'Development Task D'],
            ['Support',     'Support Request A'],
        ];

        $collection = $this->repository->getCollection(0, 5, null, [], [
            Template::JSON_NAME        => TemplateRepository::SORT_ASC,
            Template::JSON_DESCRIPTION => TemplateRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(4, $collection->to);
        self::assertSame(8, $collection->total);

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionSearch()
    {
        $expected = [
            ['Development', 'Development Task A'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task C'],
            ['Development', 'Development Task D'],
            ['Support',     'Support Request D'],
        ];

        $collection = $this->repository->getCollection(0, TemplateRepository::MAX_LIMIT, 'd', [], [
            Template::JSON_NAME        => TemplateRepository::SORT_ASC,
            Template::JSON_DESCRIPTION => TemplateRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(4, $collection->to);
        self::assertSame(5, $collection->total);

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionFilterByProject()
    {
        $expected = [
            ['Development', 'Development Task A'],
            ['Support',     'Support Request A'],
        ];

        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $collection = $this->repository->getCollection(0, TemplateRepository::MAX_LIMIT, null, [
            Template::JSON_PROJECT => $project->id,
        ], [
            Template::JSON_NAME        => TemplateRepository::SORT_ASC,
            Template::JSON_DESCRIPTION => TemplateRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(1, $collection->to);
        self::assertSame(2, $collection->total);

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionFilterByName()
    {
        $expected = [
            ['Development', 'Development Task A'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task C'],
            ['Development', 'Development Task D'],
        ];

        $collection = $this->repository->getCollection(0, TemplateRepository::MAX_LIMIT, null, [
            Template::JSON_NAME => 'eNT',
        ], [
            Template::JSON_NAME        => TemplateRepository::SORT_ASC,
            Template::JSON_DESCRIPTION => TemplateRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(3, $collection->to);
        self::assertSame(4, $collection->total);

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionFilterByPrefix()
    {
        $expected = [
            ['Support', 'Support Request A'],
            ['Support', 'Support Request B'],
            ['Support', 'Support Request C'],
            ['Support', 'Support Request D'],
        ];

        $collection = $this->repository->getCollection(0, TemplateRepository::MAX_LIMIT, null, [
            Template::JSON_PREFIX => 'rEQ',
        ], [
            Template::JSON_NAME        => TemplateRepository::SORT_ASC,
            Template::JSON_DESCRIPTION => TemplateRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(3, $collection->to);
        self::assertSame(4, $collection->total);

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionFilterByDescription()
    {
        $expected = [
            ['Development', 'Development Task D'],
            ['Support',     'Support Request D'],
        ];

        $collection = $this->repository->getCollection(0, TemplateRepository::MAX_LIMIT, null, [
            Template::JSON_DESCRIPTION => ' d',
        ], [
            Template::JSON_NAME        => TemplateRepository::SORT_ASC,
            Template::JSON_DESCRIPTION => TemplateRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(1, $collection->to);
        self::assertSame(2, $collection->total);

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionFilterByCriticalAge()
    {
        $expected = [
            ['Support', 'Support Request A'],
            ['Support', 'Support Request B'],
            ['Support', 'Support Request C'],
            ['Support', 'Support Request D'],
        ];

        $collection = $this->repository->getCollection(0, TemplateRepository::MAX_LIMIT, null, [
            Template::JSON_CRITICAL => 3,
        ], [
            Template::JSON_NAME        => TemplateRepository::SORT_ASC,
            Template::JSON_DESCRIPTION => TemplateRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(3, $collection->to);
        self::assertSame(4, $collection->total);

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionFilterByFrozenTime()
    {
        $expected = [
            ['Support', 'Support Request A'],
            ['Support', 'Support Request B'],
            ['Support', 'Support Request C'],
            ['Support', 'Support Request D'],
        ];

        $collection = $this->repository->getCollection(0, TemplateRepository::MAX_LIMIT, null, [
            Template::JSON_FROZEN => 7,
        ], [
            Template::JSON_NAME        => TemplateRepository::SORT_ASC,
            Template::JSON_DESCRIPTION => TemplateRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(3, $collection->to);
        self::assertSame(4, $collection->total);

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionFilterByLocked()
    {
        $expected = [
            ['Development', 'Development Task B'],
            ['Development', 'Development Task D'],
            ['Support',     'Support Request A'],
            ['Support',     'Support Request B'],
        ];

        $collection = $this->repository->getCollection(0, TemplateRepository::MAX_LIMIT, null, [
            Template::JSON_LOCKED => true,
        ], [
            Template::JSON_NAME        => TemplateRepository::SORT_ASC,
            Template::JSON_DESCRIPTION => TemplateRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(3, $collection->to);
        self::assertSame(4, $collection->total);

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionSort()
    {
        $expected = [
            ['Support',     'Support Request A'],
            ['Support',     'Support Request B'],
            ['Support',     'Support Request C'],
            ['Support',     'Support Request D'],
            ['Development', 'Development Task A'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task C'],
            ['Development', 'Development Task D'],
        ];

        $collection = $this->repository->getCollection(0, TemplateRepository::MAX_LIMIT, null, [], [
            Template::JSON_NAME        => TemplateRepository::SORT_DESC,
            Template::JSON_DESCRIPTION => TemplateRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(7, $collection->to);
        self::assertSame(8, $collection->total);

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }
}
