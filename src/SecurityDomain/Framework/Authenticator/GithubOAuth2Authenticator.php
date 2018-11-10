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
use League\OAuth2\Client\Provider\Github;
use League\OAuth2\Client\Token\AccessToken;
use League\Tactician\CommandBus;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Authenticates user against GitHub OAuth2 server.
 */
class GithubOAuth2Authenticator extends AbstractOAuth2Authenticator
{
    // ID to distinguish states of several providers.
    protected const PROVIDER_ID = AccountProvider::GITHUB;

    protected $commandBus;

    /** @var null|Github */
    protected $provider;

    /**
     * Dependency Injection constructor.
     *
     * @param RouterInterface  $router
     * @param SessionInterface $session
     * @param CommandBus       $commandBus
     * @param null|string      $clientId
     * @param null|string      $clientSecret
     */
    public function __construct(
        RouterInterface  $router,
        SessionInterface $session,
        CommandBus       $commandBus,
        ?string          $clientId,
        ?string          $clientSecret
    )
    {
        parent::__construct($router, $session);

        $this->commandBus = $commandBus;

        if ($clientId && $clientSecret) {
            $this->provider = new Github([
                'clientId'     => $clientId,
                'clientSecret' => $clientSecret,
                'redirectUri'  => $router->generate('oauth_github', [], RouterInterface::ABSOLUTE_URL),
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getScope(): array
    {
        return [
            'user:email',
        ];
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
    protected function getUserFromToken(AccessToken $token): ?UserInterface
    {
        /** @var \League\OAuth2\Client\Provider\GithubResourceOwner $owner */
        $owner = $this->provider->getResourceOwner($token);

        $command = new RegisterExternalAccountCommand([
            'provider' => AccountProvider::GITHUB,
            'uid'      => $owner->getId(),
            'email'    => $owner->getEmail(),
            'fullname' => $owner->getName(),
        ]);

        if (!$command->email) {

            $request = $this->provider->getAuthenticatedRequest(
                Request::METHOD_GET,
                'https://api.github.com/user/emails',
                $token
            );

            $response = $this->provider->getResponse($request);
            $emails   = json_decode($response->getBody()->getContents(), true);

            foreach ($emails as $email) {
                if ($email['primary'] ?? false) {
                    $command->email = $email['email'];
                    break;
                }
            }
        }

        return $this->commandBus->handle($command);
    }
}
