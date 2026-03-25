<?php

namespace AxoloteSource\Logics\Logics\Flow;

use AxoloteSource\Logics\Logics\Flow\Traits\FlowLogic;
use AxoloteSource\Logics\Logics\Flow\Traits\WithValidates;
use AxoloteSource\Logics\Logics\UpdateLogic;

abstract class FlowUpdateLogicBase extends UpdateLogic
{
    use FlowLogic, WithValidates;
}
