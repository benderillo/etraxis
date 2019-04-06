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

use eTraxis\SecurityDomain\Application\Command\Users\CreateUserCommand;
use eTraxis\SecurityDomain\Application\Voter\UserVoter;
use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\SecurityDomain\Model\Repository\UserRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command handler.
 */
class CreateUserHandler
{
    protected $security;
    protected $validator;
    protected $encoder;
    protected $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param ValidatorInterface            $validator
     * @param UserPasswordEncoderInterface  $encoder
     * @param UserRepository                $repository
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        ValidatorInterface            $validator,
        UserPasswordEncoderInterface  $encoder,
        UserRepository                $repository
    )
    {
        $this->security   = $security;
        $this->validator  = $validator;
        $this->encoder    = $encoder;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param CreateUserCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws BadRequestHttpException
     * @throws ConflictHttpException
     *
     * @return User
     */
    public function handle(CreateUserCommand $command): User
    {
        if (!$this->security->isGranted(UserVoter::CREATE_USER)) {
            throw new AccessDeniedHttpException();
        }

        $user = new User();

        $user->email       = $command->email;
        $user->fullname    = $command->fullname;
        $user->description = $command->description;
        $user->isAdmin     = $command->admin;
        $user->locale      = $command->locale;
        $user->theme       = $command->theme;
        $user->timezone    = $command->timezone;

        $user->setEnabled(!$command->disabled);

        try {
            $user->password = $this->encoder->encodePassword($user, $command->password);
        }
        catch (BadCredentialsException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        $errors = $this->validator->validate($user);

        if (count($errors)) {
            throw new ConflictHttpException($errors->get(0)->getMessage());
        }

        $this->repository->persist($user);

        return $user;
    }
}
