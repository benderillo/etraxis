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

use eTraxis\SecurityDomain\Application\Command\Users\ForgetPasswordCommand;
use eTraxis\SecurityDomain\Model\Repository\UserRepository;
use eTraxis\SharedDomain\Application\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Command handler.
 */
class ForgetPasswordHandler
{
    protected $translator;
    protected $mailer;
    protected $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param TranslatorInterface $translator
     * @param MailerInterface     $mailer
     * @param UserRepository      $repository
     */
    public function __construct(TranslatorInterface $translator, MailerInterface $mailer, UserRepository $repository)
    {
        $this->translator = $translator;
        $this->mailer     = $mailer;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param ForgetPasswordCommand $command
     *
     * @throws \Exception
     *
     * @return null|string Generated reset token (NULL if user not found).
     */
    public function handle(ForgetPasswordCommand $command): ?string
    {
        /** @var null|\eTraxis\SecurityDomain\Model\Entity\User $user */
        $user = $this->repository->findOneByUsername($command->email);

        if ($user === null || $user->isAccountExternal()) {
            return null;
        }

        // Token expires in 2 hours.
        $token = $user->generateResetToken(new \DateInterval('PT2H'));
        $this->repository->persist($user);

        $this->mailer->send(
            $user->email,
            $user->fullname,
            $this->translator->trans('email.forgot_password.subject', [], null, $user->locale),
            'security/forgot/email.html.twig',
            [
                'locale' => $user->locale,
                'token'  => $token,
            ]
        );

        return $token;
    }
}
