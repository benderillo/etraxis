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

namespace eTraxis\SecurityDomain\Framework\Controller\ApiMyController;

use eTraxis\SecurityDomain\Model\Entity\User;
use eTraxis\Tests\TransactionalTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\BasePasswordEncoder;

class SetPasswordTest extends TransactionalTestCase
{
    public function testSuccess()
    {
        $this->loginAs('nhills@example.com');

        /** @var \Symfony\Component\Security\Core\Encoder\UserPasswordEncoder $encoder */
        $encoder = $this->client->getContainer()->get('security.password_encoder');

        /** @var User $user */
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'nhills@example.com']);

        self::assertTrue($encoder->isPasswordValid($user, 'secret'));

        $uri = '/api/my/password';

        $data = [
            'current' => 'secret',
            'new'     => 'P@ssw0rd',
        ];

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertFalse($encoder->isPasswordValid($user, 'secret'));
        self::assertTrue($encoder->isPasswordValid($user, 'P@ssw0rd'));
    }

    public function testBadCredentials()
    {
        $this->loginAs('nhills@example.com');

        $uri = '/api/my/password';

        $data = [
            'current' => 'wrong',
            'new'     => 'P@ssw0rd',
        ];

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('Unknown user or wrong password.', json_decode($response->getContent(), true));
    }

    public function test400()
    {
        $this->loginAs('nhills@example.com');

        $uri = '/api/my/password';

        $data = [
            'current' => 'secret',
            'new'     => str_repeat('*', BasePasswordEncoder::MAX_PASSWORD_LENGTH + 1),
        ];

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function test401()
    {
        $uri = '/api/my/password';

        $data = [
            'current' => 'secret',
            'new'     => 'P@ssw0rd',
        ];

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function test403()
    {
        $this->loginAs('einstein@ldap.forumsys.com');

        $uri = '/api/my/password';

        $data = [
            'current' => 'secret',
            'new'     => 'P@ssw0rd',
        ];

        $response = $this->json(Request::METHOD_PUT, $uri, $data);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('Password cannot be set for external accounts.', json_decode($response->getContent(), true));
    }
}
