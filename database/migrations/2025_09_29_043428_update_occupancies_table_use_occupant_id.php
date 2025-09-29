<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('occupancies', function (Blueprint $table) {
            // Drop the existing user_id foreign key constraint
            $table->dropForeign(['user_id']);
            
            // Add occupant_id column
            $table->foreignId('occupant_id')->after('room_id')->constrained('occupants')->onDelete('cascade');
            
            // Drop user_id column
            $table->dropColumn('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('occupancies', function (Blueprint $table) {
            // Add back user_id column
            $table->foreignId('user_id')->after('room_id')->constrained('users')->onDelete('cascade');
            
            // Drop occupant_id foreign key and column
            $table->dropForeign(['occupant_id']);
            $table->dropColumn('occupant_id');
        });
    }
};
