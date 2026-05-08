<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Data\DefaultsData;
use Aerni\AdvancedSeo\Models\Defaults;
use Aerni\AdvancedSeo\Support\SeoDebug;
use Illuminate\Support\Collection;
use Statamic\Facades\Blink;
use Statamic\Facades\Site;
use Statamic\Fields\Value;
use Statamic\Tags\Context;

class GetSiteDefaults
{
    public static function handle(mixed $data): Collection
    {
        if (! $locale = EvaluateModelLocale::handle($data)) {
            return collect();
        }

        $defaults = Blink::once("advanced-seo::site::{$locale}", function () use ($locale, $data) {
            $siteDefaults = Defaults::enabledInType('site')
                ->flatMap(fn ($model) => GetAugmentedDefaults::handle(
                    new DefaultsData(
                        type: 'site',
                        handle: $model['handle'],
                        locale: $locale,
                        sites: Site::all()->map->handle(),
                    )
                ));

            /**
             * TODO: Instead of merging the overrides, we might be able to refactor this to something similar to the GetPageData action.
             * Instead of augmenting all the default values upfront, we could get the blueprint of each site default and then add the values to each blueprint field.
             */
            if ($data instanceof Context) {
                return self::mergeViewOverrides($siteDefaults, $data);
            }

            return $siteDefaults;
        });

        SeoDebug::log('seo-debug.site-defaults', fn () => in_array($locale, ['sv_SE', 'fi_FI', 'sv_FI', 'sv_EN'], true) ? [
            'route' => optional(request()->route())->getName(),
            'path' => request()->path(),
            'input_type' => is_object($data) ? get_class($data) : gettype($data),
            'locale' => $locale,
            'resolved_keys' => $defaults->keys()->all(),
            'site_name' => $defaults->get('site_name')?->value(),
            'title_separator' => $defaults->get('title_separator')?->value(),
            'twitter_handle' => $defaults->get('twitter_handle')?->value(),
        ] : null);

        return $defaults;
    }

    /**
     * Allow overriding site defaults by matching key in the data.
     * This is useful if you want to override the site default when working with custom views.
     * We need to create a new value object because we can't simply change the `value` in the existing object.
     */
    protected static function mergeViewOverrides(Collection $siteDefaults, Collection $overrides): Collection
    {
        $overrides = $siteDefaults
            ->intersectByKeys($overrides)
            ->map(fn ($originalValue, $key) => new Value(
                value: ($value = $overrides->get($key)) instanceof Value ? $value->raw() : $value,
                handle: $originalValue->handle(),
                fieldtype: $originalValue->fieldtype(),
                augmentable: $originalValue->augmentable()
            ));

        return $siteDefaults->merge($overrides);
    }
}
