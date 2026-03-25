<?php

namespace AxoloteSource\Logics\Logics\Flow;

use AxoloteSource\Logics\Data\Flow\FlowByIdData;
use AxoloteSource\Logics\Logics\DeleteLogic;
use AxoloteSource\Logics\Logics\Flow\Traits\FlowLogic;
use AxoloteSource\Logics\Logics\Flow\Traits\WithoutValidate;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\Data;

abstract class FlowDeleteLogicBase extends DeleteLogic
{
    use FlowLogic, WithoutValidate;

    protected Data|FlowByIdData $input;

    public function run(Data|FlowByIdData $input): JsonResponse
    {
        return parent::logic($input);
    }
}
