<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Same envelope as the .NET PagedResult<T>: { items, total, page, pageSize }. */
trait Paginates
{
    /**
     * @param  class-string<JsonResource>  $resource
     */
    protected function paginate(Builder $query, Request $request, string $resource): array
    {
        $page = max(1, (int) $request->query('page', 1));
        $pageSize = min(200, max(1, (int) $request->query('pageSize', 20)));

        $total = (clone $query)->count();
        $items = $query->skip(($page - 1) * $pageSize)->take($pageSize)->get();

        return [
            'items' => $resource::collection($items)->resolve($request),
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
        ];
    }
}
