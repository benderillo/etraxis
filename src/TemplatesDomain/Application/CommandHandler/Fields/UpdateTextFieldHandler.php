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

use eTraxis\TemplatesDomain\Application\Command\Fields\UpdateTextFieldCommand;

/**
 * Command handler.
 */
class UpdateTextFieldHandler extends AbstractUpdateFieldHandler
{
    use HandlerTrait\TextHandlerTrait;

    /**
     * Command handler.
     *
     * @param UpdateTextFieldCommand $command
     */
    public function handle(UpdateTextFieldCommand $command): void
    {
        $this->update($command);
    }
}
