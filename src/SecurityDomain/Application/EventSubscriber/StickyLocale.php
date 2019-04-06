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

namespace eTraxis\SecurityDomain\Application\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

/**
 * "Sticky" locale.
 */
class StickyLocale implements EventSubscriberInterface
{
    protected $session;
    protected $locale;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param SessionInterface $session
     * @param string           $locale
     */
    public function __construct(SessionInterface $session, string $locale)
    {
        $this->session = $session;
        $this->locale  = $locale;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'saveLocale',
            KernelEvents::REQUEST             => ['setLocale', 20],
        ];
    }

    /**
     * Saves user's locale when one has been authenticated.
     *
     * @param InteractiveLoginEvent $event
     */
    public function saveLocale(InteractiveLoginEvent $event): void
    {
        /** @var \eTraxis\SecurityDomain\Model\Entity\User $user */
        $user = $event->getAuthenticationToken()->getUser();

        $this->session->set('_locale', $user->locale);
    }

    /**
     * Overrides current locale with one saved in the session.
     *
     * @param GetResponseEvent $event
     */
    public function setLocale(GetResponseEvent $event): void
    {
        $request = $event->getRequest();

        if ($request->hasPreviousSession()) {
            $request->setLocale($request->getSession()->get('_locale', $this->locale));
        }
    }
}
