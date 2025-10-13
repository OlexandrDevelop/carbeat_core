<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ImportStartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null; // Require authenticated admin
    }

    protected function prepareForValidation(): void
    {
        // Support legacy single 'url' by converting it into 'urls' array
        if ($this->has('url') && ! $this->has('urls')) {
            $single = (string) $this->input('url');
            if ($single !== '') {
                $this->merge(['urls' => [$single]]);
            }
        }

        // If a textarea with newline-separated links is sent as 'urls_text', split into array
        if ($this->has('urls_text') && ! $this->has('urls')) {
            $text = (string) $this->input('urls_text');
            $split = collect(preg_split('/\r?\n/', $text))
                ->map(fn ($v) => trim((string) $v))
                ->filter()
                ->values()
                ->all();
            if (! empty($split)) {
                $this->merge(['urls' => $split]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'service_id' => ['required', 'integer', 'min:0'],
            'pages' => ['nullable', 'integer', 'min:1'],
            'urls' => ['required', 'array', 'min:1'],
            'urls.*' => ['required', 'url'],
        ];
    }
}


