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
}
