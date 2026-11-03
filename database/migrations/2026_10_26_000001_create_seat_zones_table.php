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
        if (! Schema::hasTable('seat_zones')) {
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
        }

        $legacyZoneTableExists = Schema::hasTable('room_zones');
        $zoneForeignKeyTarget = $this->zoneForeignKeyTarget();

        if (Schema::hasColumn('seats', 'zone_id') && $zoneForeignKeyTarget !== null && $zoneForeignKeyTarget !== 'seat_zones') {
            Schema::table('seats', fn (Blueprint $table) => $table->dropForeign(['zone_id']));
            $zoneForeignKeyTarget = null;
        }

        Schema::table('seats', function (Blueprint $table): void {
            if (! Schema::hasColumn('seats', 'zone_id')) {
                $table->unsignedBigInteger('zone_id')->nullable()->after('room_id');
            }

            if (! Schema::hasColumn('seats', 'pos_col')) {
                $table->unsignedSmallInteger('pos_col')->nullable()->after('seat_number');
            }
        });

        if ($legacyZoneTableExists) {
            $this->migrateLegacyRoomZones();
        } else {
            $this->backfillExistingSeats();
        }

        if ($zoneForeignKeyTarget === null) {
            Schema::table('seats', function (Blueprint $table): void {
                // Deleting a zone must not delete seats or their bookings.
                $table->foreign('zone_id')->references('id')->on('seat_zones')->nullOnDelete();
            });
        }

        DB::table('seats')->whereNull('pos_col')->update(['pos_col' => DB::raw('seat_number')]);
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

    /**
     * Older local databases used room_zones with seats.zone_id already pointing there.
     * Copy those zones into the current schema and preserve each seat's zone assignment.
     */
    private function migrateLegacyRoomZones(): void
    {
        $typeDetails = [
            'svip_diamond' => [1, 150000, '#D4AF37'],
            'vip_gold' => [2, 80000, '#E5A93C'],
            'cat1_stand' => [3, 40000, '#3A5A40'],
            'cat2_wings' => [3, 0, '#1F2937'],
            'skybox_suite' => [4, 200000, '#C08497'],
            'standing_pit' => [1, -20000, '#99334D'],
        ];

        foreach (DB::table('room_zones')->orderBy('id')->get() as $legacyZone) {
            [$level, $surcharge, $color] = $typeDetails[$legacyZone->seat_type]
                ?? [1, 0, '#4B5563'];
            $kind = in_array($legacyZone->kind, ['seated', 'table', 'standing'], true)
                ? $legacyZone->kind
                : 'standing';
            $code = substr((string) $legacyZone->code, 0, 12) ?: 'STD';

            $zoneId = DB::table('seat_zones')->where('room_id', $legacyZone->room_id)
                ->where('code', $code)->value('id');

            if ($zoneId === null) {
                $zoneId = DB::table('seat_zones')->insertGetId([
                    'room_id' => $legacyZone->room_id,
                    'code' => $code,
                    'name' => $legacyZone->name,
                    'kind' => $kind,
                    'level' => $level,
                    'sort_order' => $legacyZone->sort_order,
                    'surcharge' => $surcharge,
                    'color' => $color,
                    'capacity' => $kind === 'standing' ? min((int) $legacyZone->capacity, 65535) : null,
                    'created_at' => $legacyZone->created_at,
                    'updated_at' => $legacyZone->updated_at,
                ]);
            }

            DB::table('seats')->where('room_id', $legacyZone->room_id)
                ->where('zone_id', $legacyZone->id)
                ->update(['zone_id' => $zoneId]);
        }
    }

    private function zoneForeignKeyTarget(): ?string
    {
        if (! Schema::hasColumn('seats', 'zone_id')) {
            return null;
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            foreach (DB::select('PRAGMA foreign_key_list(seats)') as $foreignKey) {
                if (($foreignKey->from ?? null) === 'zone_id') {
                    return $foreignKey->table;
                }
            }

            return null;
        }

        $constraint = DB::selectOne(
            'select referenced_table_name as target_table from information_schema.key_column_usage where table_schema = database() and table_name = ? and column_name = ? and referenced_table_name is not null limit 1',
            ['seats', 'zone_id']
        );

        return $constraint?->target_table;
    }
};
