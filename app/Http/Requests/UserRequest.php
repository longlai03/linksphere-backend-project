<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
        $routeName = $this->route()->getName();
        $userId = $this->route('user') ? $this->route('user')->_id : null;
        if ($routeName === 'login') {
            return [
                'email' => 'required|email',
                'password' => 'required|string',
            ];
        }
        if ($routeName === 'register') {
            return [
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
                'username' => 'required|string',
                'nickname' => 'nullable|string',
            ];
        }
        if ($routeName === 'update-user') {
            return [
                'nickname' => 'nullable|string',
                'avatar_url' => 'nullable|image',
                'gender' => 'nullable|string',
                'birthday' => 'nullable|date',
                'address' => 'nullable|string',
                'bio' => 'nullable|string',
                'hobbies' => 'nullable|string',
            ];
        }
        return [
            'email' => 'required|email|unique:users,email' . ($userId ? ',' . $userId . ',_id' : ''),
            'phone' => 'nullable|string|max:20',
            'password' => $this->isMethod('post') ? 'required|string' : 'nullable|string',
            'username' => 'required|string',
            'nickname' => 'nullable|string',
            'avatar_url' => 'nullable|image',
            'gender' => 'nullable|string',
            'birthday' => 'nullable|date',
            'address' => 'nullable|string',
            'bio' => 'nullable|string',
            'hobbies' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        $routeName = $this->route()->getName();
        if ($routeName === 'login') {
            return [
                'email.required' => 'Email là bắt buộc.',
                'email.email' => 'Email không đúng định dạng.',
                'password.required' => 'Mật khẩu là bắt buộc.',
            ];
        }
        if ($routeName === 'register') {
            return [
                'email.required' => 'Email là bắt buộc.',
                'email.email' => 'Email không đúng định dạng.',
                'email.unique' => 'Email đã tồn tại.',
                'password.required' => 'Mật khẩu là bắt buộc.',
                'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
                'username.required' => 'Tên người dùng là bắt buộc.',
                'username.max' => 'Tên người dùng không được vượt quá 255 ký tự.',
            ];
        }
        return [];
    }
}
