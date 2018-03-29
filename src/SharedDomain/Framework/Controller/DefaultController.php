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

namespace eTraxis\SharedDomain\Framework\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default controller.
 */
class DefaultController extends Controller
{
    /**
     * Default page of public area.
     *
     * @Route("/", name="homepage")
     *
     * @return Response
     */
    public function homepage(): Response
    {
        return $this->render('base.html.twig');
    }

    /**
     * Default page of admin area.
     *
     * @Route("/admin/", name="admin")
     *
     * @return Response
     */
    public function admin(): Response
    {
        return $this->render('base.html.twig');
    }
}
