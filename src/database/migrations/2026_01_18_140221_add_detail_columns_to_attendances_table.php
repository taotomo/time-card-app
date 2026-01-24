<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // break_timeカラムが存在する場合のみ削除
            if (Schema::hasColumn('attendances', 'break_time')) {
                $table->dropColumn('break_time');
            }
            
            // 休憩開始・終了時刻（複数対応のため、JSON形式で保存）
            // SQLiteとMySQLの互換性のためtextを使用
            if (!Schema::hasColumn('attendances', 'break_times')) {
                $table->text('break_times')->nullable()->comment('休憩時間帯（開始・終了のペア）');
            }
            
            // 備考
            if (!Schema::hasColumn('attendances', 'remarks')) {
                $table->text('remarks')->nullable()->comment('備考');
            }
            
            // 承認ステータス（0: 通常、1: 承認待ち、2: 承認済み、3: 却下）
            if (!Schema::hasColumn('attendances', 'approval_status')) {
                $table->tinyInteger('approval_status')->default(0)->comment('承認ステータス');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['break_times', 'remarks', 'approval_status']);
            $table->integer('break_time')->default(0)->comment('休憩時間（分）');
        });
    }
};
