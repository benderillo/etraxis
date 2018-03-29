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

namespace eTraxis\SecurityDomain\Framework\Encoder;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class Md5PasswordEncoderTest extends TestCase
{
    /** @var Md5PasswordEncoder */
    protected $encoder;

    protected function setUp()
    {
        parent::setUp();

        $this->encoder = new Md5PasswordEncoder();
    }

    public function testEncodePassword()
    {
        self::assertSame('8dbdda48fb8748d6746f1965824e966a', $this->encoder->encodePassword('simple'));
    }

    public function testEncodePasswordMaxLength()
    {
        $raw = str_pad(null, Md5PasswordEncoder::MAX_PASSWORD_LENGTH, '*');

        try {
            $this->encoder->encodePassword($raw);
        }
        catch (\Exception $exception) {
            self::fail();
        }

        self::assertTrue(true);
    }

    public function testEncodePasswordTooLong()
    {
        $this->expectException(BadCredentialsException::class);

        $raw = str_pad(null, Md5PasswordEncoder::MAX_PASSWORD_LENGTH + 1, '*');

        $this->encoder->encodePassword($raw);
    }

    public function testIsPasswordValid()
    {
        $encoded = '8dbdda48fb8748d6746f1965824e966a';
        $valid   = 'simple';
        $invalid = 'invalid';

        self::assertTrue($this->encoder->isPasswordValid($encoded, $valid));
        self::assertFalse($this->encoder->isPasswordValid($encoded, $invalid));
    }
}
