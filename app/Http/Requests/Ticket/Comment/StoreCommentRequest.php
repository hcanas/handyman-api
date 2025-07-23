<?php

namespace App\Http\Requests\Ticket\Comment;

use App\Http\Requests\BaseFormRequest;
use App\Models\Comment;
use Illuminate\Support\Facades\Gate;

class StoreCommentRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', [Comment::class, $this->route('ticket')]);
    }

    public function rules(): array
    {
        return [
            'message' => 'nullable|string|max:1000|required_without:file',
            'file' => 'nullable|file|mimes:pdf,docx,txt,jpg,jpeg,png,gif,webp|max:5120|required_without:message',
        ];
    }
}
