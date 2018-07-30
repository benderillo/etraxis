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

namespace eTraxis\TemplatesDomain\Model\Entity;

use PHPUnit\Framework\TestCase;

class DecimalValueTest extends TestCase
{
    public function testConstruct()
    {
        $expected = '1234567890.0987654321';
        $decimal  = new DecimalValue($expected);

        self::assertSame($expected, $decimal->value);
    }

    public function testTrim()
    {
        $decimal = new DecimalValue('0100');
        self::assertSame('100', $decimal->value);

        $decimal = new DecimalValue('03.1415000000');
        self::assertSame('3.1415', $decimal->value);

        $decimal = new DecimalValue('00.1415000000');
        self::assertSame('0.1415', $decimal->value);

        $decimal = new DecimalValue('03.0000000000');
        self::assertSame('3', $decimal->value);

        $decimal = new DecimalValue('00.0000000000');
        self::assertSame('0', $decimal->value);
    }
}
