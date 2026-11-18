<?php

namespace AxoloteSource\Logics\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;

class IndexData extends Data
{
    #[Min(1)]
    public ?int $page = 1;

    #[Min(1)]
    #[Max(100)]
    public ?int $limit = 15;

    public ?string $order = 'asc';

    public ?string $order_by = 'id';

    public ?array $filters = [];

    public ?string $search = null;
}
