<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('artworks')) {
            return;
        }

        $hadStatus = Schema::hasColumn('artworks', 'status');

        Schema::table('artworks', function (Blueprint $table) {
            if (! Schema::hasColumn('artworks', 'license')) {
                $table->string('license', 50)->default('all_rights_reserved')->after('image_path');
            }
            if (! Schema::hasColumn('artworks', 'originality_confirmed')) {
                $table->boolean('originality_confirmed')->default(false)->after('license');
            }
            if (! Schema::hasColumn('artworks', 'copyright_confirmed_at')) {
                $table->timestamp('copyright_confirmed_at')->nullable()->after('originality_confirmed');
            }
            if (! Schema::hasColumn('artworks', 'status')) {
                $table->string('status', 20)->default('pending')->index()->after('copyright_confirmed_at');
            }
            if (! Schema::hasColumn('artworks', 'moderation_note')) {
                $table->text('moderation_note')->nullable()->after('status');
            }
            if (! Schema::hasColumn('artworks', 'moderated_by')) {
                $table->foreignId('moderated_by')->nullable()->after('moderation_note')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('artworks', 'moderated_at')) {
                $table->timestamp('moderated_at')->nullable()->after('moderated_by');
            }
        });

        // Backward-compatibility: keep all pre-existing artworks publicly visible as approved.
        if (! $hadStatus) {
            DB::table('artworks')->update(['status' => 'approved']);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('artworks')) {
            return;
        }

        Schema::table('artworks', function (Blueprint $table) {
            if (Schema::hasColumn('artworks', 'moderated_by')) {
                $table->dropConstrainedForeignId('moderated_by');
            }
            if (Schema::hasColumn('artworks', 'status')) {
                $table->dropIndex(['status']);
            }

            $columns = array_values(array_filter([
                'license',
                'originality_confirmed',
                'copyright_confirmed_at',
                'status',
                'moderation_note',
                'moderated_at',
            ], fn (string $col) => Schema::hasColumn('artworks', $col)));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
