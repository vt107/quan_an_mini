<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Tạo tài khoản quản trị (hoặc đặt lại mật khẩu nếu email đã có): dùng khi cài đặt trên máy chủ.
 */
#[Signature('app:create-admin {--name=} {--email=} {--password=}')]
#[Description('Tạo tài khoản admin (quản trị) đầu tiên')]
class CreateAdminUser extends Command
{
    public function handle(): int
    {
        $data = [
            'name' => $this->option('name') ?: text('Họ tên', required: true),
            'email' => $this->option('email') ?: text('Email đăng nhập', required: true),
            'password' => $this->option('password') ?: password('Mật khẩu (tối thiểu 8 ký tự)', required: true),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::updateOrCreate(['email' => $data['email']], [
            'name' => $data['name'],
            'password' => $data['password'],
            'is_active' => true,
        ]);

        $this->info(($user->wasRecentlyCreated ? 'Đã tạo' : 'Đã cập nhật')." tài khoản admin {$user->email}. Đăng nhập tại ".url('/admin'));

        return self::SUCCESS;
    }
}
