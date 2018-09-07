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

use eTraxis\TemplatesDomain\Application\Command\Fields\UpdateDecimalFieldCommand;

/**
 * Command handler.
 */
class UpdateDecimalFieldHandler extends AbstractUpdateFieldHandler
{
    use HandlerTrait\DecimalHandlerTrait;

    /**
     * Command handler.
     *
     * @param UpdateDecimalFieldCommand $command
     */
    public function handle(UpdateDecimalFieldCommand $command): void
    {
        $this->update($command);
    }
}
