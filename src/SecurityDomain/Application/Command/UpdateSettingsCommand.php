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

namespace eTraxis\SecurityDomain\Application\Command;

use Symfony\Component\Validator\Constraints as Assert;
use Webinarium\DataTransferObjectTrait;

/**
 * Updates profile info of the current user.
 *
 * @property string $locale   New locale.
 * @property string $theme    New theme.
 * @property string $timezone New timezone.
 */
class UpdateSettingsCommand
{
    use DataTransferObjectTrait;

    /**
     * @Assert\NotNull
     * @Assert\Choice(callback={"eTraxis\SecurityDomain\Model\Dictionary\Locale", "keys"}, strict=true)
     */
    public $locale;

    /**
     * @Assert\NotNull
     * @Assert\Choice(callback={"eTraxis\SecurityDomain\Model\Dictionary\Theme", "keys"}, strict=true)
     */
    public $theme;

    /**
     * @Assert\NotNull
     * @Assert\Choice(callback={"eTraxis\SecurityDomain\Model\Dictionary\Timezone", "values"}, strict=true)
     */
    public $timezone;
}
