<?php

namespace App\Http\Requests\Api\V1\Booking;

use Illuminate\Foundation\Http\FormRequest;

class ListBookingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'master_id' => ['nullable', 'integer', 'exists:masters,id'],
            'status' => ['nullable', 'in:pending,confirmed,cancelled'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
