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

use eTraxis\TemplatesDomain\Application\Command\Fields\UpdateListFieldCommand;

/**
 * Command handler.
 */
class UpdateListFieldHandler extends AbstractUpdateFieldHandler
{
    use HandlerTrait\ListHandlerTrait;

    /**
     * Command handler.
     *
     * @param UpdateListFieldCommand $command
     */
    public function handle(UpdateListFieldCommand $command): void
    {
        $this->update($command);
    }
}
