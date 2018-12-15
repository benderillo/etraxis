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

namespace eTraxis\SecurityDomain\Framework\Controller;

use eTraxis\SecurityDomain\Model\Dictionary\Locale;
use eTraxis\SecurityDomain\Model\Dictionary\Theme;
use eTraxis\SecurityDomain\Model\Dictionary\Timezone;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Settings controller.
 *
 * @Route("/settings")
 */
class SettingsController extends AbstractController
{
    /**
     * 'Settings' page.
     *
     * @Route("", name="settings", methods={"GET"})
     *
     * @return Response
     */
    public function index(): Response
    {
        /** @var \eTraxis\SecurityDomain\Model\Entity\User $user */
        $user = $this->getUser();

        $location = timezone_location_get(new \DateTimeZone($user->timezone));
        $country  = $location['country_code'] === '??' ? 'UTC' : $location['country_code'];

        return $this->render('settings/index.html.twig', [
            'locales'   => Locale::all(),
            'themes'    => Theme::all(),
            'countries' => Timezone::getCountries(),
            'country'   => $country,
        ]);
    }

    /**
     * Returns list of timezone cities for specified country.
     *
     * @Route("/cities/{country}", methods={"GET"})
     *
     * @param string $country ISO 3166-1 code.
     *
     * @return JsonResponse
     */
    public function cities(string $country): JsonResponse
    {
        return $this->json(Timezone::getCities($country));
    }
}
