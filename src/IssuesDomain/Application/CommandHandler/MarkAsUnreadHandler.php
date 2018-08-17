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
use eTraxis\IssuesDomain\Application\Command\MarkAsUnreadCommand;
use eTraxis\IssuesDomain\Model\Entity\LastRead;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Command handler.
 */
class MarkAsUnreadHandler
{
    protected $tokens;
    protected $manager;

    /**
     * Dependency Injection constructor.
     *
     * @param TokenStorageInterface  $tokens
     * @param EntityManagerInterface $manager
     */
    public function __construct(TokenStorageInterface $tokens, EntityManagerInterface $manager)
    {
        $this->tokens  = $tokens;
        $this->manager = $manager;
    }

    /**
     * Command handler.
     *
     * @param MarkAsUnreadCommand $command
     */
    public function handle(MarkAsUnreadCommand $command): void
    {
        /** @var \eTraxis\SecurityDomain\Model\Entity\User $user */
        $user = $this->tokens->getToken()->getUser();

        $query = $this->manager->createQueryBuilder();

        $query
            ->delete(LastRead::class, 'r')
            ->where('r.user = :user')
            ->andWhere($query->expr()->in('r.issue', ':issues'));

        $query->getQuery()->execute([
            'user'   => $user,
            'issues' => $command->issues,
        ]);
    }
}
