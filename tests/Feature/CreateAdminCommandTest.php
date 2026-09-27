<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_admin(): void
    {
        $this->artisan('app:create-admin', ['--name' => 'Chủ quán', '--email' => 'owner@test.vn', '--password' => 'mat-khau-123'])
            ->assertSuccessful();

        $user = User::firstWhere('email', 'owner@test.vn');
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('mat-khau-123', $user->password));
    }

    public function test_resets_password_of_existing_user_and_validates(): void
    {
        User::factory()->inactive()->create(['email' => 'owner@test.vn']);

        $this->artisan('app:create-admin', ['--name' => 'A', '--email' => 'owner@test.vn', '--password' => 'mat-khau-moi'])->assertSuccessful();

        $user = User::firstWhere('email', 'owner@test.vn');
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('mat-khau-moi', $user->password));

        $this->artisan('app:create-admin', ['--name' => 'A', '--email' => 'sai-email', '--password' => '123'])->assertFailed();
    }
}
