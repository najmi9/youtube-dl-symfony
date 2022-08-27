<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Events\VideoConvertingEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class VideoConvertingSubscriber implements EventSubscriberInterface
{
    private HubInterface $hub;

    public function __construct(HubInterface $hub)
    {
        $this->hub = $hub;
    }

    public static function getSubscribedEvents()
    {
        return [
            VideoConvertingEvent::class => 'videoConverting',
        ];
    }

    public function videoConverting(VideoConvertingEvent $event)
    {
        // push to browser the data
        $lastOutput = $event->getLastOutput();
        $topics = $event->getTopics();

        $this->hub->publish(
            new Update(
                $topics,
                json_encode($lastOutput),
                true,
            )
        );
    }
}
