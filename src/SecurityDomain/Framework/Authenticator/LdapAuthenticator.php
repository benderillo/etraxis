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
use eTraxis\SecurityDomain\Model\Dictionary\LdapServerType;
use League\Tactician\CommandBus;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Authenticates user against LDAP server.
 */
class LdapAuthenticator extends AbstractAuthenticator
{
    use TargetPathTrait;

    protected $commandBus;
    protected $basedn;

    /** @var LdapUri */
    protected $uri;

    /** @var LdapInterface */
    protected $ldap;

    /**
     * Dependency Injection constructor.
     *
     * @param RouterInterface  $router
     * @param SessionInterface $session
     * @param CommandBus       $commandBus
     * @param null|string      $url
     * @param null|string      $basedn
     */
    public function __construct(
        RouterInterface  $router,
        SessionInterface $session,
        CommandBus       $commandBus,
        ?string          $url,
        ?string          $basedn
    )
    {
        parent::__construct($router, $session);

        $this->commandBus = $commandBus;
        $this->basedn     = $basedn;

        $this->uri = LdapUri::createFromString($url ?? 'null://localhost');

        if ($this->uri->getScheme() !== LdapUri::SCHEMA_NULL) {
            $this->ldap = Ldap::create('ext_ldap', [
                'host'       => $this->uri->getHost(),
                'port'       => $this->uri->getPort() ?? 389,
                'encryption' => $this->uri->getEncryption(),
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Request $request)
    {
        if ($this->uri->getScheme() === LdapUri::SCHEMA_NULL) {
            return false;
        }

        return parent::supports($request);
    }

    /**
     * {@inheritdoc}
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $this->ldap->bind($this->uri->getBindUser(), $this->uri->getBindPassword());

        $username = $this->ldap->escape($credentials['username'], null, LdapInterface::ESCAPE_FILTER);
        $query    = $this->ldap->query($this->basedn, sprintf('(mail=%s)', $username));
        $entries  = $query->execute();

        if (count($entries) === 0) {
            throw new UsernameNotFoundException();
        }

        $attributes = $entries[0]->getAttributes();

        $attrname = LdapServerType::get($this->uri->getType());

        $uid      = $attributes[$attrname][0] ?? null;
        $fullname = $attributes['cn'][0]      ?? null;

        if ($uid === null || $fullname === null) {
            throw new UsernameNotFoundException();
        }

        $command = new RegisterExternalAccountCommand([
            'provider' => AccountProvider::LDAP,
            'uid'      => $uid,
            'email'    => $credentials['username'],
            'fullname' => $fullname,
        ]);

        return $this->commandBus->handle($command);
    }

    /**
     * {@inheritdoc}
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        try {
            $attrname = LdapServerType::get($this->uri->getType());

            /** @var \eTraxis\SecurityDomain\Model\Entity\User $user */
            $dn = sprintf('%s=%s,%s', $attrname, $user->account->uid, $this->basedn);
            $this->ldap->bind($dn, $credentials['password']);
        }
        catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return null;
    }
}
