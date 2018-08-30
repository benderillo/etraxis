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

namespace eTraxis\SharedDomain\Model\Collection;

use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\Tests\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

class CollectionTraitTest extends WebTestCase
{
    use CollectionTrait;

    /** @var \eTraxis\SecurityDomain\Model\Repository\UserRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(User::class);
    }

    public function testDefaults()
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

        $request = new Request();
        $request->setMethod(Request::METHOD_GET);

        $collection = $this->getCollection($request, $this->repository);

        $actual  = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        self::assertSame(0, $collection->from);
        self::assertSame(34, $collection->to);
        self::assertSame(35, $collection->total);

        self::assertSame($expected, $actual);
    }

    public function testOffset()
    {
        $expected = [
            'Kyla Schultz',
            'Vida Parker',
            'Tony Buckridge',
            'Nikko Hills',
            'Jarrell Kiehn',
        ];

        $request = new Request(['offset' => 30]);
        $request->setMethod(Request::METHOD_GET);

        $collection = $this->getCollection($request, $this->repository);

        $actual  = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        self::assertSame(30, $collection->from);
        self::assertSame(34, $collection->to);
        self::assertSame(35, $collection->total);

        self::assertSame($expected, $actual);
    }

    public function testOffsetNegative()
    {
        $request = new Request(['offset' => PHP_INT_MIN]);
        $request->setMethod(Request::METHOD_GET);

        $collection = $this->getCollection($request, $this->repository);

        self::assertCount(35, $collection->data);

        self::assertSame(0, $collection->from);
        self::assertSame(34, $collection->to);
        self::assertSame(35, $collection->total);
    }

    public function testOffsetHuge()
    {
        $request = new Request(['offset' => PHP_INT_MAX]);
        $request->setMethod(Request::METHOD_GET);

        $collection = $this->getCollection($request, $this->repository);

        self::assertCount(0, $collection->data);

        self::assertSame(PHP_INT_MAX, $collection->from);
        self::assertSame(35, $collection->total);
    }

    public function testLimit()
    {
        $expected = [
            'eTraxis Admin',
            'Artem Rodygin',
            'Albert Einstein',
            'Ted Berge',
            'Joe Gutmann',
        ];

        $request = new Request(['limit' => 5]);
        $request->setMethod(Request::METHOD_GET);

        $collection = $this->getCollection($request, $this->repository);

        $actual  = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        self::assertSame(0, $collection->from);
        self::assertSame(4, $collection->to);
        self::assertSame(35, $collection->total);

        self::assertSame($expected, $actual);
    }

    public function testLimitNegative()
    {
        $request = new Request(['limit' => PHP_INT_MIN]);
        $request->setMethod(Request::METHOD_GET);

        $collection = $this->getCollection($request, $this->repository);

        self::assertCount(1, $collection->data);

        self::assertSame(0, $collection->from);
        self::assertSame(0, $collection->to);
        self::assertSame(35, $collection->total);
    }

    public function testLimitHuge()
    {
        $request = new Request(['limit' => PHP_INT_MAX]);
        $request->setMethod(Request::METHOD_GET);

        $collection = $this->getCollection($request, $this->repository);

        self::assertCount(35, $collection->data);

        self::assertSame(0, $collection->from);
        self::assertSame(34, $collection->to);
        self::assertSame(35, $collection->total);
    }

    public function testSearch()
    {
        $expected = [
            'Joe Gutmann',
            'Derrick Tillman',
            'Hunter Stroman',
            'Leland Doyle',
            'Dorcas Ernser',
            'Berenice O\'Connell',
            'Carolyn Hill',
            'Dangelo Hill',
            'Emmanuelle Bartell',
            'Juanita Goodwin',
            'Jarrell Kiehn',
        ];

        $request = new Request([], [], [], [], [], ['HTTP_X-Search' => 'mAn']);
        $request->setMethod(Request::METHOD_GET);

        $collection = $this->getCollection($request, $this->repository);

        $actual  = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        self::assertSame(0, $collection->from);
        self::assertSame(10, $collection->to);
        self::assertSame(11, $collection->total);

        self::assertSame($expected, $actual);
    }

    public function testFilter()
    {
        $expected = [
            'Jeramy Mueller',
            'Dorcas Ernser',
            'Berenice O\'Connell',
            'Bell Kemmer',
        ];

        $filter = [
            User::JSON_EMAIL       => 'eR',
            User::JSON_DESCRIPTION => 'a+',
        ];

        $request = new Request([], [], [], [], [], ['HTTP_X-Filter' => json_encode($filter)]);
        $request->setMethod(Request::METHOD_GET);

        $collection = $this->getCollection($request, $this->repository);

        $actual  = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        self::assertSame(0, $collection->from);
        self::assertSame(3, $collection->to);
        self::assertSame(4, $collection->total);

        self::assertSame($expected, $actual);
    }

    public function testSort()
    {
        $expected = [
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
            'Joe Gutmann',
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
            'Albert Einstein',
        ];

        $sort = [
            User::JSON_PROVIDER => 'ASC',
            User::JSON_FULLNAME => 'ASC',
        ];

        $request = new Request([], [], [], [], [], ['HTTP_X-Sort' => json_encode($sort)]);
        $request->setMethod(Request::METHOD_GET);

        $collection = $this->getCollection($request, $this->repository);

        $actual  = array_map(function (User $user) {
            return $user->fullname;
        }, $collection->data);

        self::assertSame(0, $collection->from);
        self::assertSame(34, $collection->to);
        self::assertSame(35, $collection->total);

        self::assertSame($expected, $actual);
    }
}
