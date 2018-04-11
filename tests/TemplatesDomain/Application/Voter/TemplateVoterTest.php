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

    protected function setUp()
    {
        parent::setUp();

        $this->security = $this->client->getContainer()->get('security.authorization_checker');
    }

    public function testUnsupportedAttribute()
    {
        /** @var \eTraxis\TemplatesDomain\Model\Repository\TemplateRepository $repository */
        $repository = $this->doctrine->getRepository(Template::class);

        [$template] = $repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertFalse($this->security->isGranted('UNKNOWN', $template));
    }

    public function testAnonymous()
    {
        $voter = new TemplateVoter();
        $token = new AnonymousToken('', 'anon.');

        /** @var \eTraxis\TemplatesDomain\Model\Repository\ProjectRepository $repository */
        $repository = $this->doctrine->getRepository(Project::class);

        $project = $repository->findOneBy(['name' => 'Distinctio']);

        /** @var \eTraxis\TemplatesDomain\Model\Repository\TemplateRepository $repository */
        $repository = $this->doctrine->getRepository(Template::class);

        [$template] = $repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        self::assertSame(TemplateVoter::ACCESS_DENIED, $voter->vote($token, $project, [TemplateVoter::CREATE_TEMPLATE]));
        self::assertSame(TemplateVoter::ACCESS_DENIED, $voter->vote($token, $template, [TemplateVoter::UPDATE_TEMPLATE]));
        self::assertSame(TemplateVoter::ACCESS_DENIED, $voter->vote($token, $template, [TemplateVoter::DELETE_TEMPLATE]));
        self::assertSame(TemplateVoter::ACCESS_DENIED, $voter->vote($token, $template, [TemplateVoter::LOCK_TEMPLATE]));
        self::assertSame(TemplateVoter::ACCESS_DENIED, $voter->vote($token, $template, [TemplateVoter::UNLOCK_TEMPLATE]));
        self::assertSame(TemplateVoter::ACCESS_DENIED, $voter->vote($token, $template, [TemplateVoter::MANAGE_PERMISSIONS]));
    }

    public function testCreate()
    {
        /** @var \eTraxis\TemplatesDomain\Model\Repository\ProjectRepository $repository */
        $repository = $this->doctrine->getRepository(Project::class);

        $project = $repository->findOneBy(['name' => 'Distinctio']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(TemplateVoter::CREATE_TEMPLATE, $project));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(TemplateVoter::CREATE_TEMPLATE, $project));
    }

    public function testUpdate()
    {
        /** @var \eTraxis\TemplatesDomain\Model\Repository\TemplateRepository $repository */
        $repository = $this->doctrine->getRepository(Template::class);

        [$template] = $repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(TemplateVoter::UPDATE_TEMPLATE, $template));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(TemplateVoter::UPDATE_TEMPLATE, $template));
    }

    public function testDelete()
    {
        /** @var \eTraxis\TemplatesDomain\Model\Repository\TemplateRepository $repository */
        $repository = $this->doctrine->getRepository(Template::class);

        [$template] = $repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(TemplateVoter::DELETE_TEMPLATE, $template));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(TemplateVoter::DELETE_TEMPLATE, $template));
    }

    public function testLock()
    {
        /** @var \eTraxis\TemplatesDomain\Model\Repository\TemplateRepository $repository */
        $repository = $this->doctrine->getRepository(Template::class);

        [$templateA, /* skipping */, $templateC] = $repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(TemplateVoter::LOCK_TEMPLATE, $templateA));
        self::assertTrue($this->security->isGranted(TemplateVoter::LOCK_TEMPLATE, $templateC));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(TemplateVoter::LOCK_TEMPLATE, $templateA));
        self::assertFalse($this->security->isGranted(TemplateVoter::LOCK_TEMPLATE, $templateC));
    }

    public function testUnlock()
    {
        /** @var \eTraxis\TemplatesDomain\Model\Repository\TemplateRepository $repository */
        $repository = $this->doctrine->getRepository(Template::class);

        [$templateA, /* skipping */, $templateC] = $repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(TemplateVoter::UNLOCK_TEMPLATE, $templateA));
        self::assertTrue($this->security->isGranted(TemplateVoter::UNLOCK_TEMPLATE, $templateC));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(TemplateVoter::UNLOCK_TEMPLATE, $templateA));
        self::assertFalse($this->security->isGranted(TemplateVoter::UNLOCK_TEMPLATE, $templateC));
    }

    public function testManagePermissions()
    {
        /** @var \eTraxis\TemplatesDomain\Model\Repository\TemplateRepository $repository */
        $repository = $this->doctrine->getRepository(Template::class);

        [$template] = $repository->findBy(['name' => 'Development'], ['id' => 'ASC']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(TemplateVoter::MANAGE_PERMISSIONS, $template));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(TemplateVoter::MANAGE_PERMISSIONS, $template));
    }
}
