<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InertiaPaginator
{
    /** @return array{data: array<int, mixed>, links: array{first: string|null, last: string|null, prev: string|null, next: string|null}, meta: array{current_page: int, from: int|null, last_page: int, path: string, per_page: int, to: int|null, total: int, links: array<int, array{url: string|null, label: string, active: bool}>}} */
    public static function from(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => $paginator->items(),
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'path' => $paginator->path(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
                'links' => $paginator->linkCollection()->toArray(),
            ],
        ];
    }
}
