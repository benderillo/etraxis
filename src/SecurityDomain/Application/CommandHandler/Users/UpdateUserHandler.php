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

namespace eTraxis\SecurityDomain\Application\CommandHandler\Users;

use eTraxis\SecurityDomain\Application\Command\Users\UpdateUserCommand;
use eTraxis\SecurityDomain\Application\Voter\UserVoter;
use eTraxis\SecurityDomain\Model\Repository\UserRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
class UpdateUserHandler
{
    protected $security;
    protected $validator;
    protected $tokens;
    protected $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param ValidatorInterface            $validator
     * @param TokenStorageInterface         $tokens
     * @param UserRepository                $repository
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        ValidatorInterface            $validator,
        TokenStorageInterface         $tokens,
        UserRepository                $repository
    )
    {
        $this->security   = $security;
        $this->validator  = $validator;
        $this->tokens     = $tokens;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param UpdateUserCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     * @throws NotFoundHttpException
     */
    public function handle(UpdateUserCommand $command): void
    {
        /** @var null|\eTraxis\SecurityDomain\Model\Entity\User $user */
        $user = $this->repository->find($command->user);

        if (!$user) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(UserVoter::UPDATE_USER, $user)) {
            throw new AccessDeniedHttpException();
        }

        $user->email       = $command->email;
        $user->fullname    = $command->fullname;
        $user->description = $command->description;
        $user->locale      = $command->locale;
        $user->theme       = $command->theme;
        $user->timezone    = $command->timezone;

        /** @var \eTraxis\SecurityDomain\Model\Entity\User $current */
        $current = $this->tokens->getToken()->getUser();

        // Don't disable yourself.
        if ($user->id !== $current->id) {
            $user->isAdmin = $command->admin;
            $user->setEnabled(!$command->disabled);
        }

        $errors = $this->validator->validate($user);

        if (count($errors)) {
            // Emails are used as usernames, so restore the entity to avoid impersonation.
            $this->repository->refresh($user);

            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->repository->persist($user);
    }
}
