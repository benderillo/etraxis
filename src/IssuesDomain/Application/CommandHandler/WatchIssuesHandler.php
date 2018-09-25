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

namespace eTraxis\IssuesDomain\Application\CommandHandler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use eTraxis\IssuesDomain\Application\Command\WatchIssuesCommand;
use eTraxis\IssuesDomain\Model\Entity\Issue;
use eTraxis\IssuesDomain\Model\Entity\Watcher;
use eTraxis\IssuesDomain\Model\Repository\WatcherRepository;
use eTraxis\TemplatesDomain\Model\Dictionary\TemplatePermission;
use eTraxis\TemplatesDomain\Model\Entity\Template;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Command handler.
 */
class WatchIssuesHandler
{
    protected $tokens;
    protected $repository;
    protected $manager;

    /**
     * Dependency Injection constructor.
     *
     * @param TokenStorageInterface  $tokens
     * @param WatcherRepository      $repository
     * @param EntityManagerInterface $manager
     */
    public function __construct(
        TokenStorageInterface  $tokens,
        WatcherRepository      $repository,
        EntityManagerInterface $manager
    )
    {
        $this->tokens     = $tokens;
        $this->repository = $repository;
        $this->manager    = $manager;
    }

    /**
     * Command handler.
     *
     * @param WatchIssuesCommand $command
     */
    public function handle(WatchIssuesCommand $command): void
    {
        /** @var \eTraxis\SecurityDomain\Model\Entity\User $user */
        $user = $this->tokens->getToken()->getUser();

        // Find all templates which issues the user has access to.
        $query = $this->manager->createQueryBuilder();

        $query
            ->distinct()
            ->select('t')
            ->from(Template::class, 't')
            ->innerJoin('t.groupPermissionsCollection', 'tp', Join::WITH, 'tp.permission = :permission')
            ->innerJoin('tp.group', 'g')
            ->innerJoin('g.membersCollection', 'u', Join::WITH, 'u = :user');

        $templates = $query->getQuery()->execute([
            'permission' => TemplatePermission::VIEW_ISSUES,
            'user'       => $user,
        ]);

        // Filter specified issues to those the user has access to.
        $query = $this->manager->createQueryBuilder();

        $query
            ->distinct()
            ->select('i')
            ->from(Issue::class, 'i')
            ->innerJoin('i.state', 's')
            ->where($query->expr()->in('i.id', ':issues'))
            ->andWhere($query->expr()->orX(
                'i.author = :user',
                'i.responsible = :user',
                $query->expr()->in('s.template', ':templates')
            ));

        $issues = $query->getQuery()->execute([
            'user'      => $user,
            'templates' => $templates,
            'issues'    => $command->issues,
        ]);

        // Delete existing watchings for resulted issues.
        $query = $this->manager->createQueryBuilder();

        $query
            ->delete(Watcher::class, 'w')
            ->where('w.user = :user')
            ->andWhere($query->expr()->in('w.issue', ':issues'));

        $query->getQuery()->execute([
            'user'   => $user,
            'issues' => $issues,
        ]);

        // Watch resulted issues.
        foreach ($issues as $issue) {
            $watcher = new Watcher($issue, $user);
            $this->repository->persist($watcher);
        }
    }
}
