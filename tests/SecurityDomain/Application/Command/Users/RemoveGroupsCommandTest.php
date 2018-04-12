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

class RemoveGroupsCommandTest extends TransactionalTestCase
{
    /** @var \eTraxis\SecurityDomain\Model\Repository\UserRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(User::class);
    }

    public function testSuccess()
    {
        $before = [
            'Company Staff',
            'Developers A',
            'Developers B',
        ];

        $after = [
            'Company Staff',
            'Developers B',
        ];

        $this->loginAs('admin@example.com');

        /** @var \eTraxis\SecurityDomain\Model\Repository\GroupRepository $groupRepository */
        $groupRepository = $this->doctrine->getRepository(Group::class);

        /** @var Group $devA */
        /** @var Group $devC */
        $devA = $groupRepository->findOneBy(['description' => 'Developers A']);
        $devC = $groupRepository->findOneBy(['description' => 'Developers C']);

        /** @var User $user */
        $user = $this->repository->findOneByUsername('labshire@example.com');

        $groups = array_map(function (Group $group) {
            return $group->description ?? $group->name;
        }, $user->groups);

        sort($groups);
        self::assertSame($before, $groups);

        $command = new RemoveGroupsCommand([
            'user'   => $user->id,
            'groups' => [
                $devA->id,
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

        /** @var Group $devA */
        $devA = $this->doctrine->getRepository(Group::class)->findOneBy(['description' => 'Developers A']);

        /** @var User $user */
        $user = $this->repository->findOneByUsername('labshire@example.com');

        $command = new RemoveGroupsCommand([
            'user'   => $user->id,
            'groups' => [
                $devA->id,
            ],
        ]);

        $this->commandbus->handle($command);
    }

    public function testUnknownUser()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var Group $devA */
        $devA = $this->doctrine->getRepository(Group::class)->findOneBy(['description' => 'Developers A']);

        $command = new RemoveGroupsCommand([
            'user'   => self::UNKNOWN_ENTITY_ID,
            'groups' => [
                $devA->id,
            ],
        ]);

        $this->commandbus->handle($command);
    }
}
