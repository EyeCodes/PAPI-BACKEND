<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class ProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_user_can_get_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'salary' => 50000.00
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/profile');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'salary' => 50000.00
                ]
            ]);
    }

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'salary' => 50000.00
        ]);

        $response = $this->actingAs($user)
            ->putJson('/api/profile', [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'salary' => 75000.00
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'name' => 'Jane Doe',
                    'email' => 'jane@example.com',
                    'salary' => 75000.00
                ]
            ]);

        // Verify the database was updated
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'salary' => 75000.00
        ]);
    }

    public function test_user_can_update_only_name(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'salary' => 50000.00
        ]);

        $response = $this->actingAs($user)
            ->putJson('/api/profile', [
                'name' => 'Jane Doe'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Jane Doe',
                    'email' => 'john@example.com', // Unchanged
                    'salary' => 50000.00 // Unchanged
                ]
            ]);
    }

    public function test_user_can_update_only_salary(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'salary' => 50000.00
        ]);

        $response = $this->actingAs($user)
            ->putJson('/api/profile', [
                'salary' => 100000.00
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'John Doe', // Unchanged
                    'email' => 'john@example.com', // Unchanged
                    'salary' => 100000.00
                ]
            ]);
    }

    public function test_user_cannot_update_with_invalid_email(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        $response = $this->actingAs($user)
            ->putJson('/api/profile', [
                'email' => 'invalid-email'
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ]);
    }

    public function test_user_cannot_update_with_duplicate_email(): void
    {
        $user1 = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        $user2 = User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com'
        ]);

        $response = $this->actingAs($user1)
            ->putJson('/api/profile', [
                'email' => 'jane@example.com'
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ]);
    }

    public function test_user_cannot_update_with_negative_salary(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'salary' => 50000.00
        ]);

        $response = $this->actingAs($user)
            ->putJson('/api/profile', [
                'salary' => -1000.00
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ]);
    }

    public function test_user_can_change_password(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('oldpassword')
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/change-password', [
                'current_password' => 'oldpassword',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password changed successfully'
            ]);

        // Verify the password was updated
        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    public function test_user_cannot_change_password_with_incorrect_current_password(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('oldpassword')
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/change-password', [
                'current_password' => 'wrongpassword',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123'
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Current password is incorrect'
            ]);
    }

    public function test_user_cannot_change_password_with_mismatched_confirmation(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('oldpassword')
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/change-password', [
                'current_password' => 'oldpassword',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'differentpassword'
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ]);
    }

    public function test_user_cannot_change_password_with_short_password(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('oldpassword')
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/change-password', [
                'current_password' => 'oldpassword',
                'new_password' => 'short',
                'new_password_confirmation' => 'short'
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ]);
    }

    public function test_user_can_check_salary_when_set(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'salary' => 50000.00
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/check-salary');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'has_salary' => true,
                    'salary' => 50000.00,
                    'message' => 'Salary is set'
                ]
            ]);
    }

    public function test_user_can_check_salary_when_not_set(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'salary' => null
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/check-salary');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'has_salary' => false,
                    'salary' => null,
                    'message' => 'Salary is not set'
                ]
            ]);
    }

    public function test_user_can_check_salary_when_zero(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'salary' => 0.00
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/check-salary');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'has_salary' => false,
                    'salary' => 0.00,
                    'message' => 'Salary is not set'
                ]
            ]);
    }

    public function test_unauthenticated_user_cannot_access_profile_endpoints(): void
    {
        // Test get profile
        $response = $this->getJson('/api/profile');
        $response->assertStatus(401);

        // Test update profile
        $response = $this->putJson('/api/profile', [
            'name' => 'John Doe'
        ]);
        $response->assertStatus(401);

        // Test change password
        $response = $this->postJson('/api/change-password', [
            'current_password' => 'oldpassword',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123'
        ]);
        $response->assertStatus(401);

        // Test check salary
        $response = $this->getJson('/api/check-salary');
        $response->assertStatus(401);
    }
}
