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

namespace eTraxis\TemplatesDomain\Framework\Controller\ApiStatesController;

use eTraxis\SecurityDomain\Model\Entity\Group;
use eTraxis\TemplatesDomain\Model\Dictionary\SystemRole;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\TemplatesDomain\Model\Entity\StateGroupTransition;
use eTraxis\TemplatesDomain\Model\Entity\StateRoleTransition;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTransitionsTest extends TransactionalTestCase
{
    public function testSuccessAll()
    {
        /** @var State $stateFrom */
        [/* skipping */, $stateFrom] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var State $stateTo */
        [/* skipping */, $stateTo] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        /** @var Group $group */
        [/* skipping */, $group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['description' => 'ASC']);

        $roles = array_filter($stateFrom->roleTransitions, function (StateRoleTransition $transition) use ($stateTo) {
            return $transition->toState === $stateTo && $transition->role === SystemRole::AUTHOR;
        });

        $groups = array_filter($stateFrom->groupTransitions, function (StateGroupTransition $transition) use ($stateTo, $group) {
            return $transition->toState === $stateTo && $transition->group === $group;
        });

        self::assertEmpty($roles);
        self::assertEmpty($groups);

        $data = [
            'state'  => $stateTo->id,
            'roles'  => [
                SystemRole::AUTHOR,
            ],
            'groups' => [
                $group->id,
            ],
        ];

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/states/%s/transitions', $stateFrom->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->doctrine->getManager()->refresh($stateFrom);

        $roles = array_filter($stateFrom->roleTransitions, function (StateRoleTransition $transition) use ($stateTo) {
            return $transition->toState === $stateTo && $transition->role === SystemRole::AUTHOR;
        });

        $groups = array_filter($stateFrom->groupTransitions, function (StateGroupTransition $transition) use ($stateTo, $group) {
            return $transition->toState === $stateTo && $transition->group === $group;
        });

        self::assertNotEmpty($roles);
        self::assertNotEmpty($groups);
    }

    public function testSuccessRoles()
    {
        /** @var State $stateFrom */
        [/* skipping */, $stateFrom] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var State $stateTo */
        [/* skipping */, $stateTo] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        /** @var Group $group */
        [/* skipping */, $group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['description' => 'ASC']);

        $roles = array_filter($stateFrom->roleTransitions, function (StateRoleTransition $transition) use ($stateTo) {
            return $transition->toState === $stateTo && $transition->role === SystemRole::AUTHOR;
        });

        $groups = array_filter($stateFrom->groupTransitions, function (StateGroupTransition $transition) use ($stateTo, $group) {
            return $transition->toState === $stateTo && $transition->group === $group;
        });

        self::assertEmpty($roles);
        self::assertEmpty($groups);

        $data = [
            'state' => $stateTo->id,
            'roles' => [
                SystemRole::AUTHOR,
            ],
        ];

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/states/%s/transitions', $stateFrom->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->doctrine->getManager()->refresh($stateFrom);

        $roles = array_filter($stateFrom->roleTransitions, function (StateRoleTransition $transition) use ($stateTo) {
            return $transition->toState === $stateTo && $transition->role === SystemRole::AUTHOR;
        });

        $groups = array_filter($stateFrom->groupTransitions, function (StateGroupTransition $transition) use ($stateTo, $group) {
            return $transition->toState === $stateTo && $transition->group === $group;
        });

        self::assertNotEmpty($roles);
        self::assertEmpty($groups);
    }

    public function testSuccessGroups()
    {
        /** @var State $stateFrom */
        [/* skipping */, $stateFrom] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var State $stateTo */
        [/* skipping */, $stateTo] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        /** @var Group $group */
        [/* skipping */, $group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['description' => 'ASC']);

        $roles = array_filter($stateFrom->roleTransitions, function (StateRoleTransition $transition) use ($stateTo) {
            return $transition->toState === $stateTo && $transition->role === SystemRole::AUTHOR;
        });

        $groups = array_filter($stateFrom->groupTransitions, function (StateGroupTransition $transition) use ($stateTo, $group) {
            return $transition->toState === $stateTo && $transition->group === $group;
        });

        self::assertEmpty($roles);
        self::assertEmpty($groups);

        $data = [
            'state'  => $stateTo->id,
            'groups' => [
                $group->id,
            ],
        ];

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/states/%s/transitions', $stateFrom->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->doctrine->getManager()->refresh($stateFrom);

        $roles = array_filter($stateFrom->roleTransitions, function (StateRoleTransition $transition) use ($stateTo) {
            return $transition->toState === $stateTo && $transition->role === SystemRole::AUTHOR;
        });

        $groups = array_filter($stateFrom->groupTransitions, function (StateGroupTransition $transition) use ($stateTo, $group) {
            return $transition->toState === $stateTo && $transition->group === $group;
        });

        self::assertEmpty($roles);
        self::assertNotEmpty($groups);
    }

    public function testSuccessNone()
    {
        /** @var State $stateFrom */
        [/* skipping */, $stateFrom] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var State $stateTo */
        [/* skipping */, $stateTo] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        /** @var Group $group */
        [/* skipping */, $group] = $this->doctrine->getRepository(Group::class)->findBy(['name' => 'Developers'], ['description' => 'ASC']);

        $roles = array_filter($stateFrom->roleTransitions, function (StateRoleTransition $transition) use ($stateTo) {
            return $transition->toState === $stateTo && $transition->role === SystemRole::AUTHOR;
        });

        $groups = array_filter($stateFrom->groupTransitions, function (StateGroupTransition $transition) use ($stateTo, $group) {
            return $transition->toState === $stateTo && $transition->group === $group;
        });

        self::assertEmpty($roles);
        self::assertEmpty($groups);

        $data = [
            'state' => $stateTo->id,
        ];

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/states/%s/transitions', $stateFrom->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $this->doctrine->getManager()->refresh($stateFrom);

        $roles = array_filter($stateFrom->roleTransitions, function (StateRoleTransition $transition) use ($stateTo) {
            return $transition->toState === $stateTo && $transition->role === SystemRole::AUTHOR;
        });

        $groups = array_filter($stateFrom->groupTransitions, function (StateGroupTransition $transition) use ($stateTo, $group) {
            return $transition->toState === $stateTo && $transition->group === $group;
        });

        self::assertEmpty($roles);
        self::assertEmpty($groups);
    }

    public function test400()
    {
        /** @var State $stateFrom */
        [/* skipping */, $stateFrom] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        $data = [
            'roles' => [
                SystemRole::AUTHOR,
            ],
        ];

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/states/%s/transitions', $stateFrom->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function test401()
    {
        /** @var State $stateFrom */
        [/* skipping */, $stateFrom] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var State $stateTo */
        [/* skipping */, $stateTo] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        $data = [
            'state' => $stateTo->id,
            'roles' => [
                SystemRole::AUTHOR,
            ],
        ];

        $uri = sprintf('/api/states/%s/transitions', $stateFrom->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        /** @var State $stateFrom */
        [/* skipping */, $stateFrom] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Assigned'], ['id' => 'ASC']);

        /** @var State $stateTo */
        [/* skipping */, $stateTo] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        $data = [
            'state' => $stateTo->id,
            'roles' => [
                SystemRole::AUTHOR,
            ],
        ];

        $this->loginAs('artem@example.com');

        $uri = sprintf('/api/states/%s/transitions', $stateFrom->id);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function test404()
    {
        /** @var State $stateTo */
        [/* skipping */, $stateTo] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'Completed'], ['id' => 'ASC']);

        $data = [
            'state' => $stateTo->id,
            'roles' => [
                SystemRole::AUTHOR,
            ],
        ];

        $this->loginAs('admin@example.com');

        $uri = sprintf('/api/states/%s/transitions', self::UNKNOWN_ENTITY_ID);

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
