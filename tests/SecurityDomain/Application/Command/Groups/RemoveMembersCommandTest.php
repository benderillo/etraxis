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

namespace eTraxis\SecurityDomain\Application\Command\Groups;

use eTraxis\SecurityDomain\Model\Entity\Group;
use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RemoveMembersCommandTest extends TransactionalTestCase
{
    /** @var \eTraxis\SecurityDomain\Model\Repository\GroupRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Group::class);
    }

    public function testSuccess()
    {
        $this->loginAs('admin@example.com');

        $before = [
            'christy.mcdermott@example.com',
            'dquigley@example.com',
            'fdooley@example.com',
            'labshire@example.com',
        ];

        $after = [
            'christy.mcdermott@example.com',
            'dquigley@example.com',
            'labshire@example.com',
        ];

        /** @var \eTraxis\SecurityDomain\Model\Repository\UserRepository $userRepository */
        $userRepository = $this->doctrine->getRepository(User::class);

        /** @var User $fdooley */
        /** @var User $nhills */
        $fdooley = $userRepository->findOneByUsername('fdooley@example.com');
        $nhills  = $userRepository->findOneByUsername('nhills@example.com');

        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $members = array_map(function (User $user) {
            return $user->email;
        }, $group->members);

        sort($members);
        self::assertSame($before, $members);

        $command = new RemoveMembersCommand([
            'group' => $group->id,
            'users' => [
                $fdooley->id,
                $nhills->id,
            ],
        ]);

        $this->commandbus->handle($command);

        /** @var Group $group */
        $group = $this->repository->find($group->id);

        $members = array_map(function (User $user) {
            return $user->email;
        }, $group->members);

        sort($members);
        self::assertSame($after, $members);
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var User $fdooley */
        $fdooley = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'fdooley@example.com']);

        /** @var Group $group */
        [$group] = $this->repository->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        $command = new RemoveMembersCommand([
            'group' => $group->id,
            'users' => [
                $fdooley->id,
            ],
        ]);

        $this->commandbus->handle($command);
    }

    public function testUnknownGroup()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var User $fdooley */
        $fdooley = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'fdooley@example.com']);

        $command = new RemoveMembersCommand([
            'group' => self::UNKNOWN_ENTITY_ID,
            'users' => [
                $fdooley->id,
            ],
        ]);

        $this->commandbus->handle($command);
    }
}
