<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_ordinary_user_with_a_hashed_password(): void
    {
        $this->artisan('user:create')
            ->expectsQuestion('Name', 'CLI User')
            ->expectsQuestion('Email address', 'USER@EXAMPLE.COM')
            ->expectsQuestion('Password', 'SecurePassword123!')
            ->expectsQuestion('Confirm password', 'SecurePassword123!')
            ->expectsOutput('User created successfully.')
            ->expectsOutput('Email: user@example.com')
            ->assertSuccessful();

        $user = User::query()->sole();

        $this->assertSame('CLI User', $user->name);
        $this->assertSame('user@example.com', $user->email);
        $this->assertFalse($user->is_admin);
        $this->assertNotSame('SecurePassword123!', $user->password);
        $this->assertTrue(Hash::check('SecurePassword123!', $user->password));
    }

    public function test_it_rejects_a_duplicate_email_address(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $this->artisan('user:create')
            ->expectsQuestion('Name', 'Duplicate User')
            ->expectsQuestion('Email address', 'existing@example.com')
            ->expectsQuestion('Password', 'SecurePassword123!')
            ->expectsQuestion('Confirm password', 'SecurePassword123!')
            ->expectsOutputToContain('email has already been taken')
            ->assertExitCode(Command::INVALID);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_it_rejects_invalid_input_without_creating_a_user(): void
    {
        $this->artisan('user:create')
            ->expectsQuestion('Name', '')
            ->expectsQuestion('Email address', 'not-an-email')
            ->expectsQuestion('Password', 'short')
            ->expectsQuestion('Confirm password', 'different')
            ->expectsOutputToContain('email field must be a valid email address')
            ->assertExitCode(Command::INVALID);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_it_creates_an_administrator_only_after_confirmation(): void
    {
        $this->artisan('user:create', ['--admin' => true])
            ->expectsOutput('You are about to create an administrator account.')
            ->expectsOutput('Administrator accounts have access to the admin panel.')
            ->expectsConfirmation('Continue?', 'yes')
            ->expectsQuestion('Name', 'Administrator')
            ->expectsQuestion('Email address', 'admin@example.com')
            ->expectsQuestion('Password', 'SecurePassword123!')
            ->expectsQuestion('Confirm password', 'SecurePassword123!')
            ->assertSuccessful();

        $this->assertTrue(User::query()->sole()->is_admin);
    }

    public function test_it_does_not_create_an_administrator_when_confirmation_is_declined(): void
    {
        $this->artisan('user:create', ['--admin' => true])
            ->expectsConfirmation('Continue?', 'no')
            ->expectsOutput('User creation cancelled.')
            ->assertSuccessful();

        $this->assertDatabaseCount('users', 0);
    }
}
