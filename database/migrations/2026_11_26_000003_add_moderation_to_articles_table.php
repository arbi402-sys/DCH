<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('articles')) {
            return;
        }

        $hadStatus = Schema::hasColumn('articles', 'status');

        Schema::table('articles', function (Blueprint $table) {
            if (! Schema::hasColumn('articles', 'status')) {
                $table->string('status', 20)->default('pending')->index()->after('image_path');
            }
            if (! Schema::hasColumn('articles', 'moderation_note')) {
                $table->text('moderation_note')->nullable()->after('status');
            }
            if (! Schema::hasColumn('articles', 'moderated_by')) {
                $table->foreignId('moderated_by')->nullable()->after('moderation_note')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('articles', 'moderated_at')) {
                $table->timestamp('moderated_at')->nullable()->after('moderated_by');
            }
        });

        // Backward-compatibility: keep all pre-existing articles publicly visible as approved
        // (including databases that already had a legacy status column such as 'published').
        if (! $hadStatus) {
            DB::table('articles')->update(['status' => 'approved']);
        } else {
            DB::table('articles')
                ->whereNull('status')
                ->orWhereNotIn('status', ['pending', 'approved', 'rejected'])
                ->update(['status' => 'approved']);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('articles')) {
            return;
        }

        Schema::table('articles', function (Blueprint $table) {
            if (Schema::hasColumn('articles', 'moderated_by')) {
                $table->dropConstrainedForeignId('moderated_by');
            }
            if (Schema::hasColumn('articles', 'status')) {
                $table->dropIndex(['status']);
            }

            $columns = array_values(array_filter([
                'status',
                'moderation_note',
                'moderated_at',
            ], fn (string $col) => Schema::hasColumn('articles', $col)));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
