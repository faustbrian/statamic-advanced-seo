<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Data\DefaultsData;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Support\SeoDebug;
use Illuminate\Support\Collection;

class GetAugmentedDefaults
{
    public static function handle(DefaultsData $data): Collection
    {
        $set = Seo::findOrMake($data->type, $data->handle)
            ->ensureLocalizations($data->sites);

        $localization = $set->in($data->locale);

        SeoDebug::log('seo-debug.augmented-defaults-before', fn () => SeoDebug::isRelevantDefaultsData($data) ? [
            'route' => optional(request()->route())->getName(),
            'path' => request()->path(),
            'type' => $data->type,
            'handle' => $data->handle,
            'locale' => $data->locale,
            'sites' => $data->sites?->values()->all(),
            'localization_exists' => $localization !== null,
            'origin' => optional($localization?->origin())->locale(),
            'is_root' => $localization?->isRoot(),
            'data_keys' => $localization?->data()->keys()->all(),
            'stored_seo_title' => $localization?->data()->get('seo_title'),
            'stored_seo_description' => $localization?->data()->get('seo_description'),
        ] : null);

        $augmented = $localization?->toAugmentedCollection() ?? collect();

        SeoDebug::log('seo-debug.augmented-defaults-after', fn () => SeoDebug::isRelevantDefaultsData($data) ? [
            'route' => optional(request()->route())->getName(),
            'path' => request()->path(),
            'type' => $data->type,
            'handle' => $data->handle,
            'locale' => $data->locale,
            'resolved_keys' => $augmented->keys()->all(),
            'resolved_seo_title' => $augmented->get('seo_title')?->value(),
            'resolved_seo_description' => $augmented->get('seo_description')?->value(),
            'resolved_seo_site_name_position' => $augmented->get('seo_site_name_position')?->value(),
        ] : null);

        return $augmented;
    }
}
