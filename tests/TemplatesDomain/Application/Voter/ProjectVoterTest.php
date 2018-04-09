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

    protected function setUp()
    {
        parent::setUp();

        $this->security = $this->client->getContainer()->get('security.authorization_checker');
    }

    public function testUnsupportedAttribute()
    {
        /** @var \eTraxis\TemplatesDomain\Model\Repository\ProjectRepository $repository */
        $repository = $this->doctrine->getRepository(Project::class);

        $project = $repository->findOneBy(['name' => 'Distinctio']);

        $this->loginAs('admin@example.com');
        self::assertFalse($this->security->isGranted('UNKNOWN', $project));
    }

    public function testAnonymous()
    {
        $voter = new ProjectVoter();
        $token = new AnonymousToken('', 'anon.');

        /** @var \eTraxis\TemplatesDomain\Model\Repository\ProjectRepository $repository */
        $repository = $this->doctrine->getRepository(Project::class);

        $project = $repository->findOneBy(['name' => 'Distinctio']);

        self::assertSame(ProjectVoter::ACCESS_DENIED, $voter->vote($token, $project, [ProjectVoter::CREATE_PROJECT]));
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
}
