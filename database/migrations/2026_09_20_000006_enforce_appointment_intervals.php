<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->assertExistingAppointmentsCanBeConstrained();
        $this->replaceLegacySchedule();

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropUnique('appointments_start_at_unique');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE appointments
                ADD CONSTRAINT appointments_no_overlapping_active
                EXCLUDE USING gist (
                    tsrange(
                        start_at,
                        COALESCE(end_at, start_at + interval '1 hour'),
                        '[)'
                    ) WITH &&
                )
                WHERE (status IN ('CREATED', 'SUCCESSFUL', 'PIDOR'))
            SQL);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE appointments DROP CONSTRAINT IF EXISTS appointments_no_overlapping_active');
        }

        Schema::table('appointments', function (Blueprint $table) {
            $table->unique('start_at', 'appointments_start_at_unique');
        });
    }

    private function assertExistingAppointmentsCanBeConstrained(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $invalid = DB::selectOne(<<<'SQL'
            SELECT id
            FROM appointments
            WHERE end_at IS NOT NULL
              AND end_at <= start_at
              AND status IN ('CREATED', 'SUCCESSFUL', 'PIDOR')
            LIMIT 1
        SQL);

        if ($invalid) {
            throw new RuntimeException(
                "Cannot add appointment interval constraint: active appointment {$invalid->id} has end_at <= start_at. Run appointments:audit-schedule and resolve the record first."
            );
        }

        $overlap = DB::selectOne(<<<'SQL'
            SELECT first.id AS first_id, second.id AS second_id
            FROM appointments AS first
            INNER JOIN appointments AS second
                ON first.id < second.id
               AND first.start_at < COALESCE(second.end_at, second.start_at + interval '1 hour')
               AND COALESCE(first.end_at, first.start_at + interval '1 hour') > second.start_at
            WHERE first.status IN ('CREATED', 'SUCCESSFUL', 'PIDOR')
              AND second.status IN ('CREATED', 'SUCCESSFUL', 'PIDOR')
            LIMIT 1
        SQL);

        if ($overlap) {
            throw new RuntimeException(
                "Cannot add appointment interval constraint: active appointments {$overlap->first_id} and {$overlap->second_id} overlap. Run appointments:audit-schedule and resolve them first."
            );
        }
    }

    private function replaceLegacySchedule(): void
    {
        $weekdays = ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY'];

        DB::table('availabilities')
            ->whereIn('day_of_week', $weekdays)
            ->update([
                'start_time' => '14:00:00',
                'end_time' => '18:00:00',
                'updated_at' => now(),
            ]);

        DB::table('availabilities')
            ->whereIn('day_of_week', ['SATURDAY', 'SUNDAY'])
            ->delete();

        foreach ($weekdays as $weekday) {
            if (! DB::table('availabilities')->where('day_of_week', $weekday)->exists()) {
                DB::table('availabilities')->insert([
                    'day_of_week' => $weekday,
                    'start_time' => '14:00:00',
                    'end_time' => '18:00:00',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
