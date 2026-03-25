<?php

namespace AxoloteSource\Logics\Data\Flow;

use AxoloteSource\Logics\Data\IndexData;
use Spatie\LaravelData\Attributes\FromRouteParameter;

class FlowIndexData extends IndexData
{
    public function __construct(
        #[FromRouteParameter('model')]
        public string $model,
    ) {}
}
