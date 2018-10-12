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

use eTraxis\SecurityDomain\Framework\Authenticator\BitbucketOAuth2Authenticator;
use eTraxis\SecurityDomain\Framework\Authenticator\GithubOAuth2Authenticator;
use eTraxis\SecurityDomain\Framework\Authenticator\GoogleOAuth2Authenticator;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * OAuth2 controller.
 *
 * @Route("/oauth")
 */
class OAuth2Controller extends Controller
{
    /**
     * OAuth2 callback URL for Google.
     *
     * @Route("/google", name="oauth_google")
     *
     * @param Request                   $request
     * @param GoogleOAuth2Authenticator $authenticator
     *
     * @return Response
     */
    public function callbackGoogle(Request $request, GoogleOAuth2Authenticator $authenticator): Response
    {
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirectToRoute('homepage');
        }

        return $authenticator->start($request);
    }

    /**
     * OAuth2 callback URL for GitHub.
     *
     * @Route("/github", name="oauth_github")
     *
     * @param Request                   $request
     * @param GithubOAuth2Authenticator $authenticator
     *
     * @return Response
     */
    public function callbackGithub(Request $request, GithubOAuth2Authenticator $authenticator): Response
    {
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirectToRoute('homepage');
        }

        return $authenticator->start($request);
    }

    /**
     * OAuth2 callback URL for Bitbucket.
     *
     * @Route("/bitbucket", name="oauth_bitbucket")
     *
     * @param Request                      $request
     * @param BitbucketOAuth2Authenticator $authenticator
     *
     * @return Response
     */
    public function callbackBitbucket(Request $request, BitbucketOAuth2Authenticator $authenticator): Response
    {
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirectToRoute('homepage');
        }

        return $authenticator->start($request);
    }
}
