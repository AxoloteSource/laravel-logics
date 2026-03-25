<?php

namespace AxoloteSource\Logics\Logics\Flow;

use AxoloteSource\Logics\Logics\Flow\Traits\FlowLogic;
use AxoloteSource\Logics\Logics\Flow\Traits\WithValidates;
use AxoloteSource\Logics\Logics\StoreLogic;

abstract class FlowStoreLogicBase extends StoreLogic
{
    use FlowLogic, WithValidates;
}
