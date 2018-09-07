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

use eTraxis\TemplatesDomain\Application\Command\Fields\CreateDateFieldCommand;
use eTraxis\TemplatesDomain\Model\Entity\Field;

/**
 * Command handler.
 */
class CreateDateFieldHandler extends AbstractCreateFieldHandler
{
    use HandlerTrait\DateHandlerTrait;

    /**
     * Command handler.
     *
     * @param CreateDateFieldCommand $command
     *
     * @return Field
     */
    public function handle(CreateDateFieldCommand $command): Field
    {
        return $this->create($command);
    }
}
