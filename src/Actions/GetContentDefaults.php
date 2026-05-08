<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Support\SeoDebug;
use Illuminate\Support\Collection;
use Statamic\Facades\Blink;

class GetContentDefaults
{
    public static function handle(mixed $data): Collection
    {
        if (! $data = GetDefaultsData::handle($data)) {
            return collect();
        }

        $defaults = Blink::once(
            "advanced-seo::{$data->type}::{$data->handle}::{$data->locale}",
            fn () => GetAugmentedDefaults::handle($data)
        );

        SeoDebug::log('seo-debug.content-defaults', fn () => SeoDebug::isRelevantDefaultsData($data) ? [
            'route' => optional(request()->route())->getName(),
            'path' => request()->path(),
            'type' => $data->type,
            'handle' => $data->handle,
            'locale' => $data->locale,
            'resolved_keys' => $defaults->keys()->all(),
            'resolved_seo_title' => $defaults->get('seo_title')?->value(),
            'resolved_seo_description' => $defaults->get('seo_description')?->value(),
        ] : null);

        return $defaults;
    }
}
