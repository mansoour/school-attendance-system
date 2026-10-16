<?php

namespace Tests\Feature\Auth;

use App\Models\School;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_guardians_can_register_with_matching_student_details(): void
    {
        $school = School::create(['name' => 'Sample School']);
        Student::create(['school_id' => $school->id, 'name' => 'سارة أحمد', 'national_id' => '1000000001']);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '0500000000',
            'relation' => 'father',
            'student_national_id' => '1000000001',
            'student_name' => 'ساره احمد',
            'password' => 'password-12345',
            'password_confirmation' => 'password-12345',
            'terms' => true,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registration_is_refused_when_student_details_do_not_match(): void
    {
        $school = School::create(['name' => 'Sample School']);
        Student::create(['school_id' => $school->id, 'name' => 'Sara Ahmed', 'national_id' => '1000000001']);

        $this->post('/register', [
            'name' => 'Test User', 'email' => 'test@example.com', 'phone' => '0500000000', 'relation' => 'mother',
            'student_national_id' => '1000000001', 'student_name' => 'Someone Else',
            'password' => 'password-12345', 'password_confirmation' => 'password-12345', 'terms' => true,
        ])->assertSessionHasErrors('student_national_id');

        $this->assertGuest();
    }
}
