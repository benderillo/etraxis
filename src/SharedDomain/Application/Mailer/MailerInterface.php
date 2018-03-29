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

/**
 * Mailer interface.
 */
interface MailerInterface
{
    /**
     * Sends an email to specified recipient.
     *
     * @param string   $address  Recipient address.
     * @param string   $name     Recipient name.
     * @param string   $subject  Email subject.
     * @param string   $template Path to Twig template of the email body.
     * @param array    $args     Twig template parameters.
     * @param callable $callback A function to call before sending.
     *                           The function receives created "Swift_Message" as its parameter.
     *
     * @return bool Whether the email was accepted for delivery.
     */
    public function send(string $address, string $name, string $subject, string $template, array $args = [], ?callable $callback = null): bool;
}
