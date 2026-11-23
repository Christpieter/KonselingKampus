<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_navigation_shows_profile_dropdown_with_logout_form(): void
    {
        $user = User::factory()->create([
            'role' => 'mahasiswa',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee('id="profile-menu"', false)
            ->assertSee('Menu profil '.$user->name)
            ->assertSee('Keluar')
            ->assertSee(route('logout'), false);
    }

    public function test_logout_ends_the_authenticated_session(): void
    {
        $user = User::factory()->create([
            'role' => 'mahasiswa',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect('/');

        $this->assertGuest();
    }
}
