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

use eTraxis\SecurityDomain\Model\Dictionary\Locale;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension for user's locale.
 */
class LocaleExtension extends AbstractExtension
{
    public const LEFT_TO_RIGHT = 'ltr';
    public const RIGHT_TO_LEFT = 'rtl';

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        $options = [
            'pre_escape' => 'html',
            'is_safe'    => ['html'],
        ];

        return [
            new TwigFilter('direction', [$this, 'filterDirection'], $options),
            new TwigFilter('language', [$this, 'filterLanguage'], $options),
        ];
    }

    /**
     * Returns language direction ("ltr" or "rtl") for specified locale.
     *
     * @param string $locale
     *
     * @return string
     */
    public function filterDirection(string $locale): string
    {
        $rtl = ['ar', 'fa', 'he'];

        return in_array(mb_substr($locale, 0, 2), $rtl, true) ? self::RIGHT_TO_LEFT : self::LEFT_TO_RIGHT;
    }

    /**
     * Returns translated language name for specified locale.
     *
     * @param string $locale
     *
     * @return null|string
     */
    public function filterLanguage(string $locale): ?string
    {
        return Locale::has($locale) ? Locale::get($locale) : null;
    }
}
