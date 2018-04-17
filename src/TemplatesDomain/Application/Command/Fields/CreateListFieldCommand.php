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

namespace eTraxis\TemplatesDomain\Application\Command\Fields;

use Webinarium\DataTransferObjectTrait;

/**
 * Creates new "list" field.
 */
class CreateListFieldCommand extends AbstractCreateFieldCommand
{
    use DataTransferObjectTrait;
    use CommandTrait\ListCommandTrait;
}
