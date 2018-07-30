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

namespace eTraxis\TemplatesDomain\Application\Voter;

use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

class FieldVoterTest extends TransactionalTestCase
{
    /** @var \Symfony\Component\Security\Core\Authorization\AuthorizationChecker */
    protected $security;

    /** @var \eTraxis\TemplatesDomain\Model\Repository\FieldRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->security   = $this->client->getContainer()->get('security.authorization_checker');
        $this->repository = $this->doctrine->getRepository(Field::class);
    }

    public function testUnsupportedAttribute()
    {
        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertFalse($this->security->isGranted('UNKNOWN', $field));
    }

    public function testAnonymous()
    {
        $voter = new FieldVoter();
        $token = new AnonymousToken('', 'anon.');

        [/* skipping */, $state] = $this->doctrine->getRepository(State::class)->findBy(['name' => 'New'], ['id' => 'ASC']);

        [/* skipping */, $field] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        self::assertSame(FieldVoter::ACCESS_DENIED, $voter->vote($token, $state, [FieldVoter::CREATE_FIELD]));
        self::assertSame(FieldVoter::ACCESS_DENIED, $voter->vote($token, $field, [FieldVoter::UPDATE_FIELD]));
        self::assertSame(FieldVoter::ACCESS_DENIED, $voter->vote($token, $field, [FieldVoter::DELETE_FIELD]));
        self::assertSame(FieldVoter::ACCESS_DENIED, $voter->vote($token, $field, [FieldVoter::MANAGE_PERMISSIONS]));
    }

    public function testCreate()
    {
        /** @var \eTraxis\TemplatesDomain\Model\Repository\StateRepository $repository */
        $repository = $this->doctrine->getRepository(State::class);

        [/* skipping */, $stateB, $stateC] = $repository->findBy(['name' => 'New'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(FieldVoter::CREATE_FIELD, $stateB));
        self::assertFalse($this->security->isGranted(FieldVoter::CREATE_FIELD, $stateC));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(FieldVoter::CREATE_FIELD, $stateB));
        self::assertFalse($this->security->isGranted(FieldVoter::CREATE_FIELD, $stateC));
    }

    public function testUpdate()
    {
        [/* skipping */, $fieldB, $fieldC] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(FieldVoter::UPDATE_FIELD, $fieldB));
        self::assertFalse($this->security->isGranted(FieldVoter::UPDATE_FIELD, $fieldC));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(FieldVoter::UPDATE_FIELD, $fieldB));
        self::assertFalse($this->security->isGranted(FieldVoter::UPDATE_FIELD, $fieldC));
    }

    public function testDelete()
    {
        [/* skipping */, $fieldB, $fieldC] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(FieldVoter::DELETE_FIELD, $fieldB));
        self::assertFalse($this->security->isGranted(FieldVoter::DELETE_FIELD, $fieldC));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(FieldVoter::DELETE_FIELD, $fieldB));
        self::assertFalse($this->security->isGranted(FieldVoter::DELETE_FIELD, $fieldC));
    }

    public function testManagePermissions()
    {
        [/* skipping */, $fieldB, $fieldC] = $this->repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(FieldVoter::MANAGE_PERMISSIONS, $fieldB));
        self::assertFalse($this->security->isGranted(FieldVoter::MANAGE_PERMISSIONS, $fieldC));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(FieldVoter::MANAGE_PERMISSIONS, $fieldB));
        self::assertFalse($this->security->isGranted(FieldVoter::MANAGE_PERMISSIONS, $fieldC));
    }
}
