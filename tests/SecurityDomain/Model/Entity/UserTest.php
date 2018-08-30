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

namespace eTraxis\SecurityDomain\Model\Entity;

use eTraxis\SecurityDomain\Model\Dictionary\AccountProvider;
use eTraxis\SecurityDomain\Model\Dictionary\Locale;
use eTraxis\SecurityDomain\Model\Dictionary\Theme;
use eTraxis\SecurityDomain\Model\Dictionary\Timezone;
use eTraxis\Tests\ReflectionTrait;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    use ReflectionTrait;

    public function testConstructor()
    {
        $user = new User();

        self::assertSame(['ROLE_USER'], $user->getRoles());
        self::assertSame(AccountProvider::ETRAXIS, $user->account->provider);
        self::assertRegExp('/^([[:xdigit:]]{32})$/is', $user->account->uid);
    }

    public function testUsername()
    {
        $user = new User();
        self::assertNotSame('anna@example.com', $user->getUsername());

        $user->email = 'anna@example.com';
        self::assertSame('anna@example.com', $user->getUsername());
    }

    public function testPassword()
    {
        $user = new User();
        self::assertNotSame('secret', $user->getPassword());

        $token = $user->generateResetToken(new \DateInterval('PT2H'));
        self::assertTrue($user->isResetTokenValid($token));

        $user->password = 'secret';
        self::assertSame('secret', $user->getPassword());
        self::assertFalse($user->isResetTokenValid($token));
    }

    public function testRoles()
    {
        $user = new User();
        self::assertSame(['ROLE_USER'], $user->getRoles());

        $user->isAdmin = true;
        self::assertSame(['ROLE_ADMIN'], $user->getRoles());

        $user->isAdmin = false;
        self::assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testIsAccountExternal()
    {
        $user = new User();
        self::assertFalse($user->isAccountExternal());

        $user->account->provider = AccountProvider::LDAP;
        self::assertTrue($user->isAccountExternal());

        $user->account->provider = AccountProvider::ETRAXIS;
        self::assertFalse($user->isAccountExternal());
    }

    public function testEncoderName()
    {
        $user = new User();

        // md5
        $user->password = '8dbdda48fb8748d6746f1965824e966a';
        self::assertSame('legacy.md5', $user->getEncoderName());

        // sha1
        $user->password = 'mzMEbtOdGC462vqQRa1nh9S7wyE=';
        self::assertSame('legacy.sha1', $user->getEncoderName());

        // bcrypt
        $user->password = '$2y$13$892p0g2hOe1cW5m5YRr32uvNJLTsE4Y20IALX1EseRbi6a9zVFDFy';
        self::assertNull($user->getEncoderName());
    }

    public function testJsonSerialize()
    {
        $expected = [
            'id'          => 123,
            'email'       => 'anna@example.com',
            'fullname'    => 'Anna Rodygina',
            'description' => null,
            'admin'       => false,
            'disabled'    => false,
            'locked'      => false,
            'provider'    => AccountProvider::FALLBACK,
            'locale'      => Locale::FALLBACK,
            'theme'       => Theme::FALLBACK,
            'timezone'    => Timezone::FALLBACK,
        ];

        $user = new User();

        $this->setProperty($user, 'id', 123);

        $user->email    = 'anna@example.com';
        $user->fullname = 'Anna Rodygina';

        self::assertSame($expected, $user->jsonSerialize());
    }

    public function testIsAdmin()
    {
        $user = new User();
        self::assertFalse($user->isAdmin);

        $user->isAdmin = true;
        self::assertTrue($user->isAdmin);

        $user->isAdmin = false;
        self::assertFalse($user->isAdmin);
    }

    public function testLocale()
    {
        $user = new User();
        self::assertSame('en_US', $user->locale);

        $user->locale = 'ru';
        self::assertSame('ru', $user->locale);

        $user->locale = 'xx';
        self::assertSame('ru', $user->locale);
    }

    public function testTheme()
    {
        $user = new User();
        self::assertSame('azure', $user->theme);

        $user->theme = 'emerald';
        self::assertSame('emerald', $user->theme);

        $user->theme = 'unknown';
        self::assertSame('emerald', $user->theme);
    }

    public function testTimezone()
    {
        $user = new User();
        self::assertSame('UTC', $user->timezone);

        $user->timezone = 'Pacific/Auckland';
        self::assertSame('Pacific/Auckland', $user->timezone);

        $user->timezone = 'Unknown';
        self::assertSame('Pacific/Auckland', $user->timezone);
    }

    public function testGroups()
    {
        $user = new User();
        self::assertSame([], $user->groups);

        /** @var \Doctrine\Common\Collections\ArrayCollection $groups */
        $groups = $this->getProperty($user, 'groupsCollection');
        $groups->add('Group A');
        $groups->add('Group B');

        self::assertSame(['Group A', 'Group B'], $user->groups);
    }

    public function testCanAccountBeLocked()
    {
        $user = new User();
        self::assertTrue($this->callMethod($user, 'canAccountBeLocked'));

        $user->account->provider = AccountProvider::LDAP;
        self::assertFalse($this->callMethod($user, 'canAccountBeLocked'));

        $user->account->provider = AccountProvider::ETRAXIS;
        self::assertTrue($this->callMethod($user, 'canAccountBeLocked'));
    }
}
