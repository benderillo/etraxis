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

namespace eTraxis\SecurityDomain\Application\Command\Users;

use eTraxis\SecurityDomain\Model\Entity\Group;
use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AddGroupsCommandTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $before = [
            'Company Staff',
            'Developers A',
            'Developers B',
        ];

        $after = [
            'Company Staff',
            'Developers A',
            'Developers B',
            'Developers C',
        ];

        $this->loginAs('admin@example.com');

        /** @var \eTraxis\SecurityDomain\Model\Repository\GroupRepository $groupRepository */
        $groupRepository = $this->doctrine->getRepository(Group::class);

        /** @var Group $devB */
        /** @var Group $devC */
        $devB = $groupRepository->findOneBy(['description' => 'Developers B']);
        $devC = $groupRepository->findOneBy(['description' => 'Developers C']);

        /** @var \eTraxis\SecurityDomain\Model\Repository\UserRepository $repository */
        $repository = $this->doctrine->getRepository(User::class);

        /** @var User $user */
        $user = $repository->findOneByUsername('labshire@example.com');

        $groups = array_map(function (Group $group) {
            return $group->description ?? $group->name;
        }, $user->groups);

        sort($groups);
        self::assertSame($before, $groups);

        $command = new AddGroupsCommand([
            'id'     => $user->id,
            'groups' => [
                $devB->id,
                $devC->id,
            ],
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->refresh($user);

        $groups = array_map(function (Group $group) {
            return $group->description ?? $group->name;
        }, $user->groups);

        sort($groups);
        self::assertSame($after, $groups);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var Group $devC */
        $devC = $this->doctrine->getRepository(Group::class)->findOneBy(['description' => 'Developers C']);

        /** @var \eTraxis\SecurityDomain\Model\Repository\UserRepository $repository */
        $repository = $this->doctrine->getRepository(User::class);

        /** @var User $user */
        $user = $repository->findOneByUsername('labshire@example.com');

        $command = new AddGroupsCommand([
            'id'     => $user->id,
            'groups' => [
                $devC->id,
            ],
        ]);

        $this->commandbus->handle($command);
    }

    public function testUnknownUser()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var Group $devC */
        $devC = $this->doctrine->getRepository(Group::class)->findOneBy(['description' => 'Developers C']);

        $command = new AddGroupsCommand([
            'id'     => self::UNKNOWN_ENTITY_ID,
            'groups' => [
                $devC->id,
            ],
        ]);

        $this->commandbus->handle($command);
    }
}
