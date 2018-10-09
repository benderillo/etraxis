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

use eTraxis\SecurityDomain\Model\Dictionary\LdapServerType;
use League\Uri\AbstractUri;
use League\Uri\Components\Query;
use League\Uri\Components\UserInfo;

/**
 * LDAP URI.
 */
class LdapUri extends AbstractUri
{
    public const SCHEMA_NONE  = 'none';
    public const SCHEMA_LDAP  = 'ldap';
    public const SCHEMA_LDAPS = 'ldaps';

    public const ENCRYPTION_NONE = 'none';
    public const ENCRYPTION_SSL  = 'ssl';
    public const ENCRYPTION_TLS  = 'tls';

    protected static $supported_schemes = [
        self::SCHEMA_NONE  => 0,
        self::SCHEMA_LDAP  => 0,
        self::SCHEMA_LDAPS => 0,
    ];

    protected $user;
    protected $password;
    protected $encryption;
    protected $type;

    /**
     * Returns binding user.
     *
     * @return null|string
     */
    public function getBindUser(): ?string
    {
        return $this->user;
    }

    /**
     * Returns binding password.
     *
     * @return null|string
     */
    public function getBindPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Returns server encryption.
     *
     * @return string
     */
    public function getEncryption(): string
    {
        return $this->encryption;
    }

    /**
     * Returns server type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    protected function isValidUri(): bool
    {
        /** @var UserInfo $userInfo */
        $userInfo = new UserInfo();
        $userInfo = $userInfo->withContent($this->getUserInfo());

        $this->user     = $userInfo->getUser(UserInfo::NO_ENCODING);
        $this->password = $userInfo->getPass(UserInfo::NO_ENCODING);

        $query = new Query($this->getQuery());

        $this->encryption = $query->getParam('encryption', self::ENCRYPTION_NONE);
        $this->type       = $query->getParam('type', LdapServerType::FALLBACK);

        return mb_strlen($this->host) !== 0
            && isset(static::$supported_schemes[$this->scheme])
            && LdapServerType::has($this->type);
    }
}
