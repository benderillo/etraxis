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

class FieldPCRETest extends TestCase
{
    use ReflectionTrait;

    public function testValidate()
    {
        $pcre = new FieldPCRE();

        $pcre->check = '(\d{3})-(\d{3})-(\d{4})';

        self::assertTrue($pcre->validate('123-456-7890'));
        self::assertFalse($pcre->validate('123-456-789'));
        self::assertFalse($pcre->validate('abc-def-ghij'));
        self::assertFalse($pcre->validate(''));
        self::assertFalse($pcre->validate(null));
    }

    public function testTransform()
    {
        $expected = [
            '123-456-7890' => '(123) 456-7890',
            '123-456-789'  => '123-456-789',
            'abc-def-ghij' => 'abc-def-ghij',
            ''             => '',
            null           => '',
        ];

        $pcre = new FieldPCRE();

        $pcre->search  = '(\d{3})-(\d{3})-(\d{4})';
        $pcre->replace = '($1) $2-$3';

        foreach ($expected as $from => $to) {
            self::assertSame($to, $pcre->transform($from));
        }
    }

    public function testTransform1()
    {
        $expected = '123-456-7890';

        $pcre = new FieldPCRE();

        $pcre->search = '(\d{3})-(\d{3})-(\d{4})';
        self::assertSame($expected, $pcre->transform($expected));

        $pcre->replace = '($1) $2-$3';
        self::assertNotSame($expected, $pcre->transform($expected));
    }

    public function testTransform2()
    {
        $expected = '123-456-7890';

        $pcre = new FieldPCRE();

        $pcre->replace = '($1) $2-$3';
        self::assertSame($expected, $pcre->transform($expected));

        $pcre->search = '(\d{3})-(\d{3})-(\d{4})';
        self::assertNotSame($expected, $pcre->transform($expected));
    }
}
