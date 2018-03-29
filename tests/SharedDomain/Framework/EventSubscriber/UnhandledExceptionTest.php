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

namespace eTraxis\SharedDomain\Framework\EventSubscriber;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Translation\TranslatorInterface;

class UnhandledExceptionTest extends TestCase
{
    /** @var UnhandledException */
    protected $subscriber;

    protected function setUp()
    {
        parent::setUp();

        $logger = new NullLogger();

        /** @var \PHPUnit_Framework_MockObject_MockObject $translator */
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnMap([
                ['Authentication required.', [], null, null, 'User-friendly 401 error message.'],
                ['http_error.403.description', [], null, null, 'User-friendly 403 error message.'],
                ['http_error.404.description', [], null, null, 'User-friendly 404 error message.'],
            ]);

        /** @var \Symfony\Component\Translation\TranslatorInterface $translator */
        $this->subscriber = new UnhandledException($logger, $translator);
    }

    public function testGetSubscribedEvents()
    {
        $expected = [
            'kernel.exception',
        ];

        self::assertSame($expected, array_keys(UnhandledException::getSubscribedEvents()));
    }

    public function testMasterRequest()
    {
        $request = new Request();

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new GetResponseForExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            new HttpException(Response::HTTP_NOT_FOUND, 'Unknown username.')
        );

        $this->subscriber->onException($event);

        $response = $event->getResponse();

        self::assertNull($response);
    }

    public function testHttp401Exception()
    {
        $request = new Request();
        $request->headers->add(['X-Requested-With' => 'XMLHttpRequest']);

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new GetResponseForExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::SUB_REQUEST,
            new UnauthorizedHttpException('')
        );

        $this->subscriber->onException($event);

        $response = $event->getResponse();
        $content  = $response->getContent();

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame('User-friendly 401 error message.', trim($content, '"'));
    }

    public function testHttp403Exception()
    {
        $request = new Request();
        $request->headers->add(['X-Requested-With' => 'XMLHttpRequest']);

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new GetResponseForExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::SUB_REQUEST,
            new AccessDeniedHttpException()
        );

        $this->subscriber->onException($event);

        $response = $event->getResponse();
        $content  = $response->getContent();

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('User-friendly 403 error message.', trim($content, '"'));
    }

    public function testHttp404Exception()
    {
        $request = new Request();
        $request->headers->add(['X-Requested-With' => 'XMLHttpRequest']);

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new GetResponseForExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::SUB_REQUEST,
            new NotFoundHttpException()
        );

        $this->subscriber->onException($event);

        $response = $event->getResponse();
        $content  = $response->getContent();

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        self::assertSame('User-friendly 404 error message.', trim($content, '"'));
    }

    public function testHttpDefaultMessageException()
    {
        $request = new Request();
        $request->headers->add(['X-Requested-With' => 'XMLHttpRequest']);

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new GetResponseForExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::SUB_REQUEST,
            new ConflictHttpException()
        );

        $this->subscriber->onException($event);

        $response = $event->getResponse();
        $content  = $response->getContent();

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        self::assertSame(Response::$statusTexts[Response::HTTP_INTERNAL_SERVER_ERROR], trim($content, '"'));
    }

    public function testHttpCustomMessageException()
    {
        $request = new Request();
        $request->headers->add(['X-Requested-With' => 'XMLHttpRequest']);

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new GetResponseForExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::SUB_REQUEST,
            new AccessDeniedHttpException('You are not allowed for this action.')
        );

        $this->subscriber->onException($event);

        $response = $event->getResponse();
        $content  = $response->getContent();

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('You are not allowed for this action.', trim($content, '"'));
    }

    public function testException()
    {
        $request = new Request();
        $request->headers->add(['X-Requested-With' => 'XMLHttpRequest']);

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        $event = new GetResponseForExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::SUB_REQUEST,
            new \Exception('Something went wrong.')
        );

        $this->subscriber->onException($event);

        $response = $event->getResponse();
        $content  = $response->getContent();

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        self::assertSame('Something went wrong.', trim($content, '"'));
    }
}
