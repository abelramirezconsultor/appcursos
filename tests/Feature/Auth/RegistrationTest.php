<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'business_name' => 'Academia Fiscal',
            'alias' => 'appcursos',
            'course_area' => 'Contabilidad',
            'years_experience' => '6-10',
            'team_size_range' => '2-5',
            'expected_students_6m' => 120,
            'planned_courses_year_1' => 12,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas((new User)->getTable(), [
            'email' => 'test@example.com',
        ]);
    }
}
