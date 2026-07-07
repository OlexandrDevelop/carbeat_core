<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddGuestReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'rating' => 'required|integer|between:1,5',
            'review' => 'required|string|min:2|max:2000',
        ];
    }
}
