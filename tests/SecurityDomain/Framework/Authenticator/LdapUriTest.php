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

class LdapUriTest extends TestCase
{
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

    public function testInvalidSchema()
    {
        self::expectException(UriException::class);

        LdapUri::createFromString('ssh://root:secret@example.com');
    }

    public function testEmptyHost()
    {
        self::expectException(UriException::class);

        LdapUri::createFromString('ldap://root:secret@');
    }

    public function testInvalidType()
    {
        self::expectException(UriException::class);

        LdapUri::createFromString('ldap://example.com?type=acme');
    }
}
