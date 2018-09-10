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

use eTraxis\TemplatesDomain\Model\Entity\Project;
use eTraxis\TemplatesDomain\Model\Entity\Template;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

class TemplateVoterTest extends TransactionalTestCase
{
    /** @var \Symfony\Component\Security\Core\Authorization\AuthorizationChecker */
    protected $security;

    /** @var \eTraxis\TemplatesDomain\Model\Repository\TemplateRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->security   = $this->client->getContainer()->get('security.authorization_checker');
        $this->repository = $this->doctrine->getRepository(Template::class);
    }

    public function testUnsupportedAttribute()
    {
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertFalse($this->security->isGranted('UNKNOWN', $template));
    }

    public function testAnonymous()
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();

        $voter = new TemplateVoter($manager);
        $token = new AnonymousToken('', 'anon.');

        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        self::assertSame(TemplateVoter::ACCESS_DENIED, $voter->vote($token, $project, [TemplateVoter::CREATE_TEMPLATE]));
        self::assertSame(TemplateVoter::ACCESS_DENIED, $voter->vote($token, $template, [TemplateVoter::UPDATE_TEMPLATE]));
        self::assertSame(TemplateVoter::ACCESS_DENIED, $voter->vote($token, $template, [TemplateVoter::DELETE_TEMPLATE]));
        self::assertSame(TemplateVoter::ACCESS_DENIED, $voter->vote($token, $template, [TemplateVoter::LOCK_TEMPLATE]));
        self::assertSame(TemplateVoter::ACCESS_DENIED, $voter->vote($token, $template, [TemplateVoter::UNLOCK_TEMPLATE]));
        self::assertSame(TemplateVoter::ACCESS_DENIED, $voter->vote($token, $template, [TemplateVoter::MANAGE_PERMISSIONS]));
    }

    public function testCreate()
    {
        $project = $this->doctrine->getRepository(Project::class)->findOneBy(['name' => 'Distinctio']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(TemplateVoter::CREATE_TEMPLATE, $project));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(TemplateVoter::CREATE_TEMPLATE, $project));
    }

    public function testUpdate()
    {
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(TemplateVoter::UPDATE_TEMPLATE, $template));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(TemplateVoter::UPDATE_TEMPLATE, $template));
    }

    public function testDelete()
    {
        [$templateA] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);
        [$templateD] = $this->repository->findBy(['name' => 'Development'], ['id' => 'DESC']);

        $this->loginAs('admin@example.com');
        self::assertFalse($this->security->isGranted(TemplateVoter::DELETE_TEMPLATE, $templateA));
        self::assertTrue($this->security->isGranted(TemplateVoter::DELETE_TEMPLATE, $templateD));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(TemplateVoter::DELETE_TEMPLATE, $templateA));
        self::assertFalse($this->security->isGranted(TemplateVoter::DELETE_TEMPLATE, $templateD));
    }

    public function testLock()
    {
        [$templateA, /* skipping */, $templateC] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(TemplateVoter::LOCK_TEMPLATE, $templateA));
        self::assertTrue($this->security->isGranted(TemplateVoter::LOCK_TEMPLATE, $templateC));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(TemplateVoter::LOCK_TEMPLATE, $templateA));
        self::assertFalse($this->security->isGranted(TemplateVoter::LOCK_TEMPLATE, $templateC));
    }

    public function testUnlock()
    {
        [$templateA, /* skipping */, $templateC] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(TemplateVoter::UNLOCK_TEMPLATE, $templateA));
        self::assertTrue($this->security->isGranted(TemplateVoter::UNLOCK_TEMPLATE, $templateC));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(TemplateVoter::UNLOCK_TEMPLATE, $templateA));
        self::assertFalse($this->security->isGranted(TemplateVoter::UNLOCK_TEMPLATE, $templateC));
    }

    public function testManagePermissions()
    {
        [$template] = $this->repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(TemplateVoter::MANAGE_PERMISSIONS, $template));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(TemplateVoter::MANAGE_PERMISSIONS, $template));
    }
}
