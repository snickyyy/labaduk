<?php

namespace App\DTOs;

use App\Http\Requests\Api\StoreAppointmentRequest;
use Carbon\CarbonImmutable;

final readonly class CreateAppointmentDTO
{
    /**
     * @param  CarbonImmutable  $startAt  начало записи, нормализованное к UTC
     */
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $phoneNumber,
        public CarbonImmutable $startAt,
    ) {}

    public static function fromRequest(StoreAppointmentRequest $request): self
    {
        return new self(
            firstName: (string) $request->validated('first_name'),
            lastName: (string) $request->validated('last_name'),
            email: (string) $request->validated('email'),
            phoneNumber: (string) $request->validated('phone_number'),
            startAt: CarbonImmutable::parse((string) $request->validated('start_at'))->utc(),
        );
    }

    /**
     * @return array<string, string|CarbonImmutable>
     */
    public function toArray(): array
    {
        return [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'phone_number' => $this->phoneNumber,
            'start_at' => $this->startAt,
        ];
    }
}
