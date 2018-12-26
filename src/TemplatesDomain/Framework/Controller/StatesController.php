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

use eTraxis\TemplatesDomain\Application\Voter\StateVoter;
use eTraxis\TemplatesDomain\Model\Entity\State;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * States controller.
 *
 * @Route("/admin/states")
 * @Security("is_granted('ROLE_ADMIN')")
 */
class StatesController extends AbstractController
{
    /**
     * Returns permissions for specified state.
     *
     * @Route("/permissions/{id}", name="admin_state_permissions", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @param State $state
     *
     * @return JsonResponse
     */
    public function permissions(State $state): JsonResponse
    {
        return $this->json([
            'update'       => $this->isGranted(StateVoter::UPDATE_STATE, $state),
            'delete'       => $this->isGranted(StateVoter::DELETE_STATE, $state),
            'initial'      => $this->isGranted(StateVoter::SET_INITIAL, $state),
            'transitions'  => $this->isGranted(StateVoter::MANAGE_TRANSITIONS, $state),
            'responsibles' => $this->isGranted(StateVoter::MANAGE_RESPONSIBLE_GROUPS, $state),
        ]);
    }
}
