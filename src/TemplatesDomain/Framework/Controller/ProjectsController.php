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

use eTraxis\TemplatesDomain\Model\Dictionary\StateResponsible;
use eTraxis\TemplatesDomain\Model\Dictionary\StateType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        ]);
    }
}
