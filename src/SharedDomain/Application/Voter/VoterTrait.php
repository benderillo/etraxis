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

namespace eTraxis\SharedDomain\Application\Voter;

/**
 * A trait for supported attributes.
 *
 * The trait requires an array which must be declared as property named '$attributes'.
 * Each key of the array is an attribute name, value - class of the subject (use 'null' if subject is not required).
 *
 * Example:
 *
 * protected $attributes = [
 *     'create' => null,
 *     'update' => MyEntity::class,
 *     'delete' => MyEntity::class,
 * ];
 */
trait VoterTrait
{
    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {
        // Whether the attribute is supported.
        if (!array_key_exists($attribute, $this->attributes)) {
            return false;
        }

        // Whether the subject is not required.
        if ($this->attributes[$attribute] === null) {
            return true;
        }

        // Subject may be a Doctrine Proxy class,
        // e.g. 'Proxies\__CG__\App\Entity\MyEntity' instead of 'App\Entity\MyEntity'.
        $class = mb_substr(get_class($subject), -mb_strlen($this->attributes[$attribute]));

        // The subject must be an object of expected class.
        return is_object($subject) && $class === $this->attributes[$attribute];
    }
}
