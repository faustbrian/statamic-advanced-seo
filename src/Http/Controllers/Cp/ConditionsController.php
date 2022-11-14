<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Actions\GetDefaultsData;
use Aerni\AdvancedSeo\Conditions\ShowSitemapFields;
use Aerni\AdvancedSeo\Conditions\ShowSocialImagesGeneratorFields;
use Aerni\AdvancedSeo\Data\DefaultsData;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Statamic\Facades\Data;

class ConditionsController extends Controller
{
    public function __invoke(Request $request): array
    {
        /**
         * Try to get the model by id first.
         * If no model was found, we are parsing the URL instead.
         * This is the case when creating an entry or term.
         */
        $data = $request->get('id')
            ? $this->getDataById($request)
            : $this->getDataByUrl($request);

        /**
         * We have to manually set the locale if a site query exists.
         * That's because the locale can't be correctly evaluated in the EvaluateModelLocale action.
         * Because the request is coming from this controller and doesn't contain the "site" query from the original request.
         * This is the case when editing an existing term.
         */
        if ($site = $request->get('site')) {
            $data->locale = $site;
        }

        return [
            'showSitemapFields' => ShowSitemapFields::handle($data),
            'showSocialImagesGeneratorFields' => ShowSocialImagesGeneratorFields::handle($data),
        ];
    }

    protected function getDataById(Request $request): DefaultsData
    {
        $id = $request->get('id');

        $model = Data::find($id) ?? Seo::findById($id);

        return GetDefaultsData::handle($model);
    }

    protected function getDataByUrl(Request $request): DefaultsData
    {
        $url = parse_url($request->get('url')['href']);
        $path = array_slice(explode('/', $url['path']), 2);

        /**
         * TODO: This could also be moved into the GetDefaultsData action by adding a case for a URL string.
         * For now we leave it as is as we only use this case once.
         */
        return new DefaultsData(
            type: $path[0],
            handle: $path[1],
            locale: $path[4],
            // sites: TODO: Right now we don't need the sites.
        );
    }
}
