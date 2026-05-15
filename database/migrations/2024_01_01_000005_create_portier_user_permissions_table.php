<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('portier.table_prefix', 'portier_');

        Schema::create($prefix.'user_permissions', function (Blueprint $table) use ($prefix) {
            $table->unsignedBigInteger('user_id');
            $table->string('user_type');
            $table->foreignId('permission_id')->constrained($prefix.'permissions')->cascadeOnDelete();
            $table->boolean('granted')->default(true);
            $table->primary(['user_id', 'user_type', 'permission_id']);
            $table->index(['user_id', 'user_type']);
        });
    }

    public function down(): void
    {
        $prefix = config('portier.table_prefix', 'portier_');
        Schema::dropIfExists($prefix.'user_permissions');
    }
};
