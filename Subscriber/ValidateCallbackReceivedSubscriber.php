<?php

namespace Knojector\SteamAuthenticationBundle\Subscriber;

use Knojector\SteamAuthenticationBundle\Event\CallbackReceivedEvent;
use Knojector\SteamAuthenticationBundle\Event\PayloadValidEvent;
use Knojector\SteamAuthenticationBundle\Exception\InvalidCallbackPayloadException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author knojector <dev@knojector.xyz>
 */
class ValidateCallbackReceivedSubscriber implements EventSubscriberInterface
{
    const STEAM_VALIDATION_URL = 'https://steamcommunity.com/openid/login';

    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private HttpClientInterface $client
    ) {
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CallbackReceivedEvent::NAME => [
                ['onCallbackReceived', 10],
            ],
        ];
    }

    public function onCallbackReceived(CallbackReceivedEvent $event): void
    {
        $callback = $event->getSteamCallback();
        $callback->openid_mode = 'check_authentication';

        // Convert only properties with 'openid_' prefix to dot notation
        $newCallback = [];
        foreach ((array)$callback as $key => $value) {
            if (str_starts_with($key, 'openid_')) {
                $newKey = str_replace('openid_', 'openid.', $key);
            } else {
                $newKey = $key;
            }
            $newCallback[$newKey] = $value;
        }

        $response = $this->client->request(
            'POST',
            self::STEAM_VALIDATION_URL,
            [
                'body' => $newCallback,
                'proxy' => $_ENV['PROXY'],
                // Optional: Authentication if needed
                // 'proxy_auth_basic' => 'username:password'
            ]
        );

        if (false === str_contains($response->getContent(), 'is_valid:true')) {
            throw new InvalidCallbackPayloadException();
        }

        $this->eventDispatcher->dispatch(new PayloadValidEvent($callback->getCommunityId()), PayloadValidEvent::NAME);
    }
}
