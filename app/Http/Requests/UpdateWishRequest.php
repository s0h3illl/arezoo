<?php

namespace App\Http\Requests;

class UpdateWishRequest extends StoreWishRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'remove_thumbnail' => ['boolean'],
        ];
    }
}
