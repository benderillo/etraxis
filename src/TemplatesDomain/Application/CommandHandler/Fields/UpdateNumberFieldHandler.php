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

namespace eTraxis\TemplatesDomain\Application\CommandHandler\Fields;

use eTraxis\TemplatesDomain\Application\Command\Fields\UpdateNumberFieldCommand;

/**
 * Command handler.
 */
class UpdateNumberFieldHandler extends AbstractUpdateFieldHandler
{
    use HandlerTrait\NumberHandlerTrait;

    /**
     * Command handler.
     *
     * @param UpdateNumberFieldCommand $command
     */
    public function handle(UpdateNumberFieldCommand $command): void
    {
        $this->update($command);
    }
}
