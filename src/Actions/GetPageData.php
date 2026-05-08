<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;
use Aerni\AdvancedSeo\Support\SeoDebug;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Fields\Value;

class GetPageData
{
    public static function handle(mixed $model): Collection
    {
        $blueprint = OnPageSeoBlueprint::make();

        /**
         * We only want to return data of enabled features.
         * This ensures that we don't return any values of conditionally hidden fields.
         * This would typically happen when a feature like the social images generator has been disabled.
         */
        if ($data = GetDefaultsData::handle($model)) {
            $blueprint->data($data);
        }

        $fields = $blueprint->get()->fields()->all();

        if ($model instanceof Entry || $model instanceof Term) {
            $pageData = $model->toAugmentedCollection($fields->keys()->toArray());

            SeoDebug::log('seo-debug.page-data-entry', fn () => SeoDebug::isHomepageEntry($model) ? [
                'route' => optional(request()->route())->getName(),
                'path' => request()->path(),
                'entry_id' => $model->id(),
                'site' => $model->locale(),
                'slug' => $model->slug(),
                'origin_id' => optional($model->origin())->id(),
                'resolved_keys' => $pageData->keys()->all(),
                'resolved_seo_title' => $pageData->get('seo_title')?->value(),
                'resolved_seo_description' => $pageData->get('seo_description')?->value(),
                'resolved_seo_og_title' => $pageData->get('seo_og_title')?->value(),
                'resolved_seo_twitter_title' => $pageData->get('seo_twitter_title')?->value(),
            ] : null);

            return $pageData;
        }

        $pageData = $model->intersectByKeys($fields)
            ->map(
                fn ($value, $field) => $value instanceof Value
                ? $value
                : $fields->get($field)->setValue($value)->augment()->value()
            );

        SeoDebug::log('seo-debug.page-data-context', fn () => SeoDebug::isHomepageContext($model) ? [
            'route' => optional(request()->route())->getName(),
            'path' => request()->path(),
            'resolved_keys' => $pageData->keys()->all(),
            'resolved_seo_title' => $pageData->get('seo_title')?->value(),
            'resolved_seo_description' => $pageData->get('seo_description')?->value(),
            'resolved_seo_og_title' => $pageData->get('seo_og_title')?->value(),
            'resolved_seo_twitter_title' => $pageData->get('seo_twitter_title')?->value(),
        ] : null);

        return $pageData;
    }
}
