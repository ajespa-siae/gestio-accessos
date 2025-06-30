<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'username' => fake()->unique()->userName(),
            'nif' => fake()->unique()->regexify('[0-9]{8}[A-Z]'),
            'rol_principal' => 'empleat',
            'actiu' => true,
            'ldap_managed' => false,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol_principal' => 'admin',
        ]);
    }

    public function rrhh(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol_principal' => 'rrhh',
        ]);
    }

    public function it(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol_principal' => 'it',
        ]);
    }

    public function gestor(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol_principal' => 'gestor',
        ]);
    }
}
