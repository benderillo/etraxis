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

namespace eTraxis\SharedDomain\Application\Mailer;

use Psr\Log\LoggerInterface;
use Swift_Mailer;
use Twig_Environment;

/**
 * Shortcut service for standard mailer.
 */
class Mailer implements MailerInterface
{
    protected $logger;
    protected $twig;
    protected $mailer;
    protected $senderAddress;
    protected $senderName;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param LoggerInterface  $logger  Debug logger.
     * @param Twig_Environment $twig    Templates renderer.
     * @param Swift_Mailer     $mailer  Mailer service.
     * @param string           $address Email address of the sender.
     * @param string           $name    Name of the sender.
     */
    public function __construct(
        LoggerInterface  $logger,
        Twig_Environment $twig,
        Swift_Mailer     $mailer,
        ?string          $address = null,
        ?string          $name    = null
    )
    {
        $this->logger        = $logger;
        $this->twig          = $twig;
        $this->mailer        = $mailer;
        $this->senderAddress = $address;
        $this->senderName    = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function send(string $address, string $name, string $subject, string $template, array $args = [], ?callable $callback = null): bool
    {
        $this->logger->info('Send email', [$address, $name, $subject]);

        $body = $this->twig->render($template, $args);

        $message = new \Swift_Message($subject, $body, 'text/html');
        $message->setTo($address, $name);

        if ($this->senderAddress !== null) {
            $message->setSender($this->senderAddress, $this->senderName ?? null);
            $message->setFrom($this->senderAddress, $this->senderName ?? null);
        }

        if ($callback !== null) {
            $callback($message);
        }

        if (empty($message->getReturnPath()) && empty($message->getSender()) && empty($message->getFrom())) {
            return true;
        }

        return $this->mailer->send($message) !== 0;
    }
}
