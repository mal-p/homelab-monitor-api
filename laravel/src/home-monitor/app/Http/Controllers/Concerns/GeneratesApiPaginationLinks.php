<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Pagination\Paginator;

trait GeneratesApiPaginationLinks
{
    protected function generatePaginationLinks(Paginator $resource): array
    {
        $links = [];
        $currentPage = $resource->currentPage();
        $path = $resource->path();

        if ($resource->hasMorePages()) {
            $nextPage = $currentPage + 1;

            $links[] = [
                'href' => "{$path}?page={$nextPage}",
                'rel' => 'next',
                'method' => 'GET',
            ];
        }

        if ($currentPage > 1) {
            $prevPage = $currentPage - 1;

            $links[] = [
                'href' => "{$path}?page={$prevPage}",
                'rel' => 'prev',
                'method' => 'GET',
            ];
        }

        return $links;
    }
}
