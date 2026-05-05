<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('portier.table_prefix', 'portier_');

        Schema::create($prefix.'user_roles', function (Blueprint $table) use ($prefix) {
            $table->unsignedBigInteger('user_id');
            $table->string('user_type');
            $table->foreignId('role_id')->constrained($prefix.'roles')->cascadeOnDelete();
            $table->primary(['user_id', 'user_type', 'role_id']);
            $table->index(['user_id', 'user_type']);
        });
    }

    public function down(): void
    {
        $prefix = config('portier.table_prefix', 'portier_');
        Schema::dropIfExists($prefix.'user_roles');
    }
};
