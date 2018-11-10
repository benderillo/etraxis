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

namespace eTraxis\SecurityDomain\Framework\Authenticator;

use eTraxis\SecurityDomain\Application\Command\Users\RegisterExternalAccountCommand;
use eTraxis\SecurityDomain\Model\Dictionary\AccountProvider;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Token\AccessToken;
use League\Tactician\CommandBus;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Authenticates user against Google OAuth2 server.
 */
class GoogleOAuth2Authenticator extends AbstractOAuth2Authenticator
{
    // ID to distinguish states of several providers.
    protected const PROVIDER_ID = AccountProvider::GOOGLE;

    protected $commandBus;

    /** @var null|Google */
    protected $provider;

    /**
     * Dependency Injection constructor.
     *
     * @param RouterInterface  $router
     * @param SessionInterface $session
     * @param CommandBus       $commandBus
     * @param null|string      $clientId
     * @param null|string      $clientSecret
     * @param null|string      $clientDomain
     */
    public function __construct(
        RouterInterface  $router,
        SessionInterface $session,
        CommandBus       $commandBus,
        ?string          $clientId,
        ?string          $clientSecret,
        ?string          $clientDomain
    )
    {
        parent::__construct($router, $session);

        $this->commandBus = $commandBus;

        if ($clientId && $clientSecret) {
            $this->provider = new Google([
                'clientId'     => $clientId,
                'clientSecret' => $clientSecret,
                'hostedDomain' => $clientDomain,
                'redirectUri'  => $router->generate('oauth_google', [], RouterInterface::ABSOLUTE_URL),
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getProvider(): ?AbstractProvider
    {
        return $this->provider;
    }

    /**
     * {@inheritdoc}
     */
    protected function getScope(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserFromToken(AccessToken $token): ?UserInterface
    {
        /** @var \League\OAuth2\Client\Provider\GoogleUser $owner */
        $owner = $this->provider->getResourceOwner($token);

        $command = new RegisterExternalAccountCommand([
            'provider' => AccountProvider::GOOGLE,
            'uid'      => $owner->getId(),
            'email'    => $owner->getEmail(),
            'fullname' => $owner->getName(),
        ]);

        return $this->commandBus->handle($command);
    }
}
