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

namespace eTraxis\IssuesDomain\Model\Repository;

use eTraxis\IssuesDomain\Model\Entity\Comment;
use eTraxis\IssuesDomain\Model\Entity\Issue;
use eTraxis\Tests\WebTestCase;

/**
 * @coversDefaultClass \eTraxis\IssuesDomain\Model\Repository\CommentRepository
 */
class CommentRepositoryTest extends WebTestCase
{
    /** @var CommentRepository */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->doctrine->getRepository(Comment::class);
    }

    /**
     * @covers ::__construct
     */
    public function testRepository()
    {
        self::assertInstanceOf(CommentRepository::class, $this->repository);
    }

    /**
     * @covers ::findAllByIssue
     */
    public function testFindAllByIssueWithPrivate()
    {
        $expected = [
            'Assumenda dolor tempora nisi tempora tempore.',
            'Ut ipsum explicabo iste sequi dignissimos.',
            'Natus excepturi est eaque nostrum non.',
        ];

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $comments = $this->repository->findAllByIssue($issue, true);

        self::assertCount(3, $comments);

        foreach ($comments as $index => $comment) {
            self::assertSame($expected[$index], mb_substr($comment->body, 0, mb_strlen($expected[$index])));
        }
    }

    /**
     * @covers ::findAllByIssue
     */
    public function testFindAllByIssueNoPrivate()
    {
        $expected = [
            'Assumenda dolor tempora nisi tempora tempore.',
            'Natus excepturi est eaque nostrum non.',
        ];

        /** @var Issue $issue */
        [$issue] = $this->doctrine->getRepository(Issue::class)->findBy(['subject' => 'Development task 2'], ['id' => 'ASC']);

        $comments = $this->repository->findAllByIssue($issue, false);

        self::assertCount(2, $comments);

        foreach ($comments as $index => $comment) {
            self::assertSame($expected[$index], mb_substr($comment->body, 0, mb_strlen($expected[$index])));
        }
    }
}
