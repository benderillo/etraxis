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

use eTraxis\SecurityDomain\Application\Command\Users\UpdateSettingsCommand;
use eTraxis\SecurityDomain\Model\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Command handler.
 */
class UpdateSettingsHandler
{
    protected $tokens;
    protected $session;
    protected $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param TokenStorageInterface $tokens
     * @param SessionInterface      $session
     * @param UserRepository        $repository
     */
    public function __construct(TokenStorageInterface $tokens, SessionInterface $session, UserRepository $repository)
    {
        $this->tokens     = $tokens;
        $this->session    = $session;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param UpdateSettingsCommand $command
     *
     * @throws AccessDeniedHttpException
     */
    public function handle(UpdateSettingsCommand $command): void
    {
        $token = $this->tokens->getToken();

        // User must be logged in.
        if (!$token) {
            throw new AccessDeniedHttpException();
        }

        /** @var \eTraxis\SecurityDomain\Model\Entity\User $user */
        $user = $token->getUser();

        $user->locale   = $command->locale;
        $user->theme    = $command->theme;
        $user->timezone = $command->timezone;

        $this->repository->persist($user);

        $this->session->set('_locale', $user->locale);
    }
}
