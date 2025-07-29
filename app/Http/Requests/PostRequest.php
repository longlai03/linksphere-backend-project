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
     */
    public function rules(): array
    {
        return [
            'caption' => 'required|string|max:255',
            'media' => 'nullable|array',
            'media.*.position' => 'nullable|integer|min:0',
            'media.*.tagged_user' => 'nullable|string|max:255',
            'media.*.base64' => 'nullable|string',
            'media.*.original_file_name' => 'nullable|string|max:255',
            'privacy' => 'required|string|in:public,friends,private',
        ];
    }

    public function messages(): array
    {
        return [
            'caption.required' => 'Vui lòng nhập nội dung bài viết.',
            'caption.string' => 'Nội dung bài viết phải là chuỗi văn bản.',
            'caption.max' => 'Nội dung bài viết không được vượt quá 255 ký tự.',
            'media.array' => 'Media phải là một mảng.',
            'media.*.position.integer' => 'Vị trí media phải là số nguyên.',
            'media.*.position.min' => 'Vị trí media không được nhỏ hơn 0.',
            'media.*.tagged_user.string' => 'Tagged user phải là chuỗi văn bản.',
            'media.*.tagged_user.max' => 'Tagged user không được vượt quá 255 ký tự.',
            'media.*.base64.string' => 'Base64 data phải là chuỗi.',
            'media.*.original_file_name.string' => 'Tên file gốc phải là chuỗi văn bản.',
            'media.*.original_file_name.max' => 'Tên file gốc không được vượt quá 255 ký tự.',
            'privacy.required' => 'Vui lòng chọn quyền riêng tư cho bài viết.',
            'privacy.in' => 'Giá trị quyền riêng tư không hợp lệ. Chọn từ các giá trị: public, friends, private.',
        ];
    }
}
