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

use eTraxis\SecurityDomain\Application\Command\Users\RegisterExternalAccountCommand;
use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\SecurityDomain\Model\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Command handler.
 */
class RegisterExternalAccountHandler
{
    protected $logger;
    protected $repository;
    protected $locale;
    protected $theme;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param LoggerInterface $logger
     * @param UserRepository  $repository
     * @param string          $locale
     * @param string          $theme
     */
    public function __construct(LoggerInterface $logger, UserRepository $repository, string $locale, string $theme)
    {
        $this->logger     = $logger;
        $this->repository = $repository;
        $this->locale     = $locale;
        $this->theme      = $theme;
    }

    /**
     * Command handler.
     *
     * @param RegisterExternalAccountCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws BadRequestHttpException
     * @throws ConflictHttpException
     *
     * @return User
     */
    public function handle(RegisterExternalAccountCommand $command): User
    {
        /** @var User $user */
        $user = $this->repository->findOneBy([
            'account.provider' => $command->provider,
            'account.uid'      => $command->uid,
        ]);

        // If we can't find the account by its UID, try to find by the email.
        if ($user === null) {
            $this->logger->info('Cannot find by UID.', [$command->provider, $command->uid]);

            $user = $this->repository->findOneByUsername($command->email);
        }

        // Register new account.
        if ($user === null) {
            $this->logger->info('Register external account.', [$command->email, $command->fullname]);

            $user = new User();

            $user->locale = $this->locale;
            $user->theme  = $this->theme;
        }
        // The account already exists - update it.
        else {
            $this->logger->info('Update external account.', [$command->email, $command->fullname]);
        }

        $user->account->provider = $command->provider;
        $user->account->uid      = $command->uid;
        $user->email             = $command->email;
        $user->fullname          = $command->fullname;
        $user->password          = null;

        $this->repository->persist($user);

        return $user;
    }
}
