<?php

use App\Enums\DayOfWeek;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();
            $table->enum('day_of_week', array_column(DayOfWeek::cases(), 'value'));
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            $table->index('day_of_week');
        });

        $now = now();

        DB::table('availabilities')->insert(array_map(
            fn (DayOfWeek $day) => [
                'day_of_week' => $day->value,
                'start_time' => in_array($day, [DayOfWeek::SATURDAY, DayOfWeek::SUNDAY], true) ? '10:00:00' : '13:00:00',
                'end_time' => '22:00:00',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            DayOfWeek::cases(),
        ));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('availabilities');
    }
};
