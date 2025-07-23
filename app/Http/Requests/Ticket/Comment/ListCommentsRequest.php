<?php

namespace App\Http\Requests\Ticket\Comment;

use App\Http\Requests\BaseFormRequest;
use App\Models\Comment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ListCommentsRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewAny', [Comment::class, $this->route('ticket')]);
    }

    public function rules(): array
    {
        return [
            'page' => 'sometimes|numeric|min:0',
            'per_page' => 'sometimes|numeric|min:0',
            'order_by' => [
                'sometimes',
                'required_with:order_dir',
                Rule::in([
                    'created_at',
                ]),
            ],
            'order_dir' => [
                'sometimes',
                'required_with:order_by',
                Rule::in([
                    'asc',
                    'desc',
                ]),
            ],
        ];
    }
}
