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

namespace eTraxis\TemplatesDomain\Application\CommandHandler\States;

use Doctrine\ORM\EntityManagerInterface;
use eTraxis\TemplatesDomain\Application\Command\States\SetInitialStateCommand;
use eTraxis\TemplatesDomain\Application\Voter\StateVoter;
use eTraxis\TemplatesDomain\Model\Dictionary\StateType;
use eTraxis\TemplatesDomain\Model\Entity\State;
use eTraxis\TemplatesDomain\Model\Repository\StateRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class SetInitialStateHandler
{
    protected $security;
    protected $repository;
    protected $manager;

    /**
     * Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param StateRepository               $repository
     * @param EntityManagerInterface        $manager
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        StateRepository               $repository,
        EntityManagerInterface        $manager
    )
    {
        $this->security   = $security;
        $this->repository = $repository;
        $this->manager    = $manager;
    }

    /**
     * Command handler.
     *
     * @param SetInitialStateCommand $command
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     * @throws \ReflectionException
     */
    public function handle(SetInitialStateCommand $command): void
    {
        /** @var null|\eTraxis\TemplatesDomain\Model\Entity\State $state */
        $state = $this->repository->find($command->state);

        if (!$state) {
            throw new NotFoundHttpException();
        }

        if (!$this->security->isGranted(StateVoter::SET_INITIAL, $state)) {
            throw new AccessDeniedHttpException();
        }

        if ($state->type !== StateType::INITIAL) {

            // Only one initial state is allowed per template.
            $query = $this->manager->createQuery('
                UPDATE TemplatesDomain:State state
                SET state.type = :interim
                WHERE state.template = :template AND state.type = :initial
            ');

            $query->execute([
                'template' => $state->template,
                'initial'  => StateType::INITIAL,
                'interim'  => StateType::INTERMEDIATE,
            ]);

            $reflection = new \ReflectionProperty(State::class, 'type');
            $reflection->setAccessible(true);
            $reflection->setValue($state, StateType::INITIAL);

            $this->repository->persist($state);
        }
    }
}
