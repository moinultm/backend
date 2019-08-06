<?php

namespace App\Traits;
use Illuminate\Support\Collection;

class PartialList
{

    private $data;
    private $count;
    private $page;
    private $size;


    public function __construct(Collection $data, int $count, int $page, int $size)
    {
        $this->data = $data;
        $this->count = $count;
        $this->page = $page;
        $this->size = $size;
    }

    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'count' => $this->count,
            'page' => $this->page,
            'size' => $this->size
        ];
    }

}