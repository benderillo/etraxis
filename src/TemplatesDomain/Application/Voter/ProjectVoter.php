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

use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\TemplatesDomain\Model\Entity\Project;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter for "Project" entities.
 */
class ProjectVoter extends Voter
{
    public const CREATE_PROJECT  = 'project.create';
    public const UPDATE_PROJECT  = 'project.update';
    public const DELETE_PROJECT  = 'project.delete';
    public const SUSPEND_PROJECT = 'project.suspend';
    public const RESUME_PROJECT  = 'project.resume';

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {
        $attributes = [
            self::CREATE_PROJECT  => null,
            self::UPDATE_PROJECT  => Project::class,
            self::DELETE_PROJECT  => Project::class,
            self::SUSPEND_PROJECT => Project::class,
            self::RESUME_PROJECT  => Project::class,
        ];

        // Whether the attribute is supported.
        if (!array_key_exists($attribute, $attributes)) {
            return false;
        }

        // Whether the subject is not required.
        if ($attributes[$attribute] === null) {
            return true;
        }

        // The subject must be an object of expected class.
        return is_object($subject) && get_class($subject) === $attributes[$attribute];
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

        /** @var Project $subject */
        switch ($attribute) {

            default:
                return false;
        }
    }
}
