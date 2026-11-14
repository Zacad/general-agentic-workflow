<?php

declare(strict_types=1);

namespace Workflow\Domain;

final class AutomatedEvent
{
    /** @return list<array<string, mixed>> */
    public static function validate(mixed $events): array
    {
        if (!is_array($events) || !array_is_list($events)) {
            throw new InvalidItemException('automated_events must be a list.');
        }
        $ids = [];
        foreach ($events as $event) {
            if (!is_array($event)) {
                throw new InvalidItemException('Invalid automated event.');
            }
            $keys = array_keys($event); sort($keys);
            if ($keys !== ['action', 'at', 'id', 'run_id', 'text', 'version']
                || $event['version'] !== 1
                || !is_string($event['id']) || !preg_match('/\AEV-[0-9a-f]{32}\z/', $event['id'])
                || isset($ids[$event['id']])
                || !in_array($event['action'], ['decide', 'accept-review'], true)
                || !is_string($event['run_id']) || !preg_match('/\AR-[0-9a-f]{32}\z/', $event['run_id'])
                || !is_string($event['text']) || trim($event['text']) === '' || !preg_match('//u', $event['text'])
                || !is_string($event['at']) || !preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z\z/', $event['at'])) {
                throw new InvalidItemException('Invalid or duplicate automated event.');
            }
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $event['at'], new \DateTimeZone('UTC'));
            if ($date === false || $date->format('Y-m-d\TH:i:s\Z') !== $event['at']) {
                throw new InvalidItemException('Invalid automated event timestamp.');
            }
            $ids[$event['id']] = true;
        }
        return $events;
    }

    public static function create(string $action, string $runId, string $text): array
    {
        $event = ['version' => 1, 'id' => 'EV-'.bin2hex(random_bytes(16)), 'action' => $action,
            'at' => gmdate('Y-m-d\TH:i:s\Z'), 'run_id' => $runId, 'text' => $text];
        self::validate([$event]);
        return $event;
    }
}
