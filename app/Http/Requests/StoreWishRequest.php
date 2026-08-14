<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreWishRequest extends FormRequest
{
    private const int MAX_THUMBNAIL_KILOBYTES = 2_048;

    private const int MAX_LINK_CHARACTERS = 2_048;

    /**
     * The largest price the column can hold — `unsignedInteger`'s ceiling.
     *
     * Validating against it keeps an absurd figure a refusal the person can read
     * rather than an overflow the database decides on their behalf.
     */
    private const int MAX_PRICE = 4_294_967_295;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Title and price are the only things asked for, so adding a wish stays a
     * quick act. The price is required even though the design called it
     * optional: the card's progress bar has no meaning without a target, and a
     * priceless wish would need a second card state nothing else wants.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'purchase_link' => ['nullable', 'string', 'url', 'max:'.self::MAX_LINK_CHARACTERS],
            // A wish costing nothing has no target to raise money against, so a
            // zero is refused as surely as a missing figure.
            'price' => ['required', 'integer', 'min:100000', 'max:'.self::MAX_PRICE],
            'thumbnail' => ['nullable', 'image', 'max:'.self::MAX_THUMBNAIL_KILOBYTES],
        ];
    }
}
