<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2014-2016 Artem Rodygin
//
//  You should have received a copy of the GNU General Public License
//  along with the file. If not, see <http://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace eTraxis\SecurityDomain\Application\EventSubscriber;

use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class StickyLocaleTest extends TransactionalTestCase
{
    /** @var \Symfony\Component\HttpFoundation\RequestStack */
    protected $request_stack;

    /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface */
    protected $session;

    protected function setUp()
    {
        parent::setUp();

        $this->request_stack = $this->client->getContainer()->get('request_stack');
        $this->session       = $this->client->getContainer()->get('session');
    }

    public function testGetSubscribedEvents()
    {
        $expected = [
            'security.interactive_login',
            'kernel.request',
        ];

        self::assertSame($expected, array_keys(StickyLocale::getSubscribedEvents()));
    }

    public function testSaveLocale()
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'artem@example.com']);

        $user->locale = 'ru';

        $request = new Request();
        $token   = new UsernamePasswordToken($user, null, 'etraxis_provider');

        $event = new InteractiveLoginEvent($request, $token);

        $object = new StickyLocale($this->session, 'en');
        $object->saveLocale($event);

        self::assertSame('ru', $this->session->get('_locale'));
    }

    public function testSetDefaultLocale()
    {
        $request = new Request();

        $request->setSession($this->session);
        $request->cookies->set($this->session->getName(), $this->session->getId());

        $this->request_stack->push($request);

        $event = new GetResponseEvent(static::$kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $object = new StickyLocale($this->session, 'ru');

        $object->setLocale($event);

        self::assertSame('ru', $event->getRequest()->getLocale());
    }

    public function testSetLocaleBySession()
    {
        $request = new Request();

        $request->setSession($this->session);
        $request->cookies->set($this->session->getName(), $this->session->getId());
        $this->session->set('_locale', 'ja');

        $this->request_stack->push($request);

        $event = new GetResponseEvent(static::$kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $object = new StickyLocale($this->session, 'ru');

        $object->setLocale($event);

        self::assertSame('ja', $event->getRequest()->getLocale());
    }
}
