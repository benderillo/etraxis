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

class Sha1PasswordEncoderTest extends TestCase
{
    /** @var Sha1PasswordEncoder */
    protected $encoder;

    protected function setUp()
    {
        parent::setUp();

        $this->encoder = new Sha1PasswordEncoder();
    }

    public function testEncodePassword()
    {
        self::assertSame('mzMEbtOdGC462vqQRa1nh9S7wyE=', $this->encoder->encodePassword('legacy'));
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
        $encoded = 'mzMEbtOdGC462vqQRa1nh9S7wyE=';
        $valid   = 'legacy';
        $invalid = 'invalid';

        self::assertTrue($this->encoder->isPasswordValid($encoded, $valid));
        self::assertFalse($this->encoder->isPasswordValid($encoded, $invalid));
    }
}
