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

namespace eTraxis\SecurityDomain\Model\Repository;

use eTraxis\SecurityDomain\Model\Dictionary\AccountProvider;
use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\Tests\WebTestCase;

class UserRepositoryTest extends WebTestCase
{
    /** @var UserRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(User::class);
    }

    public function testRepository()
    {
        self::assertInstanceOf(UserRepository::class, $this->repository);
    }

    public function testFindOneByUsernameSuccess()
    {
        /** @var User $user */
        $user = $this->repository->findOneByUsername('admin@example.com');

        self::assertInstanceOf(User::class, $user);
        self::assertSame('eTraxis Admin', $user->fullname);
    }

    public function testFindOneByUsernameUnknown()
    {
        /** @var User $user */
        $user = $this->repository->findOneByUsername('404@example.com');

        self::assertNull($user);
    }

    public function testGetCollectionDefault()
    {
        $collection = $this->repository->getCollection();

        self::assertSame(0, $collection->from);
        self::assertSame(34, $collection->to);
        self::assertSame(35, $collection->total);

        $expected = array_map(function (User $user) {
            return $user->fullname;
        }, $this->repository->findAll());

        $actual = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        sort($expected);
        sort($actual);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionOffset()
    {
        $expected = [
            'Nikko Hills',
            'Ted Berge',
            'Tony Buckridge',
            'Tracy Marquardt',
            'Vida Parker',
        ];

        $collection = $this->repository->getCollection(30, UserRepository::MAX_LIMIT, null, [], [
            User::JSON_FULLNAME => UserRepository::SORT_ASC,
        ]);

        self::assertSame(30, $collection->from);
        self::assertSame(34, $collection->to);
        self::assertSame(35, $collection->total);

        $actual = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionLimit()
    {
        $expected = [
            'Albert Einstein',
            'Alyson Schinner',
            'Anissa Marvin',
            'Ansel Koepp',
            'Artem Rodygin',
            'Bell Kemmer',
            'Berenice O\'Connell',
            'Carolyn Hill',
            'Carson Legros',
            'Carter Batz',
        ];

        $collection = $this->repository->getCollection(0, 10, null, [], [
            User::JSON_FULLNAME => UserRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(9, $collection->to);
        self::assertSame(35, $collection->total);

        $actual = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionSearch()
    {
        $expected = [
            'Berenice O\'Connell',
            'Carolyn Hill',
            'Dangelo Hill',
            'Derrick Tillman',
            'Dorcas Ernser',
            'Emmanuelle Bartell',
            'Hunter Stroman',
            'Jarrell Kiehn',
            'Joe Gutmann',
            'Juanita Goodwin',
            'Leland Doyle',
        ];

        $collection = $this->repository->getCollection(0, UserRepository::MAX_LIMIT, 'mAn', [], [
            User::JSON_FULLNAME => UserRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(10, $collection->to);
        self::assertSame(11, $collection->total);

        $actual = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionFilterByEmail()
    {
        $expected = [
            'Berenice O\'Connell',
            'Lucas O\'Connell',
        ];

        $collection = $this->repository->getCollection(0, UserRepository::MAX_LIMIT, '', [
            User::JSON_EMAIL => 'oCoNNel',
        ], [
            User::JSON_FULLNAME => UserRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(1, $collection->to);
        self::assertSame(2, $collection->total);

        $actual = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionFilterByFullname()
    {
        $expected = [
            'Berenice O\'Connell',
            'Lucas O\'Connell',
        ];

        $collection = $this->repository->getCollection(0, UserRepository::MAX_LIMIT, '', [
            User::JSON_FULLNAME => 'o\'cONneL',
        ], [
            User::JSON_FULLNAME => UserRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(1, $collection->to);
        self::assertSame(2, $collection->total);

        $actual = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionFilterByDescription()
    {
        $expected = [
            'Bell Kemmer',
            'Carter Batz',
            'Jarrell Kiehn',
            'Kailyn Bahringer',
            'Kyla Schultz',
            'Nikko Hills',
            'Tony Buckridge',
            'Tracy Marquardt',
            'Vida Parker',
        ];

        $collection = $this->repository->getCollection(0, UserRepository::MAX_LIMIT, '', [
            User::JSON_DESCRIPTION => 'sUPpOrT',
        ], [
            User::JSON_FULLNAME => UserRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(8, $collection->to);
        self::assertSame(9, $collection->total);

        $actual = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionFilterByAdmin()
    {
        $expected = [
            'eTraxis Admin',
        ];

        $collection = $this->repository->getCollection(0, UserRepository::MAX_LIMIT, '', [
            User::JSON_ADMIN => User::ROLE_ADMIN,
        ], [
            User::JSON_FULLNAME => UserRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(0, $collection->to);
        self::assertSame(1, $collection->total);

        $actual = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionFilterByDisabled()
    {
        $expected = [
            'Ted Berge',
        ];

        $collection = $this->repository->getCollection(0, UserRepository::MAX_LIMIT, '', [
            User::JSON_DISABLED => true,
        ], [
            User::JSON_FULLNAME => UserRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(0, $collection->to);
        self::assertSame(1, $collection->total);

        $actual = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionFilterByLockedOn()
    {
        $expected = [
            'Joe Gutmann',
        ];

        $collection = $this->repository->getCollection(0, UserRepository::MAX_LIMIT, '', [
            User::JSON_LOCKED => true,
        ], [
            User::JSON_FULLNAME => UserRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(0, $collection->to);
        self::assertSame(1, $collection->total);

        $actual = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionFilterByLockedOff()
    {
        $expected = [
            'Albert Einstein',
            'Alyson Schinner',
            'Anissa Marvin',
            'Ansel Koepp',
            'Artem Rodygin',
            'Bell Kemmer',
            'Berenice O\'Connell',
            'Carolyn Hill',
            'Carson Legros',
            'Carter Batz',
            'Christy McDermott',
            'Dangelo Hill',
            'Denis Murazik',
            'Dennis Quigley',
            'Derrick Tillman',
            'Dorcas Ernser',
            'Emmanuelle Bartell',
            'eTraxis Admin',
            'Francesca Dooley',
            'Hunter Stroman',
            'Jarrell Kiehn',
            'Jeramy Mueller',
            //'Joe Gutmann',    <- this one is locked
            'Juanita Goodwin',
            'Kailyn Bahringer',
            'Kyla Schultz',
            'Leland Doyle',
            'Lola Abshire',
            'Lucas O\'Connell',
            'Millie Bogisich',
            'Nikko Hills',
            'Ted Berge',
            'Tony Buckridge',
            'Tracy Marquardt',
            'Vida Parker',
        ];

        $collection = $this->repository->getCollection(0, UserRepository::MAX_LIMIT, '', [
            User::JSON_LOCKED => false,
        ], [
            User::JSON_FULLNAME => UserRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(33, $collection->to);
        self::assertSame(34, $collection->total);

        $actual = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionFilterByProvider()
    {
        $expected = [
            'Albert Einstein',
        ];

        $collection = $this->repository->getCollection(0, UserRepository::MAX_LIMIT, '', [
            User::JSON_PROVIDER => AccountProvider::LDAP,
        ], [
            User::JSON_FULLNAME => UserRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(0, $collection->to);
        self::assertSame(1, $collection->total);

        $actual = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionCombinedFilter()
    {
        $expected = [
            'Bell Kemmer',
            'Berenice O\'Connell',
            'Dorcas Ernser',
            'Jeramy Mueller',
        ];

        $collection = $this->repository->getCollection(0, UserRepository::MAX_LIMIT, '', [
            User::JSON_EMAIL       => 'eR',
            User::JSON_FULLNAME    => '',
            User::JSON_DESCRIPTION => 'a+',
        ], [
            User::JSON_FULLNAME => UserRepository::SORT_ASC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(3, $collection->to);
        self::assertSame(4, $collection->total);

        $actual = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }

    public function testGetCollectionSort()
    {
        $expected = [
            ['Artem Rodygin',       null],
            ['Albert Einstein',     null],
            ['eTraxis Admin',       'Built-in administrator'],
            ['Hunter Stroman',      'Client A'],
            ['Carson Legros',       'Client A+B'],
            ['Lucas O\'Connell',    'Client A+B+C'],
            ['Jeramy Mueller',      'Client A+C'],
            ['Alyson Schinner',     'Client B'],
            ['Derrick Tillman',     'Client B+C'],
            ['Denis Murazik',       'Client C'],
            ['Christy McDermott',   'Developer A'],
            ['Lola Abshire',        'Developer A+B'],
            ['Francesca Dooley',    'Developer A+B+C'],
            ['Dennis Quigley',      'Developer A+C'],
            ['Anissa Marvin',       'Developer B'],
            ['Ansel Koepp',         'Developer B+C'],
            ['Millie Bogisich',     'Developer C'],
            ['Ted Berge',           'Disabled account'],
            ['Joe Gutmann',         'Locked account'],
            ['Dangelo Hill',        'Manager A'],
            ['Dorcas Ernser',       'Manager A+B'],
            ['Leland Doyle',        'Manager A+B+C+D'],
            ['Berenice O\'Connell', 'Manager A+C'],
            ['Emmanuelle Bartell',  'Manager B'],
            ['Carolyn Hill',        'Manager B+C'],
        ];

        $collection = $this->repository->getCollection(0, 25, '', [], [
            User::JSON_DESCRIPTION => UserRepository::SORT_ASC,
            User::JSON_FULLNAME    => UserRepository::SORT_DESC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(24, $collection->to);
        self::assertSame(35, $collection->total);

        $actual = array_map(function (User $user) {
            return [$user->fullname, $user->description];
        }, $collection->data);

        self::assertSame($expected, $actual);
    }
}
