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
        $expected = [
            'eTraxis Admin',
            'Artem Rodygin',
            'Albert Einstein',
            'Ted Berge',
            'Joe Gutmann',
            'Lucas O\'Connell',
            'Carson Legros',
            'Jeramy Mueller',
            'Derrick Tillman',
            'Hunter Stroman',
            'Alyson Schinner',
            'Denis Murazik',
            'Leland Doyle',
            'Dorcas Ernser',
            'Berenice O\'Connell',
            'Carolyn Hill',
            'Dangelo Hill',
            'Emmanuelle Bartell',
            'Juanita Goodwin',
            'Francesca Dooley',
            'Lola Abshire',
            'Dennis Quigley',
            'Ansel Koepp',
            'Christy McDermott',
            'Anissa Marvin',
            'Millie Bogisich',
            'Tracy Marquardt',
            'Bell Kemmer',
            'Carter Batz',
            'Kailyn Bahringer',
            'Kyla Schultz',
            'Vida Parker',
            'Tony Buckridge',
            'Nikko Hills',
            'Jarrell Kiehn',
        ];

        $collection = $this->repository->getCollection();

        self::assertSame(0, $collection->from);
        self::assertSame(34, $collection->to);
        self::assertSame(35, $collection->total);

        $actual = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

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

        $collection = $this->repository->getCollection(30, 10, null, [], [
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

        $collection = $this->repository->getCollection(0, 25, 'mAn', [], [
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

        $collection = $this->repository->getCollection(0, 25, '', [
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

        $collection = $this->repository->getCollection(0, 25, '', [
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

        $collection = $this->repository->getCollection(0, 25, '', [
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

        $collection = $this->repository->getCollection(0, 25, '', [
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

        $collection = $this->repository->getCollection(0, 25, '', [
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

        $collection = $this->repository->getCollection(0, 25, '', [
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
            'Vida Parker',
            'Tracy Marquardt',
            'Tony Buckridge',
            'Ted Berge',
            'Nikko Hills',
            'Millie Bogisich',
            'Lucas O\'Connell',
            'Lola Abshire',
            'Leland Doyle',
            'Kyla Schultz',
            'Kailyn Bahringer',
            'Juanita Goodwin',
            //'Joe Gutmann',    <- this one is locked
            'Jeramy Mueller',
            'Jarrell Kiehn',
            'Hunter Stroman',
            'Francesca Dooley',
            'eTraxis Admin',
            'Emmanuelle Bartell',
            'Dorcas Ernser',
            'Derrick Tillman',
            'Dennis Quigley',
            'Denis Murazik',
            'Dangelo Hill',
            'Christy McDermott',
            'Carter Batz',
        ];

        $collection = $this->repository->getCollection(0, 25, '', [
            User::JSON_LOCKED => false,
        ], [
            User::JSON_FULLNAME => UserRepository::SORT_DESC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(24, $collection->to);
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

        $collection = $this->repository->getCollection(0, 25, '', [
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

        $collection = $this->repository->getCollection(0, 25, '', [
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
            'Artem Rodygin',
            'Albert Einstein',
            'eTraxis Admin',
            'Hunter Stroman',
            'Carson Legros',
            'Lucas O\'Connell',
            'Jeramy Mueller',
            'Alyson Schinner',
            'Derrick Tillman',
            'Denis Murazik',
            'Christy McDermott',
            'Lola Abshire',
            'Francesca Dooley',
            'Dennis Quigley',
            'Anissa Marvin',
            'Ansel Koepp',
            'Millie Bogisich',
            'Ted Berge',
            'Joe Gutmann',
            'Dangelo Hill',
            'Dorcas Ernser',
            'Leland Doyle',
            'Berenice O\'Connell',
            'Emmanuelle Bartell',
            'Carolyn Hill',
        ];

        $collection = $this->repository->getCollection(0, 25, '', [], [
            User::JSON_DESCRIPTION => UserRepository::SORT_ASC,
            User::JSON_FULLNAME    => UserRepository::SORT_DESC,
        ]);

        self::assertSame(0, $collection->from);
        self::assertSame(24, $collection->to);
        self::assertSame(35, $collection->total);

        $actual = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        self::assertSame($expected, $actual);
    }
}
