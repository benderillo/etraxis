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

namespace eTraxis\IssuesDomain\Application\Voter;

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\IssuesDomain\Model\Entity\Issue;
use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\SharedDomain\Application\Voter\VoterTrait;
use eTraxis\TemplatesDomain\Model\Dictionary\TemplatePermission;
use eTraxis\TemplatesDomain\Model\Entity\TemplateGroupPermission;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter for "Issue" entities.
 */
class IssueVoter extends Voter
{
    use VoterTrait;

    public const VIEW_ISSUE = 'issue.view';

    protected $attributes = [
        self::VIEW_ISSUE => Issue::class,
    ];

    protected $manager;

    /**
     * Dependency Injection constructor.
     *
     * @param EntityManagerInterface $manager
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        // User must be logged in.
        if (!$user instanceof User) {
            return false;
        }

        switch ($attribute) {

            case self::VIEW_ISSUE:
                return $this->isViewGranted($subject, $user);

            default:
                return false;
        }
    }

    /**
     * Whether the specified issue can be viewed.
     *
     * @param Issue $subject Subject issue.
     * @param User  $user    Current user.
     *
     * @return bool
     */
    protected function isViewGranted(Issue $subject, User $user): bool
    {
        // Authors can always view their issues.
        if ($subject->author === $user) {
            return true;
        }

        // Responsibles can always view their issues.
        if ($subject->responsible !== null && $subject->responsible === $user) {
            return true;
        }

        $query = $this->manager->createQueryBuilder();

        $query
            ->select('tgp')
            ->from(TemplateGroupPermission::class, 'tgp')
            ->where('tgp.template = :template')
            ->andWhere($query->expr()->in('tgp.group', ':groups'))
            ->andWhere('tgp.permission = :permission')
            ->setParameters([
                'template'   => $subject->template,
                'groups'     => $user->groups,
                'permission' => TemplatePermission::VIEW_ISSUES,
            ]);

        $results = $query->getQuery()->getResult();

        return count($results) !== 0;
    }
}
