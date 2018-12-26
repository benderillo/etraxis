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

use eTraxis\TemplatesDomain\Application\Voter\ProjectVoter;
use eTraxis\TemplatesDomain\Model\Dictionary\FieldType;
use eTraxis\TemplatesDomain\Model\Dictionary\StateResponsible;
use eTraxis\TemplatesDomain\Model\Dictionary\StateType;
use eTraxis\TemplatesDomain\Model\Entity\Project;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Projects controller.
 *
 * @Route("/admin/projects")
 * @Security("is_granted('ROLE_ADMIN')")
 */
class ProjectsController extends AbstractController
{
    /**
     * 'Projects' page.
     *
     * @Route("", name="admin_projects", methods={"GET"})
     *
     * @return Response
     */
    public function index(): Response
    {
        return $this->render('projects/index.html.twig', [
            'state_types'        => StateType::all(),
            'state_responsibles' => StateResponsible::all(),
            'field_types'        => FieldType::all(),
        ]);
    }

    /**
     * Returns permissions for specified project.
     *
     * @Route("/permissions/{id}", name="admin_project_permissions", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @param Project $project
     *
     * @return JsonResponse
     */
    public function permissions(Project $project): JsonResponse
    {
        return $this->json([
            'update'  => $this->isGranted(ProjectVoter::UPDATE_PROJECT, $project),
            'delete'  => $this->isGranted(ProjectVoter::DELETE_PROJECT, $project),
            'suspend' => $this->isGranted(ProjectVoter::SUSPEND_PROJECT, $project),
            'resume'  => $this->isGranted(ProjectVoter::RESUME_PROJECT, $project),
        ]);
    }
}
