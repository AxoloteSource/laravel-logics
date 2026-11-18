<?php

namespace AxoloteSource\Logics\Tests\Unit\Data;

use AxoloteSource\Logics\Data\IndexData;
use AxoloteSource\Logics\Tests\TestCase;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;

class IndexDataTest extends TestCase
{
    public function test_index_data_declares_limit_and_page_validation()
    {
        $page = new \ReflectionProperty(IndexData::class, 'page');
        $limit = new \ReflectionProperty(IndexData::class, 'limit');

        $this->assertCount(1, $page->getAttributes(Min::class));
        $this->assertCount(1, $limit->getAttributes(Min::class));
        $this->assertCount(1, $limit->getAttributes(Max::class));
    }

    public function test_index_data_keeps_expected_defaults()
    {
        $data = new IndexData;

        $this->assertSame(1, $data->page);
        $this->assertSame(15, $data->limit);
    }
}
