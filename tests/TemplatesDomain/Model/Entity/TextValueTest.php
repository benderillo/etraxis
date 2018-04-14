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

use eTraxis\Tests\ReflectionTrait;
use PHPUnit\Framework\TestCase;

class TextValueTest extends TestCase
{
    use ReflectionTrait;

    public function testConstruct()
    {
        $expected = str_pad(null, 4000, '_');
        $text     = new TextValue($expected);

        self::assertSame(md5($expected), $this->getProperty($text, 'token'));
        self::assertSame($expected, $text->value);
    }
}
