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
use eTraxis\TemplatesDomain\Model\Entity\ListItem;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

class ListItemVoterTest extends TransactionalTestCase
{
    /** @var \Symfony\Component\Security\Core\Authorization\AuthorizationChecker */
    protected $security;

    /** @var \eTraxis\TemplatesDomain\Model\Repository\ListItemRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->security   = $this->client->getContainer()->get('security.authorization_checker');
        $this->repository = $this->doctrine->getRepository(ListItem::class);
    }

    public function testUnsupportedAttribute()
    {
        [$item] = $this->repository->findBy(['value' => 1], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertFalse($this->security->isGranted('UNKNOWN', $item));
    }

    public function testAnonymous()
    {
        $voter = new ListItemVoter();
        $token = new AnonymousToken('', 'anon.');

        [$field] = $this->doctrine->getRepository(Field::class)->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        [$item] = $this->repository->findBy(['value' => 1], ['id' => 'ASC']);

        self::assertSame(ListItemVoter::ACCESS_DENIED, $voter->vote($token, $field, [ListItemVoter::CREATE_ITEM]));
        self::assertSame(ListItemVoter::ACCESS_DENIED, $voter->vote($token, $item, [ListItemVoter::UPDATE_ITEM]));
        self::assertSame(ListItemVoter::ACCESS_DENIED, $voter->vote($token, $item, [ListItemVoter::DELETE_ITEM]));
    }

    public function testCreate()
    {
        /** @var \eTraxis\TemplatesDomain\Model\Repository\FieldRepository $repository */
        $repository = $this->doctrine->getRepository(Field::class);

        [$fieldA, /* skipping */, $fieldC] = $repository->findBy(['name' => 'Priority'], ['id' => 'ASC']);

        [$fieldW] = $repository->findBy(['name' => 'Description'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(ListItemVoter::CREATE_ITEM, $fieldA));
        self::assertFalse($this->security->isGranted(ListItemVoter::CREATE_ITEM, $fieldC));
        self::assertFalse($this->security->isGranted(ListItemVoter::CREATE_ITEM, $fieldW));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(ListItemVoter::CREATE_ITEM, $fieldA));
        self::assertFalse($this->security->isGranted(ListItemVoter::CREATE_ITEM, $fieldC));
        self::assertFalse($this->security->isGranted(ListItemVoter::CREATE_ITEM, $fieldW));
    }

    public function testUpdate()
    {
        [$itemA, /* skipping */, $itemC] = $this->repository->findBy(['value' => 1], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(ListItemVoter::UPDATE_ITEM, $itemA));
        self::assertFalse($this->security->isGranted(ListItemVoter::UPDATE_ITEM, $itemC));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(ListItemVoter::UPDATE_ITEM, $itemA));
        self::assertFalse($this->security->isGranted(ListItemVoter::UPDATE_ITEM, $itemC));
    }

    public function testDelete()
    {
        [$itemA, /* skipping */, $itemC] = $this->repository->findBy(['value' => 1], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(ListItemVoter::DELETE_ITEM, $itemA));
        self::assertFalse($this->security->isGranted(ListItemVoter::DELETE_ITEM, $itemC));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(ListItemVoter::DELETE_ITEM, $itemA));
        self::assertFalse($this->security->isGranted(ListItemVoter::DELETE_ITEM, $itemC));
    }
}
