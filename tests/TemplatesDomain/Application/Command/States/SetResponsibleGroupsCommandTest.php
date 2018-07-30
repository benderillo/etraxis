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

namespace eTraxis\TemplatesDomain\Application\Command\States;

use eTraxis\SecurityDomain\Model\Entity\Group;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\TemplatesDomain\Model\Entity\StateResponsibleGroup;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SetResponsibleGroupsCommandTest extends TransactionalTestCase
{
    /** @var \eTraxis\TemplatesDomain\Model\Repository\StateRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(State::class);
    }

    public function testSuccessAppending()
    {
        $before = [
            'Developers',
        ];

        $after = [
            'Developers',
            'Support Engineers',
        ];

        $this->loginAs('admin@example.com');

        /** @var State $state */
        [/* skipping */, $state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var Group $developers */
        [/* skipping */, $developers] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['id' => 'ASC']);

        /** @var Group $support */
        [/* skipping */, $support] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        self::assertSame($before, $this->responsibleGroupsToArray($state));

        $command = new SetResponsibleGroupsCommand([
            'state'  => $state->id,
            'groups' => [
                $developers->id,
                $support->id,
            ],
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->refresh($state);
        self::assertSame($after, $this->responsibleGroupsToArray($state));
    }

    public function testSuccessReplacing()
    {
        $before = [
            'Developers',
        ];

        $after = [
            'Support Engineers',
        ];

        $this->loginAs('admin@example.com');

        /** @var State $state */
        [/* skipping */, $state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var Group $support */
        [/* skipping */, $support] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        self::assertSame($before, $this->responsibleGroupsToArray($state));

        $command = new SetResponsibleGroupsCommand([
            'state'  => $state->id,
            'groups' => [
                $support->id,
            ],
        ]);

        $this->commandbus->handle($command);

        $this->doctrine->getManager()->refresh($state);
        self::assertSame($after, $this->responsibleGroupsToArray($state));
    }

    public function testAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('artem@example.com');

        /** @var State $state */
        [/* skipping */, $state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var Group $group */
        [/* skipping */, $group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        $command = new SetResponsibleGroupsCommand([
            'state'  => $state->id,
            'groups' => [
                $group->id,
            ],
        ]);

        $this->commandbus->handle($command);
    }

    public function testUnlockedTemplate()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var State $state */
        [$state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        $command = new SetResponsibleGroupsCommand([
            'state'  => $state->id,
            'groups' => [
                $group->id,
            ],
        ]);

        $this->commandbus->handle($command);
    }

    public function testFinalState()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var State $state */
        [/* skipping */, $state] = $this->repository->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        /** @var Group $group */
        [/* skipping */, $group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        $command = new SetResponsibleGroupsCommand([
            'state'  => $state->id,
            'groups' => [
                $group->id,
            ],
        ]);

        $this->commandbus->handle($command);
    }

    public function testUnknownState()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->loginAs('admin@example.com');

        /** @var Group $group */
        [/* skipping */, $group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'ASC']);

        $command = new SetResponsibleGroupsCommand([
            'state'  => self::UNKNOWN_ENTITY_ID,
            'groups' => [
                $group->id,
            ],
        ]);

        $this->commandbus->handle($command);
    }

    public function testWrongGroup()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown group: Support Engineers');

        $this->loginAs('admin@example.com');

        /** @var State $state */
        [/* skipping */, $state] = $this->repository->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var Group $group */
        [$group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Support Engineers'], ['id' => 'DESC']);

        $command = new SetResponsibleGroupsCommand([
            'state'  => $state->id,
            'groups' => [
                $group->id,
            ],
        ]);

        $this->commandbus->handle($command);
    }

    /**
     * @param State $state
     *
     * @return string[]
     */
    protected function responsibleGroupsToArray(State $state): array
    {
        $result = array_map(function (StateResponsibleGroup $group) {
            return $group->group->name;
        }, $state->responsibleGroups);

        sort($result);

        return $result;
    }
}
