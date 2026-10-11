<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LoginValidationTest extends TestCase
{
    public static function invalidEmails(): array
    {
        return [
            'array' => [['student@example.test'], ''],
            'nested array' => [['email' => ['student@example.test']], ''],
            'invalid string' => ['not-an-email', 'not-an-email'],
            'html string' => ['"><script>alert(1)</script>', '"><script>alert(1)</script>'],
        ];
    }

    #[DataProvider('invalidEmails')]
    public function test_invalid_email_returns_to_a_usable_login_form(mixed $email, string $expectedEmail): void
    {
        // Invalid credentials stop at validation; no database fixtures are needed.
        $this->from('/login')->post('/login', [
            'email' => $email,
            'password' => 'password123',
            'role' => 'anggota',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $response = $this->get('/login')->assertOk()->assertViewIs('auth.login')
            ->assertSee('Format email belum benar.')
            ->assertSee('name="email" class="login-input" value="'.e($expectedEmail).'"', false);

        $response->assertDontSee('<script>alert(1)</script>', false);
        $this->assertGuest();
    }
}
