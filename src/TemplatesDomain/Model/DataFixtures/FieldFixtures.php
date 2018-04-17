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

namespace eTraxis\TemplatesDomain\Model\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Entity\DecimalValue;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Entity\StringValue;
use eTraxis\TemplatesDomain\Model\Entity\TextValue;
use eTraxis\TemplatesDomain\Model\FieldTypes\NumberInterface;
use eTraxis\TemplatesDomain\Model\FieldTypes\TextInterface;

/**
 * Test fixtures for 'Field' entity.
 */
class FieldFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            StateFixtures::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = [

            'new' => [
                1 => [
                    'type'     => FieldType::LIST,
                    'name'     => 'Priority',
                    'required' => true,
                ],
                2 => [
                    'type'       => FieldType::TEXT,
                    'name'       => 'Description',
                    'required'   => false,
                    'parameters' => function (Field $field) use ($manager) {
                        /** @var \eTraxis\TemplatesDomain\Model\Repository\TextValueRepository $repository */
                        $repository = $manager->getRepository(TextValue::class);

                        $field->asText($repository)
                            ->setMaximumLength(TextInterface::MAX_LENGTH);
                    },
                ],
                3 => [
                    'type'     => FieldType::CHECKBOX,
                    'name'     => 'New feature',
                    'required' => false,
                ],
            ],

            'assigned' => [
                1 => [
                    'type'       => FieldType::DATE,
                    'name'       => 'Due date',
                    'required'   => false,
                    'parameters' => function (Field $field) {
                        $field->asDate()
                            ->setMinimumValue(0)
                            ->setMaximumValue(14)
                            ->setDefaultValue(14);
                    },
                ],
            ],

            'completed' => [
                1 => [
                    'type'       => FieldType::STRING,
                    'name'       => 'Commit ID',
                    'required'   => false,
                    'parameters' => function (Field $field) use ($manager) {
                        /** @var \eTraxis\TemplatesDomain\Model\Repository\StringValueRepository $repository */
                        $repository = $manager->getRepository(StringValue::class);

                        $field->asString($repository)
                            ->setMaximumLength(40);
                    },
                ],
                2 => [
                    'type'        => FieldType::NUMBER,
                    'name'        => 'Delta',
                    'description' => 'NCLOC',
                    'required'    => true,
                    'parameters'  => function (Field $field) {
                        $field->asNumber()
                            ->setMinimumValue(0)
                            ->setMaximumValue(NumberInterface::MAX_VALUE);
                    },
                ],
                3 => [
                    'type'        => FieldType::DURATION,
                    'name'        => 'Effort',
                    'description' => 'HH:MM',
                    'required'    => true,
                    'parameters'  => function (Field $field) {
                        $field->asDuration()
                            ->setMinimumValue('0:00')
                            ->setMaximumValue('999999:59');
                    },
                ],
                4 => [
                    'type'       => FieldType::DECIMAL,
                    'name'       => 'Test coverage',
                    'required'   => false,
                    'parameters' => function (Field $field) use ($manager) {
                        /** @var \eTraxis\TemplatesDomain\Model\Repository\DecimalValueRepository $repository */
                        $repository = $manager->getRepository(DecimalValue::class);

                        $field->asDecimal($repository)
                            ->setMinimumValue('0')
                            ->setMaximumValue('100');
                    },
                ],
            ],

            'duplicated' => [
                0 => [
                    'type'     => FieldType::ISSUE,
                    'name'     => 'Task ID',
                    'required' => true,
                    'deleted'  => true,
                ],
                1 => [
                    'type'     => FieldType::ISSUE,
                    'name'     => 'Issue ID',
                    'required' => true,
                ],
            ],

            'submitted' => [
                1 => [
                    'type'       => FieldType::TEXT,
                    'name'       => 'Description',
                    'required'   => true,
                    'parameters' => function (Field $field) use ($manager) {
                        /** @var \eTraxis\TemplatesDomain\Model\Repository\TextValueRepository $repository */
                        $repository = $manager->getRepository(TextValue::class);

                        $field->asText($repository)
                            ->setMaximumLength(250);
                    },
                ],
            ],

            'opened' => [],

            'resolved' => [],
        ];

        foreach (['a', 'b', 'c'] as $pref) {

            foreach ($data as $sref => $fields) {

                /** @var \eTraxis\TemplatesDomain\Model\Entity\State $state */
                $state = $this->getReference(sprintf('%s:%s', $sref, $pref));

                foreach ($fields as $position => $row) {

                    $field = new Field($state, $row['type']);

                    $field->position    = $position;
                    $field->name        = $row['name'];
                    $field->description = $row['description'] ?? null;
                    $field->isRequired  = $row['required'];

                    if ($row['parameters'] ?? false) {
                        $row['parameters']($field);
                    }

                    if ($row['deleted'] ?? false) {
                        $field->removedAt = time();
                    }
                    else {
                        $this->addReference(sprintf('%s:%s:%s', $sref, $pref, mb_strtolower($row['name'])), $field);
                    }

                    $manager->persist($field);
                }
            }
        }

        $manager->flush();
    }
}
