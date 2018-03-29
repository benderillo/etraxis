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

use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MailerTest extends WebTestCase
{
    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var \Twig_Environment */
    protected $twig;

    /** @var \Swift_Mailer */
    protected $mailer;

    protected function setUp()
    {
        $client = static::createClient();

        $this->logger = new NullLogger();
        $this->twig   = $client->getContainer()->get('twig');
        $this->mailer = $client->getContainer()->get('mailer');
    }

    public function testFullSender()
    {
        $service = new Mailer($this->logger, $this->twig, $this->mailer, 'noreply@example.com', 'Test Mailer');

        $result = $service->send(
            'anna@example.com',
            'Anna Rodygina',
            'Test subject',
            'email.html.twig',
            ['message' => 'Test message'],
            function (\Swift_Message $message) {
                self::assertSame('text/html', $message->getContentType());
                self::assertSame('Test subject', $message->getSubject());
                self::assertSame(['noreply@example.com' => 'Test Mailer'], $message->getSender());
                self::assertSame(['noreply@example.com' => 'Test Mailer'], $message->getFrom());
                self::assertSame(['anna@example.com' => 'Anna Rodygina'], $message->getTo());
                self::assertSame($this->twig->render('email.html.twig'), $message->getBody());
            }
        );

        self::assertTrue($result);
    }

    public function testAddressOnlySender()
    {
        $service = new Mailer($this->logger, $this->twig, $this->mailer, 'noreply@example.com');

        $result = $service->send(
            'anna@example.com',
            'Anna Rodygina',
            'Test subject',
            'email.html.twig',
            ['message' => 'Test message'],
            function (\Swift_Message $message) {
                self::assertSame('text/html', $message->getContentType());
                self::assertSame('Test subject', $message->getSubject());
                self::assertSame(['noreply@example.com' => null], $message->getSender());
                self::assertSame(['noreply@example.com' => null], $message->getFrom());
                self::assertSame(['anna@example.com' => 'Anna Rodygina'], $message->getTo());
                self::assertSame($this->twig->render('email.html.twig'), $message->getBody());
            }
        );

        self::assertTrue($result);
    }

    public function testNoSender()
    {
        $service = new Mailer($this->logger, $this->twig, $this->mailer);

        $result = $service->send(
            'anna@example.com',
            'Anna Rodygina',
            'Test subject',
            'email.html.twig',
            ['message' => 'Test message'],
            function (\Swift_Message $message) {
                self::assertSame('text/html', $message->getContentType());
                self::assertSame('Test subject', $message->getSubject());
                self::assertNull($message->getSender());
                self::assertSame(['anna@example.com' => 'Anna Rodygina'], $message->getTo());
                self::assertSame($this->twig->render('email.html.twig'), $message->getBody());
            }
        );

        self::assertTrue($result);
    }
}
