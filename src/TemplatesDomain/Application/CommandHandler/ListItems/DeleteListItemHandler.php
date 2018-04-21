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

namespace eTraxis\TemplatesDomain\Application\CommandHandler\ListItems;

use eTraxis\TemplatesDomain\Application\Command\ListItems\DeleteListItemCommand;
use eTraxis\TemplatesDomain\Application\Voter\ListItemVoter;
use eTraxis\TemplatesDomain\Model\Repository\ListItemRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
class DeleteListItemHandler
{
    protected $security;
    protected $repository;

    /**
     * Dependency Injection constructor.
     *
     * @param AuthorizationCheckerInterface $security
     * @param ListItemRepository            $repository
     */
    public function __construct(AuthorizationCheckerInterface $security, ListItemRepository $repository)
    {
        $this->security   = $security;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param DeleteListItemCommand $command
     *
     * @throws AccessDeniedHttpException
     */
    public function handle(DeleteListItemCommand $command): void
    {
        /** @var \eTraxis\TemplatesDomain\Model\Entity\ListItem $item */
        $item = $this->repository->find($command->item);

        if ($item) {

            if (!$this->security->isGranted(ListItemVoter::DELETE_ITEM, $item)) {
                throw new AccessDeniedHttpException();
            }

            $this->repository->remove($item);
        }
    }
}
