<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Illuminate\Events\Dispatcher;
use Statamic\Contracts\Entries\Entry;
use Statamic\Events;
use Statamic\Events\Event;
use Statamic\Facades\User;

class SeoDebugSubscriber
{
    public function subscribe(Dispatcher $events): array
    {
        return [
            Events\EntrySaving::class => 'logEntrySaving',
            Events\EntrySaved::class => 'logEntrySaved',
        ];
    }

    public function logEntrySaving(Event $event): void
    {
        $this->logEntryState('saving', $event->entry, [
            'localized' => request()->input('_localized'),
            'request_seo_title' => request()->input('seo_title'),
            'request_seo_description' => request()->input('seo_description'),
        ]);
    }

    public function logEntrySaved(Event $event): void
    {
        $this->logEntryState('saved', $event->entry);
    }

    protected function logEntryState(string $phase, Entry $entry, array $context = []): void
    {
        try {
            if ($entry->id() !== 'c0c7f893-37e6-47f1-b404-ec3ee0f5299a') {
                return;
            }

            logger()->info('seo-debug.entry-event', array_merge([
                'phase' => $phase,
                'route' => optional(request()->route())->getName(),
                'path' => request()->path(),
                'user' => User::current()?->email(),
                'entry_id' => $entry->id(),
                'site' => $entry->locale(),
                'slug' => $entry->slug(),
                'origin_id' => optional($entry->origin())->id(),
                'stored_seo_title' => $entry->data()->get('seo_title'),
                'stored_seo_description' => $entry->data()->get('seo_description'),
                'stored_keys' => $entry->data()->keys()->all(),
            ], $context));
        } catch (\Throwable) {
            // Ignore debug logging failures so instrumentation never affects requests.
        }
    }
}
