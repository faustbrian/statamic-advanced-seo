<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Data\DefaultsData;
use Aerni\AdvancedSeo\Support\SeoDebug;

class GetDefaultsData
{
    public static function handle(mixed $data): ?DefaultsData
    {
        if ($data instanceof DefaultsData) {
            return $data;
        }

        if (! $parent = EvaluateModelParent::handle($data)) {
            return null;
        }

        $defaultsData = new DefaultsData(
            type: EvaluateModelType::handle($parent),
            handle: EvaluateModelHandle::handle($parent),
            locale: EvaluateModelLocale::handle($data),
            sites: EvaluateModelSites::handle($parent),
        );

        SeoDebug::log('seo-debug.defaults-data', fn () => SeoDebug::isRelevantDefaultsData($defaultsData) ? [
            'route' => optional(request()->route())->getName(),
            'path' => request()->path(),
            'input_type' => is_object($data) ? get_class($data) : gettype($data),
            'parent_type' => is_object($parent) ? get_class($parent) : gettype($parent),
            'type' => $defaultsData->type,
            'handle' => $defaultsData->handle,
            'locale' => $defaultsData->locale,
            'sites' => $defaultsData->sites?->values()->all(),
        ] : null);

        return $defaultsData;
    }
}
