<?php

namespace App\Console\Commands;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class CreateUser extends Command
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create
                            {--admin : Create an administrator account}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a user through the trusted server-side CLI';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isAdmin = (bool) $this->option('admin');

        if ($isAdmin) {
            $this->warn('You are about to create an administrator account.');
            $this->line('Administrator accounts have access to the admin panel.');

            if (! $this->confirm('Continue?', false)) {
                $this->info('User creation cancelled.');

                return self::SUCCESS;
            }
        }

        $input = [
            'name' => trim((string) $this->ask('Name')),
            'email' => Str::lower(trim((string) $this->ask('Email address'))),
            'password' => (string) $this->secret('Password'),
            'password_confirmation' => (string) $this->secret('Confirm password'),
        ];

        try {
            $validator = Validator::make($input, [
                ...$this->profileRules(),
                'password' => $this->passwordRules(),
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $this->error($message);
                }

                return self::INVALID;
            }

            DB::transaction(function () use ($input, $isAdmin): void {
                $user = new User;
                $user->name = $input['name'];
                $user->email = $input['email'];
                $user->password = Hash::make($input['password']);
                $user->is_admin = $isAdmin;
                $user->save();
            });
        } catch (Throwable $exception) {
            report($exception);
            $this->error('The user could not be created. Check the application logs and try again.');

            return self::FAILURE;
        }

        $this->info('User created successfully.');
        $this->line("Email: {$input['email']}");

        return self::SUCCESS;
    }
}
