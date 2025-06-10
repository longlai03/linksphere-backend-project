<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'caption' => 'required|string|max:255',
            'media' => 'nullable|array',
            'privacy' => 'required|string|in:public,friends,private',
        ];
    }

    public function messages(): array
    {
        return [
            'caption.required' => 'Vui lòng nhập nội dung bài viết.',
            'caption.string' => 'Nội dung bài viết phải là chuỗi văn bản.',
            'caption.max' => 'Nội dung bài viết không được vượt quá 255 ký tự.',
            'privacy.required' => 'Vui lòng chọn quyền riêng tư cho bài viết.',
            'privacy.in' => 'Giá trị quyền riêng tư không hợp lệ. Chọn từ các giá trị: public, friends, private.',
        ];
    }
}
