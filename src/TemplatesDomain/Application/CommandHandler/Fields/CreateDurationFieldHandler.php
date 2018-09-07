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

use eTraxis\TemplatesDomain\Application\Command\Fields\CreateDurationFieldCommand;
use eTraxis\TemplatesDomain\Model\Entity\Field;

/**
 * Command handler.
 */
class CreateDurationFieldHandler extends AbstractCreateFieldHandler
{
    use HandlerTrait\DurationHandlerTrait;

    /**
     * Command handler.
     *
     * @param CreateDurationFieldCommand $command
     *
     * @return Field
     */
    public function handle(CreateDurationFieldCommand $command): Field
    {
        return $this->create($command);
    }
}
