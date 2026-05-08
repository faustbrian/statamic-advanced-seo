<?php

namespace Aerni\AdvancedSeo\Support;

use Aerni\AdvancedSeo\Data\DefaultsData;
use Statamic\Contracts\Entries\Entry;
use Statamic\Tags\Context;

class SeoDebug
{
    public const HOMEPAGE_ENTRY_ID = 'c0c7f893-37e6-47f1-b404-ec3ee0f5299a';

    public static function log(string $event, callable $context): void
    {
        try {
            $payload = $context();

            if ($payload === null) {
                return;
            }

            logger()->info($event, $payload);
        } catch (\Throwable) {
            // Ignore debug logging failures so instrumentation never affects requests.
        }
    }

    public static function isHomepageEntry(mixed $entry): bool
    {
        return $entry instanceof Entry && $entry->id() === self::HOMEPAGE_ENTRY_ID;
    }

    public static function isHomepageContext(mixed $context): bool
    {
        if (! $context instanceof Context || ! $context->has('id')) {
            return false;
        }

        try {
            $id = $context->get('id');

            if (is_object($id) && method_exists($id, 'augmentable')) {
                $id = $id->augmentable()?->id();
            } elseif (is_object($id) && method_exists($id, 'raw')) {
                $id = $id->raw();
            } elseif (is_object($id) && method_exists($id, 'value')) {
                $id = $id->value();
            }

            return $id === self::HOMEPAGE_ENTRY_ID;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function isRelevantDefaultsData(?DefaultsData $data): bool
    {
        return $data !== null
            && $data->type === 'collections'
            && $data->handle === 'pages'
            && in_array($data->locale, ['sv_SE', 'fi_FI', 'sv_FI', 'sv_EN'], true);
    }

    public static function isRelevantDefaultsHandleSite(?string $handle, ?string $site): bool
    {
        return in_array($site, ['sv_SE', 'fi_FI', 'sv_FI', 'sv_EN'], true)
            && in_array($handle, ['pages', 'general', 'social_media'], true);
    }
}
