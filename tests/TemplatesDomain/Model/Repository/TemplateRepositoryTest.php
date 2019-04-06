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

use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\TemplatesDomain\Model\Entity\Project;
use eTraxis\TemplatesDomain\Model\Entity\Template;
use eTraxis\Tests\WebTestCase;

/**
 * @coversDefaultClass \eTraxis\TemplatesDomain\Model\Repository\TemplateRepository
 */
class TemplateRepositoryTest extends WebTestCase
{
    /** @var TemplateRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Template::class);
    }

    /**
     * @covers ::__construct
     */
    public function testRepository()
    {
        self::assertInstanceOf(TemplateRepository::class, $this->repository);
    }

    /**
     * @covers ::getTemplatesByUser
     */
    public function testGetTemplatesByUser()
    {
        /** @var User $ldoyle */
        /** @var User $nhills */
        /** @var User $clegros */
        $ldoyle  = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);
        $nhills  = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);
        $clegros = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'clegros@example.com']);

        /** @var Template $taskC */
        /** @var Template $reqC */
        /** @var Template $reqD */
        [/* skipping */, /* skipping */, $taskC]       = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);
        [/* skipping */, /* skipping */, $reqC, $reqD] = $this->repository->findBy(['name' => 'Support'], ['id' => 'ASC']);

        self::assertSame([$taskC, $reqC, $reqD], $this->repository->getTemplatesByUser($ldoyle));
        self::assertSame([$taskC], $this->repository->getTemplatesByUser($nhills));
        self::assertEmpty($this->repository->getTemplatesByUser($clegros));
    }

    /**
     * @covers ::getCollection
     */
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

    /**
     * @covers ::getCollection
     */
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

    /**
     * @covers ::getCollection
     */
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

    /**
     * @covers ::getCollection
     * @covers ::querySearch
     */
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

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
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

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByProjectNull()
    {
        $collection = $this->repository->getCollection(0, TemplateRepository::MAX_LIMIT, null, [
            Template::JSON_PROJECT => null,
        ], [
            Template::JSON_NAME        => TemplateRepository::SORT_ASC,
            Template::JSON_DESCRIPTION => TemplateRepository::SORT_ASC,
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

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByNameNull()
    {
        $collection = $this->repository->getCollection(0, TemplateRepository::MAX_LIMIT, null, [
            Template::JSON_NAME => null,
        ], [
            Template::JSON_NAME        => TemplateRepository::SORT_ASC,
            Template::JSON_DESCRIPTION => TemplateRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->total);
        self::assertCount(0, $collection->data);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
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

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByPrefixNull()
    {
        $collection = $this->repository->getCollection(0, TemplateRepository::MAX_LIMIT, null, [
            Template::JSON_PREFIX => null,
        ], [
            Template::JSON_NAME        => TemplateRepository::SORT_ASC,
            Template::JSON_DESCRIPTION => TemplateRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->total);
        self::assertCount(0, $collection->data);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
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

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByDescriptionNull()
    {
        $collection = $this->repository->getCollection(0, TemplateRepository::MAX_LIMIT, null, [
            Template::JSON_DESCRIPTION => null,
        ], [
            Template::JSON_NAME        => TemplateRepository::SORT_ASC,
            Template::JSON_DESCRIPTION => TemplateRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->total);
        self::assertCount(0, $collection->data);
    }

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
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

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByCriticalAgeNull()
    {
        $expected = [
            ['Development', 'Development Task A'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task C'],
            ['Development', 'Development Task D'],
        ];

        $collection = $this->repository->getCollection(0, TemplateRepository::MAX_LIMIT, null, [
            Template::JSON_CRITICAL => null,
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

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
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

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
    public function testGetCollectionFilterByFrozenTimeNull()
    {
        $expected = [
            ['Development', 'Development Task A'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task C'],
            ['Development', 'Development Task D'],
        ];

        $collection = $this->repository->getCollection(0, TemplateRepository::MAX_LIMIT, null, [
            Template::JSON_FROZEN => null,
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

    /**
     * @covers ::getCollection
     * @covers ::queryFilter
     */
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

    /**
     * @covers ::getCollection
     * @covers ::queryOrder
     */
    public function testGetCollectionSortByProject()
    {
        $expected = [
            ['Development', 'Development Task A'],
            ['Support',     'Support Request A'],
            ['Development', 'Development Task C'],
            ['Support',     'Support Request C'],
            ['Development', 'Development Task B'],
            ['Support',     'Support Request B'],
            ['Development', 'Development Task D'],
            ['Support',     'Support Request D'],
        ];

        $collection = $this->repository->getCollection(0, TemplateRepository::MAX_LIMIT, null, [], [
            Template::JSON_PROJECT => TemplateRepository::SORT_ASC,
            Template::JSON_NAME    => TemplateRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(7, $collection->to);
        self::assertSame(8, $collection->total);

        $actual = array_map(function (Template $template) {
            return [$template->name, $template->description];
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
            ['Development', 'Development Task A'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task C'],
            ['Development', 'Development Task D'],
            ['Support',     'Support Request A'],
            ['Support',     'Support Request B'],
            ['Support',     'Support Request C'],
            ['Support',     'Support Request D'],
        ];

        $collection = $this->repository->getCollection(0, TemplateRepository::MAX_LIMIT, null, [], [
            Template::JSON_NAME        => TemplateRepository::SORT_ASC,
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

    /**
     * @covers ::getCollection
     * @covers ::queryOrder
     */
    public function testGetCollectionSortByPrefix()
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
            Template::JSON_PREFIX      => TemplateRepository::SORT_ASC,
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

    /**
     * @covers ::getCollection
     * @covers ::queryOrder
     */
    public function testGetCollectionSortByDescription()
    {
        $expected = [
            ['Development', 'Development Task A'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task C'],
            ['Development', 'Development Task D'],
            ['Support',     'Support Request A'],
            ['Support',     'Support Request B'],
            ['Support',     'Support Request C'],
            ['Support',     'Support Request D'],
        ];

        $collection = $this->repository->getCollection(0, TemplateRepository::MAX_LIMIT, null, [], [
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

    /**
     * @covers ::getCollection
     * @covers ::queryOrder
     */
    public function testGetCollectionSortByCritical()
    {
        $expected = [
            ['Development', 'Development Task A'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task C'],
            ['Development', 'Development Task D'],
            ['Support',     'Support Request A'],
            ['Support',     'Support Request B'],
            ['Support',     'Support Request C'],
            ['Support',     'Support Request D'],
        ];

        $collection = $this->repository->getCollection(0, TemplateRepository::MAX_LIMIT, null, [], [
            Template::JSON_CRITICAL    => TemplateRepository::SORT_ASC,
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

    /**
     * @covers ::getCollection
     * @covers ::queryOrder
     */
    public function testGetCollectionSortByFrozen()
    {
        $expected = [
            ['Development', 'Development Task A'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task C'],
            ['Development', 'Development Task D'],
            ['Support',     'Support Request A'],
            ['Support',     'Support Request B'],
            ['Support',     'Support Request C'],
            ['Support',     'Support Request D'],
        ];

        $collection = $this->repository->getCollection(0, TemplateRepository::MAX_LIMIT, null, [], [
            Template::JSON_FROZEN      => TemplateRepository::SORT_ASC,
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

    /**
     * @covers ::getCollection
     * @covers ::queryOrder
     */
    public function testGetCollectionSortByLocked()
    {
        $expected = [
            ['Development', 'Development Task A'],
            ['Development', 'Development Task C'],
            ['Support',     'Support Request C'],
            ['Support',     'Support Request D'],
            ['Development', 'Development Task B'],
            ['Development', 'Development Task D'],
            ['Support',     'Support Request A'],
            ['Support',     'Support Request B'],
        ];

        $collection = $this->repository->getCollection(0, TemplateRepository::MAX_LIMIT, null, [], [
            Template::JSON_LOCKED      => TemplateRepository::SORT_ASC,
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
