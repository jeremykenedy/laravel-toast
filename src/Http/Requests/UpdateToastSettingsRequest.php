<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelToast\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Jeremykenedy\LaravelToast\Support\ToastSettings;

class UpdateToastSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ToastSettings::canManage($this->user());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ToastSettings::rules();
    }

    protected function prepareForValidation(): void
    {
        $options = $this->input('options');

        if (!is_array($options)) {
            return;
        }

        foreach (ToastSettings::fields() as $key => $field) {
            if ($field['type'] === 'checkbox' && array_key_exists($key, $options) && $options[$key] === '') {
                $options[$key] = false;
            }
        }

        $this->merge(['options' => $options]);
    }
}
