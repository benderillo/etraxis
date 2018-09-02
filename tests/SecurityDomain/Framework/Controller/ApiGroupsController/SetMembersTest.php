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

namespace eTraxis\SecurityDomain\Framework\Controller\ApiGroupsController;

use eTraxis\SecurityDomain\Model\Entity\Group;
use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SetMembersTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Managers']);

        $expected = [
            'Berenice O\'Connell',
            'Dangelo Hill',
            'Dorcas Ernser',
            'Leland Doyle',
        ];

        $actual = array_map(function (User $user) {
            return $user->fullname;
        }, $group->members);

        self::assertSame($expected, $actual);

        /** @var User $ldoyle */
        $ldoyle = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var User $dquigley */
        $dquigley = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'dquigley@example.com']);

        $data = [
            'add' => [
                $dquigley->id,
            ],
            'remove' => [
                $ldoyle->id,
            ],
        ];

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/groups/%s/members', $group->id);

        $response = $this->json(Request::METHOD_PATCH, $uri, $data);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->doctrine->getManager()->refresh($group);

        $expected = [
            'Berenice O\'Connell',
            'Dangelo Hill',
            'Dennis Quigley',
            'Dorcas Ernser',
        ];

        $actual = array_map(function (User $user) {
            return $user->fullname;
        }, $group->members);

        self::assertSame($expected, $actual);
    }

    public function test400()
    {
        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Managers']);

        $data = [
            'add' => [
                'foo',
            ],
            'remove' => [
                'bar',
            ],
        ];

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/groups/%s/members', $group->id);

        $response = $this->json(Request::METHOD_PATCH, $uri, $data);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function test401()
    {
        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Managers']);

        /** @var User $ldoyle */
        $ldoyle = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var User $dquigley */
        $dquigley = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'dquigley@example.com']);

        $data = [
            'add' => [
                $dquigley->id,
            ],
            'remove' => [
                $ldoyle->id,
            ],
        ];

        $uri = sprintf('/api/groups/%s/members', $group->id);

        $response = $this->json(Request::METHOD_PATCH, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Managers']);

        /** @var User $ldoyle */
        $ldoyle = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var User $dquigley */
        $dquigley = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'dquigley@example.com']);

        $data = [
            'add' => [
                $dquigley->id,
            ],
            'remove' => [
                $ldoyle->id,
            ],
        ];

        $this->loginAs('artem@example.com');

        $uri = sprintf('/api/groups/%s/members', $group->id);

        $response = $this->json(Request::METHOD_PATCH, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test404()
    {
        /** @var User $ldoyle */
        $ldoyle = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'ldoyle@example.com']);

        /** @var User $dquigley */
        $dquigley = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'dquigley@example.com']);

        $data = [
            'add' => [
                $dquigley->id,
            ],
            'remove' => [
                $ldoyle->id,
            ],
        ];

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/groups/%s/members', self::UNKNOWN_ENTITY_ID);

        $response = $this->json(Request::METHOD_PATCH, $uri, $data);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
