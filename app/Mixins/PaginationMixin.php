<?php

namespace App\Mixins;

class PaginationMixin
{
    /**
     * Format the list of paginated list of models.
     * 
     * @return \Closure
     */
    public function withPaginated()
    {
        return function(int $perPage = 20, array $columns = ['*']) {
            $data = $this->paginate($perPage, $columns);

            $hasMore = $data->hasMorePages();
            $nextOffset = $hasMore ? $data->currentPage() + 1 : null;

            return [
                'data' => $data->items(),
                'has_more' => $hasMore,
                'next_offset' => $nextOffset,
            ];
        };
    }
}