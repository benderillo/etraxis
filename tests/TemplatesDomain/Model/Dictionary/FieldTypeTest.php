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

use eTraxis\TemplatesDomain\Application\Command\Fields\CreateCheckboxFieldCommand;
use eTraxis\TemplatesDomain\Application\Command\Fields\CreateDateFieldCommand;
use eTraxis\TemplatesDomain\Application\Command\Fields\CreateDecimalFieldCommand;
use eTraxis\TemplatesDomain\Application\Command\Fields\CreateDurationFieldCommand;
use eTraxis\TemplatesDomain\Application\Command\Fields\CreateIssueFieldCommand;
use eTraxis\TemplatesDomain\Application\Command\Fields\CreateListFieldCommand;
use eTraxis\TemplatesDomain\Application\Command\Fields\CreateNumberFieldCommand;
use eTraxis\TemplatesDomain\Application\Command\Fields\CreateStringFieldCommand;
use eTraxis\TemplatesDomain\Application\Command\Fields\CreateTextFieldCommand;
use eTraxis\TemplatesDomain\Application\Command\Fields\UpdateCheckboxFieldCommand;
use eTraxis\TemplatesDomain\Application\Command\Fields\UpdateDateFieldCommand;
use eTraxis\TemplatesDomain\Application\Command\Fields\UpdateDecimalFieldCommand;
use eTraxis\TemplatesDomain\Application\Command\Fields\UpdateDurationFieldCommand;
use eTraxis\TemplatesDomain\Application\Command\Fields\UpdateIssueFieldCommand;
use eTraxis\TemplatesDomain\Application\Command\Fields\UpdateListFieldCommand;
use eTraxis\TemplatesDomain\Application\Command\Fields\UpdateNumberFieldCommand;
use eTraxis\TemplatesDomain\Application\Command\Fields\UpdateStringFieldCommand;
use eTraxis\TemplatesDomain\Application\Command\Fields\UpdateTextFieldCommand;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \eTraxis\TemplatesDomain\Model\Dictionary\FieldType
 */
class FieldTypeTest extends TestCase
{
    /**
     * @covers ::getCreateCommand
     */
    public function testGetCreateCommand()
    {
        self::assertSame(CreateCheckboxFieldCommand::class, FieldType::getCreateCommand(FieldType::CHECKBOX));
        self::assertSame(CreateDateFieldCommand::class, FieldType::getCreateCommand(FieldType::DATE));
        self::assertSame(CreateDecimalFieldCommand::class, FieldType::getCreateCommand(FieldType::DECIMAL));
        self::assertSame(CreateDurationFieldCommand::class, FieldType::getCreateCommand(FieldType::DURATION));
        self::assertSame(CreateIssueFieldCommand::class, FieldType::getCreateCommand(FieldType::ISSUE));
        self::assertSame(CreateListFieldCommand::class, FieldType::getCreateCommand(FieldType::LIST));
        self::assertSame(CreateNumberFieldCommand::class, FieldType::getCreateCommand(FieldType::NUMBER));
        self::assertSame(CreateStringFieldCommand::class, FieldType::getCreateCommand(FieldType::STRING));
        self::assertSame(CreateTextFieldCommand::class, FieldType::getCreateCommand(FieldType::TEXT));
    }

    /**
     * @covers ::getUpdateCommand
     */
    public function testGetUpdateCommand()
    {
        self::assertSame(UpdateCheckboxFieldCommand::class, FieldType::getUpdateCommand(FieldType::CHECKBOX));
        self::assertSame(UpdateDateFieldCommand::class, FieldType::getUpdateCommand(FieldType::DATE));
        self::assertSame(UpdateDecimalFieldCommand::class, FieldType::getUpdateCommand(FieldType::DECIMAL));
        self::assertSame(UpdateDurationFieldCommand::class, FieldType::getUpdateCommand(FieldType::DURATION));
        self::assertSame(UpdateIssueFieldCommand::class, FieldType::getUpdateCommand(FieldType::ISSUE));
        self::assertSame(UpdateListFieldCommand::class, FieldType::getUpdateCommand(FieldType::LIST));
        self::assertSame(UpdateNumberFieldCommand::class, FieldType::getUpdateCommand(FieldType::NUMBER));
        self::assertSame(UpdateStringFieldCommand::class, FieldType::getUpdateCommand(FieldType::STRING));
        self::assertSame(UpdateTextFieldCommand::class, FieldType::getUpdateCommand(FieldType::TEXT));
    }
}
