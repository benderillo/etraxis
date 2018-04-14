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

namespace eTraxis\TemplatesDomain\Model\FieldTypes;

use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Dictionary\StateType;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Entity\Project;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\TemplatesDomain\Model\Entity\Template;
use eTraxis\Tests\ReflectionTrait;
use PHPUnit\Framework\TestCase;

class CheckboxTraitTest extends TestCase
{
    use ReflectionTrait;

    /** @var Field */
    protected $object;

    protected function setUp()
    {
        parent::setUp();

        $state = new State(new Template(new Project()), StateType::INTERMEDIATE);

        $this->object = new Field($state, FieldType::CHECKBOX);
        $this->setProperty($this->object, 'id', 1);
    }

    public function testDefaultValue()
    {
        $field      = $this->object->asCheckbox();
        $parameters = $this->getProperty($this->object, 'parameters');

        $field->setDefaultValue(true);
        self::assertTrue($field->getDefaultValue());
        self::assertSame(1, $this->getProperty($parameters, 'defaultValue'));

        $field->setDefaultValue(false);
        self::assertFalse($field->getDefaultValue());
        self::assertSame(0, $this->getProperty($parameters, 'defaultValue'));
    }
}
