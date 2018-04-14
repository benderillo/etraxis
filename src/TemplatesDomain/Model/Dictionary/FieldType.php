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

namespace eTraxis\TemplatesDomain\Model\Dictionary;

use Dictionary\StaticDictionary;

/**
 * Field types.
 */
class FieldType extends StaticDictionary
{
    public const NUMBER   = 'number';
    public const DECIMAL  = 'decimal';
    public const STRING   = 'string';
    public const TEXT     = 'text';
    public const CHECKBOX = 'checkbox';
    public const LIST     = 'list';
    public const ISSUE    = 'issue';
    public const DATE     = 'date';
    public const DURATION = 'duration';

    protected static $dictionary = [
        self::NUMBER   => 'field.type.number',
        self::DECIMAL  => 'field.type.decimal',
        self::STRING   => 'field.type.string',
        self::TEXT     => 'field.type.text',
        self::CHECKBOX => 'field.type.checkbox',
        self::LIST     => 'field.type.list',
        self::ISSUE    => 'field.type.issue',
        self::DATE     => 'field.type.date',
        self::DURATION => 'field.type.duration',
    ];
}
