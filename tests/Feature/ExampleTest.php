<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_homepage_uses_personal_copy_and_only_registration_cards(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Kamu tidak harus melewati semuanya sendirian.')
            ->assertSee('Daftar sebagai mahasiswa')
            ->assertSee('Daftar sebagai konselor')
            ->assertSee('Angka di platform ini')
            ->assertSee(route('register.student'))
            ->assertSee(route('register.counselor'))
            ->assertDontSee('Cara kerja')
            ->assertDontSee('Admin')
            ->assertDontSee('Kuesioner dan batasan');
    }
}
