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
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

class ProjectVoterTest extends TransactionalTestCase
{
    /** @var \Symfony\Component\Security\Core\Authorization\AuthorizationChecker */
    protected $security;

    /** @var \eTraxis\TemplatesDomain\Model\Repository\ProjectRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->security   = $this->client->getContainer()->get('security.authorization_checker');
        $this->repository = $this->doctrine->getRepository(Project::class);
    }

    public function testUnsupportedAttribute()
    {
        $project = $this->repository->findOneBy(['name' => 'Distinctio']);

        $this->loginAs('admin@example.com');
        self::assertFalse($this->security->isGranted('UNKNOWN', $project));
    }

    public function testAnonymous()
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->doctrine->getManager();

        $voter = new ProjectVoter($manager);
        $token = new AnonymousToken('', 'anon.');

        $project = $this->repository->findOneBy(['name' => 'Distinctio']);

        self::assertSame(ProjectVoter::ACCESS_DENIED, $voter->vote($token, null, [ProjectVoter::CREATE_PROJECT]));
        self::assertSame(ProjectVoter::ACCESS_DENIED, $voter->vote($token, $project, [ProjectVoter::UPDATE_PROJECT]));
        self::assertSame(ProjectVoter::ACCESS_DENIED, $voter->vote($token, $project, [ProjectVoter::DELETE_PROJECT]));
        self::assertSame(ProjectVoter::ACCESS_DENIED, $voter->vote($token, $project, [ProjectVoter::SUSPEND_PROJECT]));
        self::assertSame(ProjectVoter::ACCESS_DENIED, $voter->vote($token, $project, [ProjectVoter::RESUME_PROJECT]));
    }

    public function testCreate()
    {
        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(ProjectVoter::CREATE_PROJECT));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(ProjectVoter::CREATE_PROJECT));
    }

    public function testUpdate()
    {
        $project = $this->repository->findOneBy(['name' => 'Distinctio']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(ProjectVoter::UPDATE_PROJECT, $project));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(ProjectVoter::UPDATE_PROJECT, $project));
    }

    public function testDelete()
    {
        $projectA = $this->repository->findOneBy(['name' => 'Distinctio']);
        $projectD = $this->repository->findOneBy(['name' => 'Presto']);

        $this->loginAs('admin@example.com');
        self::assertFalse($this->security->isGranted(ProjectVoter::DELETE_PROJECT, $projectA));
        self::assertTrue($this->security->isGranted(ProjectVoter::DELETE_PROJECT, $projectD));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(ProjectVoter::DELETE_PROJECT, $projectA));
        self::assertFalse($this->security->isGranted(ProjectVoter::DELETE_PROJECT, $projectD));
    }

    public function testSuspend()
    {
        $projectA = $this->repository->findOneBy(['name' => 'Distinctio']);
        $projectB = $this->repository->findOneBy(['name' => 'Molestiae']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(ProjectVoter::SUSPEND_PROJECT, $projectA));
        self::assertTrue($this->security->isGranted(ProjectVoter::SUSPEND_PROJECT, $projectB));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(ProjectVoter::SUSPEND_PROJECT, $projectA));
        self::assertFalse($this->security->isGranted(ProjectVoter::SUSPEND_PROJECT, $projectB));
    }

    public function testResume()
    {
        $projectA = $this->repository->findOneBy(['name' => 'Distinctio']);
        $projectB = $this->repository->findOneBy(['name' => 'Molestiae']);

        $this->loginAs('admin@example.com');
        self::assertTrue($this->security->isGranted(ProjectVoter::RESUME_PROJECT, $projectA));
        self::assertTrue($this->security->isGranted(ProjectVoter::RESUME_PROJECT, $projectB));

        $this->loginAs('artem@example.com');
        self::assertFalse($this->security->isGranted(ProjectVoter::RESUME_PROJECT, $projectA));
        self::assertFalse($this->security->isGranted(ProjectVoter::RESUME_PROJECT, $projectB));
    }
}
