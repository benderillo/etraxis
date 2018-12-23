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

namespace eTraxis\TemplatesDomain\Framework\Controller;

use eTraxis\SecurityDomain\Application\Voter\GroupVoter;
use eTraxis\SecurityDomain\Model\Entity\Group;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Groups controller.
 *
 * @Route("/admin/groups")
 * @Security("is_granted('ROLE_ADMIN')")
 */
class GroupsController extends AbstractController
{
    /**
     * Returns permissions for specified group.
     *
     * @Route("/permissions/{id}", name="admin_group_permissions", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @param Group $group
     *
     * @return JsonResponse
     */
    public function permissions(Group $group): JsonResponse
    {
        return $this->json([
            'create'     => $this->isGranted(GroupVoter::CREATE_GROUP),
            'update'     => $this->isGranted(GroupVoter::UPDATE_GROUP, $group),
            'delete'     => $this->isGranted(GroupVoter::DELETE_GROUP, $group),
            'membership' => $this->isGranted(GroupVoter::MANAGE_MEMBERSHIP, $group),
        ]);
    }
}
