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
        // First, update existing 'paid' and 'unpaid' statuses to 'deposit'
        DB::table('occupancies')
            ->whereIn('status', ['paid', 'unpaid'])
            ->update(['status' => 'deposit']);

        // Drop the existing enum column and recreate with new values
        Schema::table('occupancies', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('occupancies', function (Blueprint $table) {
            $table->enum('status', ['deposit', 'terminated'])->default('deposit')->after('occupant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('occupancies', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('occupancies', function (Blueprint $table) {
            $table->enum('status', ['deposit', 'paid', 'unpaid', 'terminated'])->default('deposit')->after('occupant_id');
        });
    }
};