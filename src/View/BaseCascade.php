<?php

namespace Aerni\AdvancedSeo\View;

use Aerni\AdvancedSeo\Actions\GetContentDefaults;
use Aerni\AdvancedSeo\Actions\GetOnPageSeoData;
use Aerni\AdvancedSeo\Actions\GetPageData;
use Aerni\AdvancedSeo\Actions\GetSiteDefaults;
use Aerni\AdvancedSeo\Data\DefaultsData;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Fields\Value;
use Statamic\Support\Str;
use Statamic\Tags\Context;

abstract class BaseCascade
{
    protected Context|DefaultsData|Entry $model;
    protected Collection $data;

    // TODO: This also has to work with Taxonomy/Term. Not sure which class it is at this point. Have to test in GraphQl.
    public function __construct(Context|DefaultsData|Entry $model)
    {
        $this->model = $model;
        $this->data = collect();
    }

    abstract public function process(): self;

    public static function from(mixed $model): self
    {
        return new static($model);
    }

    public function all(): array
    {
        return $this->data->all();
    }

    public function get(string $key): mixed
    {
        return $this->data->get($key);
    }

    public function value(string $key): mixed
    {
        $value = $this->data->get($key);

        return $value instanceof Value ? $value->value() : $value;
    }

    public function raw(string $key): mixed
    {
        $value = $this->data->get($key);

        return $value instanceof Value ? $value->raw() : $value;
    }

    public function withSiteDefaults(): self
    {
        $this->data = $this->data->merge(GetSiteDefaults::handle($this->model));

        return $this;
    }

    public function withContentDefaults(): self
    {
        $this->data = $this->data->merge(GetContentDefaults::handle($this->model));

        return $this;
    }

    // TODO: Can we merge this with `withPageData` method?
    // This is used for GraphQL computed data.
    public function withOnPageSeoData(): self
    {
        $this->data = $this->data->merge(GetOnPageSeoData::handle($this->model));

        return $this;
    }

    // TODO: Can we merge this with `withOnPageSeoData` method?
    // This is used for Antlers computed data.
    public function withPageData(): self
    {
        // TODO: Can we move this to the constructor of the AntlersCascade?
        if (! $this->model instanceof Context) {
            throw new \Exception("The context needs to be an instance of Statamic\Tags\Context in order to get the page data.");
        }

        $this->data = $this->data->merge(GetPageData::handle($this->model));

        return $this;
    }

    /**
     * Make sure to get the site defaults if there is no value
     * for the overrides keys in the current data.
     */
    protected function ensureOverrides(): self
    {
        // The keys that should be considered for the overrides.
        $overrides = ['noindex', 'nofollow', 'og_image', 'twitter_summary_image', 'twitter_summary_large_image'];

        // The values that should be used as overrides.
        // TODO: Could pass down the overrides keys so to only augment those keys to prevent overload.
        $defaults = GetSiteDefaults::handle($this->model)->only($overrides);

        // The values from the existing data that should be overriden.
        $data = $this->data->only($overrides)->filter(fn ($item) => $item->value());

        // Only merge the defaults overrides if they don't exist in the data.
        $merged = $defaults->diffKeys($data)->merge($data);

        $this->data = $this->data->merge($merged);

        return $this;
    }

    protected function removeSeoPrefix(): self
    {
        $this->data = $this->data->mapWithKeys(fn ($item, $key) => [Str::remove('seo_', $key) => $item]);

        return $this;
    }

    protected function removeSectionFields(): self
    {
        $this->data = $this->data->filter(fn ($item, $key) => ! Str::contains($key, 'section_'));

        return $this;
    }

    protected function sortKeys(): self
    {
        $this->data = $this->data->sortKeys();

        return $this;
    }
}
