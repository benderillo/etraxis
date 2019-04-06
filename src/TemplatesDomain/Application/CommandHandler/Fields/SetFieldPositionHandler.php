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

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\TemplatesDomain\Application\Command\Fields\SetFieldPositionCommand;
use eTraxis\TemplatesDomain\Application\Voter\FieldVoter;
use eTraxis\TemplatesDomain\Model\Entity\Field;
use eTraxis\TemplatesDomain\Model\Repository\FieldRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class SetFieldPositionHandler
{
    protected $security;
    protected $repository;
    protected $manager;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param FieldRepository               $repository
     * @param EntityManagerInterface        $manager
     */
    public function __construct(AuthorizationCheckerInterface $security, FieldRepository $repository, EntityManagerInterface $manager)
    {
        $this->security   = $security;
        $this->repository = $repository;
        $this->manager    = $manager;
    }

    /**
     * Command handler.
     *
     * @param SetFieldPositionCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function handle(SetFieldPositionCommand $command)
    {
        /** @var null|\eTraxis\TemplatesDomain\Model\Entity\Field $field */
        $field = $this->repository->find($command->field);

        if (!$field || $field->isRemoved) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(FieldVoter::UPDATE_FIELD, $field)) {
            throw new AccessDeniedHttpException();
        }

        $fields = $field->state->fields;

        $count = count($fields);

        if ($command->position > $count) {
            $command->position = $count;
        }

        $oldPosition = $field->position;

        $this->setPosition($field, 0);

        if ($oldPosition < $command->position) {
            // Moving the field down.
            for ($i = $oldPosition; $i < $command->position; $i++) {
                $this->setPosition($fields[$i], $i);
            }
        }
        elseif ($oldPosition > $command->position) {
            // Moving the field up.
            for ($i = $oldPosition; $i > $command->position; $i--) {
                $this->setPosition($fields[$i - 2], $i);
            }
        }

        $this->setPosition($field, $command->position);
    }

    /**
     * Sets new position for specified field.
     *
     * @param Field $field
     * @param int   $position
     */
    protected function setPosition(Field $field, int $position): void
    {
        $query = $this->manager->createQuery('
            UPDATE TemplatesDomain:Field f
            SET f.position = :position
            WHERE f.id = :field
        ');

        $query->execute([
            'field'    => $field->id,
            'position' => $position,
        ]);
    }
}
