<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEntity extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'file' => 'required_without:metadata|file',
            'metadata' => 'nullable|string',
            'federation' => 'required|numeric',
            'explanation' => 'required|max:255',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $validator->safe();

            if (! empty($data['file']) && ! empty($data['metadata'])) {
                $validator->errors()->add('file', 'You cannot provide both a file and metadata. Please choose one.');
                $validator->errors()->add('metadata', 'You cannot provide both metadata and a file. Please choose one.');
            }
        });
    }
}
