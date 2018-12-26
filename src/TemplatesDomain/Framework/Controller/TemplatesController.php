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

use eTraxis\TemplatesDomain\Application\Voter\TemplateVoter;
use eTraxis\TemplatesDomain\Model\Entity\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Templates controller.
 *
 * @Route("/admin/templates")
 * @Security("is_granted('ROLE_ADMIN')")
 */
class TemplatesController extends AbstractController
{
    /**
     * Returns permissions for specified template.
     *
     * @Route("/permissions/{id}", name="admin_template_permissions", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @param Template $template
     *
     * @return JsonResponse
     */
    public function permissions(Template $template): JsonResponse
    {
        return $this->json([
            'update'      => $this->isGranted(TemplateVoter::UPDATE_TEMPLATE, $template),
            'delete'      => $this->isGranted(TemplateVoter::DELETE_TEMPLATE, $template),
            'lock'        => $this->isGranted(TemplateVoter::LOCK_TEMPLATE, $template),
            'unlock'      => $this->isGranted(TemplateVoter::UNLOCK_TEMPLATE, $template),
            'permissions' => $this->isGranted(TemplateVoter::MANAGE_PERMISSIONS, $template),
        ]);
    }
}
