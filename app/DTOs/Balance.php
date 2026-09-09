<?php

namespace App\DTOs;

class Balance
{
    public function __construct(
        public int $available,
        public int $held,
    ) {}

    public function total(): int
    {
        return $this->available + $this->held;
    }

    /**
     * @return array{total: int, available: int, held: int}
     */
    public function toArray(): array
    {
        return [
            'total' => $this->total(),
            'available' => $this->available,
            'held' => $this->held,
        ];
    }
}
