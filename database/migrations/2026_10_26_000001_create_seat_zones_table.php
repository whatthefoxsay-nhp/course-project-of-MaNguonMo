<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Loại ghế cũ -> khu tương ứng (tên, kind, level, phụ thu, màu) khi chuyển dữ liệu có sẵn. */
    private const LEGACY_TYPES = [
        'svip_diamond' => ['SVIP Sân Khấu Kim Cương', 'seated', 1, 150000, '#D4AF37'],
        'vip_gold' => ['VIP Mặt Sàn Trực Diện', 'seated', 2, 80000, '#E5A93C'],
        'cat1_stand' => ['Khán Đài A - Tầng 1', 'seated', 3, 40000, '#3A5A40'],
        'cat2_wings' => ['Khán Đài Cánh C & D', 'seated', 3, 0, '#1F2937'],
        'skybox_suite' => ['Skybox VIP Suite', 'seated', 4, 200000, '#C08497'],
        'standing_pit' => ['Khu Đứng GA Fanzone', 'standing', 1, -20000, '#99334D'],
        'vip' => ['Ghế VIP', 'seated', 1, 80000, '#E5A93C'],
        'normal' => ['Ghế Tiêu Chuẩn', 'seated', 2, 0, '#4B5563'],
    ];

    public function up(): void
    {
        Schema::create('seat_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->string('code', 12);
            $table->string('name', 120);
            $table->string('kind', 20)->default('seated');
            $table->unsignedTinyInteger('level')->default(1);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->integer('surcharge')->default(0);
            $table->string('color', 7)->default('#3A5A40');
            $table->string('gate', 80)->nullable();
            $table->string('description', 500)->nullable();
            $table->json('perks')->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->timestamps();

            $table->unique(['room_id', 'code']);
        });

        Schema::table('seats', function (Blueprint $table) {
            // Không cascade: xóa khu phải xóa ghế trước (tránh mất vé đã bán).
            $table->foreignId('zone_id')->nullable()->after('room_id')->constrained('seat_zones');
            $table->unsignedSmallInteger('pos_col')->nullable()->after('seat_number');
        });

        $this->backfillExistingSeats();
    }

    public function down(): void
    {
        Schema::table('seats', function (Blueprint $table) {
            $table->dropConstrainedForeignId('zone_id');
            $table->dropColumn('pos_col');
        });

        Schema::dropIfExists('seat_zones');
    }

    /** Mỗi (phòng, loại ghế cũ) thành một khu; gán khu và vị trí cột cho ghế đang có. */
    private function backfillExistingSeats(): void
    {
        $groups = DB::table('seats')
            ->select('room_id', 'type', DB::raw('COUNT(*) as total'))
            ->groupBy('room_id', 'type')
            ->orderBy('room_id')
            ->get();

        $sortOrder = [];
        foreach ($groups as $group) {
            [$name, $kind, $level, $surcharge, $color] = self::LEGACY_TYPES[$group->type]
                ?? [ucfirst((string) $group->type), 'seated', 2, 0, '#4B5563'];
            $code = substr(strtoupper(str_replace('_', '-', (string) $group->type)), 0, 12) ?: 'STD';

            $zoneId = DB::table('seat_zones')->insertGetId([
                'room_id' => $group->room_id,
                'code' => $code,
                'name' => $name,
                'kind' => $kind,
                'level' => $level,
                'sort_order' => $sortOrder[$group->room_id] = ($sortOrder[$group->room_id] ?? -1) + 1,
                'surcharge' => $surcharge,
                'color' => $color,
                'capacity' => $kind === 'standing' ? $group->total : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('seats')
                ->where('room_id', $group->room_id)
                ->where('type', $group->type)
                ->update([
                    'zone_id' => $zoneId,
                    'pos_col' => $kind === 'standing' ? null : DB::raw('seat_number'),
                ]);
        }
    }
};
