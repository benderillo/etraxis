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
use League\Uri\UriException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \eTraxis\SecurityDomain\Framework\Authenticator\LdapUri
 */
class LdapUriTest extends TestCase
{
    /**
     * @covers ::getBindPassword
     * @covers ::getBindUser
     */
    public function testNone()
    {
        $uri = LdapUri::createFromString('null://example.com');

        self::assertSame(LdapUri::SCHEMA_NULL, $uri->getScheme());
        self::assertSame('example.com', $uri->getHost());
        self::assertEmpty($uri->getPort());
        self::assertSame(LdapServerType::POSIX, $uri->getType());
        self::assertSame(LdapUri::ENCRYPTION_NONE, $uri->getEncryption());
        self::assertEmpty($uri->getBindUser());
        self::assertEmpty($uri->getBindPassword());
    }

    /**
     * @covers ::getBindPassword
     * @covers ::getBindUser
     */
    public function testLdapWithUser()
    {
        $uri = LdapUri::createFromString('ldap://root@example.com');

        self::assertSame(LdapUri::SCHEMA_LDAP, $uri->getScheme());
        self::assertSame('example.com', $uri->getHost());
        self::assertEmpty($uri->getPort());
        self::assertSame(LdapServerType::POSIX, $uri->getType());
        self::assertSame(LdapUri::ENCRYPTION_NONE, $uri->getEncryption());
        self::assertSame('root', $uri->getBindUser());
        self::assertEmpty($uri->getBindPassword());
    }

    /**
     * @covers ::getBindPassword
     * @covers ::getBindUser
     */
    public function testLdapsWithUserPassword()
    {
        $uri = LdapUri::createFromString('ldaps://root:secret@example.com');

        self::assertSame(LdapUri::SCHEMA_LDAPS, $uri->getScheme());
        self::assertSame('example.com', $uri->getHost());
        self::assertEmpty($uri->getPort());
        self::assertSame(LdapServerType::POSIX, $uri->getType());
        self::assertSame(LdapUri::ENCRYPTION_NONE, $uri->getEncryption());
        self::assertSame('root', $uri->getBindUser());
        self::assertSame('secret', $uri->getBindPassword());
    }

    /**
     * @coversNothing
     */
    public function testPort()
    {
        $uri = LdapUri::createFromString('ldap://example.com:389');

        self::assertSame(LdapUri::SCHEMA_LDAP, $uri->getScheme());
        self::assertSame('example.com', $uri->getHost());
        self::assertSame(389, $uri->getPort());
        self::assertSame(LdapServerType::POSIX, $uri->getType());
        self::assertSame(LdapUri::ENCRYPTION_NONE, $uri->getEncryption());
        self::assertEmpty($uri->getBindUser());
        self::assertEmpty($uri->getBindPassword());
    }

    /**
     * @covers ::getType
     */
    public function testType()
    {
        $uri = LdapUri::createFromString('ldap://example.com?type=win2000');

        self::assertSame(LdapUri::SCHEMA_LDAP, $uri->getScheme());
        self::assertSame('example.com', $uri->getHost());
        self::assertEmpty($uri->getPort());
        self::assertSame(LdapServerType::WIN2000, $uri->getType());
        self::assertSame(LdapUri::ENCRYPTION_NONE, $uri->getEncryption());
        self::assertEmpty($uri->getBindUser());
        self::assertEmpty($uri->getBindPassword());
    }

    /**
     * @covers ::getEncryption
     */
    public function testEncryption()
    {
        $uri = LdapUri::createFromString('ldap://example.com?encryption=tls');

        self::assertSame(LdapUri::SCHEMA_LDAP, $uri->getScheme());
        self::assertSame('example.com', $uri->getHost());
        self::assertEmpty($uri->getPort());
        self::assertSame(LdapServerType::POSIX, $uri->getType());
        self::assertSame(LdapUri::ENCRYPTION_TLS, $uri->getEncryption());
        self::assertEmpty($uri->getBindUser());
        self::assertEmpty($uri->getBindPassword());
    }

    /**
     * @covers ::getBindPassword
     * @covers ::getBindUser
     * @covers ::getEncryption
     * @covers ::getType
     */
    public function testMaximum()
    {
        $uri = LdapUri::createFromString('ldaps://root:secret@example.com:636?type=winnt&encryption=ssl');

        self::assertSame(LdapUri::SCHEMA_LDAPS, $uri->getScheme());
        self::assertSame('example.com', $uri->getHost());
        self::assertSame(636, $uri->getPort());
        self::assertSame(LdapServerType::WINNT, $uri->getType());
        self::assertSame(LdapUri::ENCRYPTION_SSL, $uri->getEncryption());
        self::assertSame('root', $uri->getBindUser());
        self::assertSame('secret', $uri->getBindPassword());
    }

    /**
     * @covers ::isValidUri
     */
    public function testInvalidSchema()
    {
        self::expectException(UriException::class);

        LdapUri::createFromString('ssh://root:secret@example.com');
    }

    /**
     * @covers ::isValidUri
     */
    public function testEmptyHost()
    {
        self::expectException(UriException::class);

        LdapUri::createFromString('ldap://root:secret@');
    }

    /**
     * @covers ::isValidUri
     */
    public function testInvalidType()
    {
        self::expectException(UriException::class);

        LdapUri::createFromString('ldap://example.com?type=acme');
    }
}
