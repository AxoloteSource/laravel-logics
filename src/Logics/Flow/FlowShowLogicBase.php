<?php

namespace AxoloteSource\Logics\Logics\Flow;

use AxoloteSource\Logics\Data\Flow\FlowByIdData;
use AxoloteSource\Logics\Logics\Flow\Traits\FlowLogic;
use AxoloteSource\Logics\Logics\Flow\Traits\WhitSearch;
use AxoloteSource\Logics\Logics\Flow\Traits\WithoutValidate;
use AxoloteSource\Logics\Logics\ShowLogic;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\Data;

abstract class FlowShowLogicBase extends ShowLogic
{
    use FlowLogic, WhitSearch, WithoutValidate;

    protected Data|FlowByIdData $input;

    public function run(Data|FlowByIdData $input): JsonResponse
    {
        return parent::logic($input);
    }
}
