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

namespace eTraxis\SharedDomain\Framework\Twig;

use PHPUnit\Framework\TestCase;

class LocaleExtensionTest extends TestCase
{
    public function testFilters()
    {
        $expected = [
            'direction',
            'language',
        ];

        $extension = new LocaleExtension();

        $filters = array_map(function (\Twig_Filter $filter) {
            return $filter->getName();
        }, $extension->getFilters());

        self::assertSame($expected, $filters);
    }

    public function testFilterDirection()
    {
        $extension = new LocaleExtension();

        self::assertSame(LocaleExtension::LEFT_TO_RIGHT, $extension->filterDirection('en'));
        self::assertSame(LocaleExtension::RIGHT_TO_LEFT, $extension->filterDirection('ar'));
        self::assertSame(LocaleExtension::RIGHT_TO_LEFT, $extension->filterDirection('fa'));
        self::assertSame(LocaleExtension::RIGHT_TO_LEFT, $extension->filterDirection('he'));
    }

    public function testFilterLanguage()
    {
        $extension = new LocaleExtension();

        self::assertSame('Русский', $extension->filterLanguage('ru'));
    }
}
