<?php

namespace App\Http\Requests\Api\V1\EasyWeek;

use Illuminate\Foundation\Http\FormRequest;

class ConnectEasyWeekRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'easyweek_url' => ['required', 'string', 'max:255'],
        ];
    }
}
