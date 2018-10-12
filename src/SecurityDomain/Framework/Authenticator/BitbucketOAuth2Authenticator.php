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
use League\OAuth2\Client\Token\AccessToken;
use League\Tactician\CommandBus;
use Stevenmaguire\OAuth2\Client\Provider\Bitbucket;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Authenticates user against Bitbucket OAuth2 server.
 */
class BitbucketOAuth2Authenticator extends AbstractOAuth2Authenticator
{
    // ID to distinguish states of several providers.
    protected const PROVIDER_ID = AccountProvider::BITBUCKET;

    protected $commandBus;

    /** @var null|Bitbucket */
    protected $provider;

    /**
     * Dependency Injection constructor.
     *
     * @param RouterInterface  $router
     * @param SessionInterface $session
     * @param CommandBus       $commandBus
     * @param string           $clientId
     * @param string           $clientSecret
     */
    public function __construct(
        RouterInterface  $router,
        SessionInterface $session,
        CommandBus       $commandBus,
        string           $clientId,
        string           $clientSecret
    )
    {
        parent::__construct($router, $session);

        $this->commandBus = $commandBus;

        if ($clientId && $clientSecret) {
            $this->provider = new Bitbucket([
                'clientId'     => $clientId,
                'clientSecret' => $clientSecret,
                'redirectUri'  => $router->generate('oauth_bitbucket', [], RouterInterface::ABSOLUTE_URL),
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getScope(): array
    {
        return [
            'account',
            'email',
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
        /** @var \Stevenmaguire\OAuth2\Client\Provider\BitbucketResourceOwner $owner */
        $owner = $this->provider->getResourceOwner($token);

        $command = new RegisterExternalAccountCommand([
            'provider' => AccountProvider::BITBUCKET,
            'uid'      => $owner->getId(),
            'fullname' => $owner->getName(),
        ]);

        $request = $this->provider->getAuthenticatedRequest(
            Request::METHOD_GET,
            'https://api.bitbucket.org/2.0/user/emails',
            $token
        );

        $response = $this->provider->getResponse($request);
        $contents = json_decode($response->getBody()->getContents(), true);
        $emails   = $contents['values'] ?? [];

        foreach ($emails as $email) {
            if ($email['is_primary'] ?? false) {
                $command->email = $email['email'];
                break;
            }
        }

        return $this->commandBus->handle($command);
    }
}
