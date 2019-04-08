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

use League\Tactician\Bundle\Middleware\InvalidCommandException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handles any unhandled exception.
 */
class UnhandledException implements EventSubscriberInterface
{
    protected $logger;
    protected $translator;
    protected $normalizer;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param LoggerInterface     $logger
     * @param TranslatorInterface $translator
     * @param NormalizerInterface $normalizer
     */
    public function __construct(LoggerInterface $logger, TranslatorInterface $translator, NormalizerInterface $normalizer)
    {
        $this->logger     = $logger;
        $this->translator = $translator;
        $this->normalizer = $normalizer;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => 'onException',
        ];
    }

    /**
     * In case of AJAX: logs the exception and converts it into JSON response with HTTP error.
     *
     * @param GetResponseForExceptionEvent $event
     *
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function onException(GetResponseForExceptionEvent $event): void
    {
        $request   = $event->getRequest();
        $exception = $event->getException();

        if ($request->isXmlHttpRequest() || $request->getContentType() === 'json') {

            if ($exception instanceof InvalidCommandException) {
                $violations = $this->normalizer->normalize($exception->getViolations());
                $this->logger->error('Validation exception', [$exception->getMessage(), $violations]);
                $response = new JsonResponse($violations, JsonResponse::HTTP_BAD_REQUEST);
                $event->setResponse($response);
            }
            elseif ($exception instanceof HttpException) {
                $message = $exception->getMessage() ?: $this->getHttpErrorMessage($exception->getStatusCode());
                $this->logger->error('HTTP exception', [$message]);
                $response = new JsonResponse($message, $exception->getStatusCode());
                $event->setResponse($response);
            }
            else {
                $message = $exception->getMessage() ?: JsonResponse::$statusTexts[JsonResponse::HTTP_INTERNAL_SERVER_ERROR];
                $this->logger->critical('Exception', [$message]);
                $response = new JsonResponse($message, JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
                $event->setResponse($response);
            }
        }
    }

    /**
     * Returns user-friendly error message for specified HTTP status code.
     *
     * @param int $statusCode
     *
     * @return string
     */
    protected function getHttpErrorMessage(int $statusCode): string
    {
        switch ($statusCode) {

            case JsonResponse::HTTP_UNAUTHORIZED:
                return $this->translator->trans('Authentication required.');

            case JsonResponse::HTTP_FORBIDDEN:
                return $this->translator->trans('http_error.403.description');

            case JsonResponse::HTTP_NOT_FOUND:
                return $this->translator->trans('http_error.404.description');

            default:
                return JsonResponse::$statusTexts[JsonResponse::HTTP_INTERNAL_SERVER_ERROR];
        }
    }
}
