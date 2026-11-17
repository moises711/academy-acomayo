<?php

namespace Tests\Unit;

use App\Support\InertiaPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class InertiaPaginatorTest extends TestCase
{
    public function test_it_returns_the_pagination_shape_used_by_frontend_lists(): void
    {
        $paginator = new LengthAwarePaginator(
            items: ['registro 11', 'registro 12'],
            total: 12,
            perPage: 10,
            currentPage: 2,
            options: ['path' => '/matriculas'],
        );

        $data = InertiaPaginator::from($paginator);

        $this->assertSame(['registro 11', 'registro 12'], $data['data']);
        $this->assertSame('/matriculas?page=1', $data['links']['first']);
        $this->assertSame('/matriculas?page=2', $data['links']['last']);
        $this->assertSame('/matriculas?page=1', $data['links']['prev']);
        $this->assertNull($data['links']['next']);
        $this->assertSame(2, $data['meta']['current_page']);
        $this->assertCount(4, $data['meta']['links']);
    }
}
