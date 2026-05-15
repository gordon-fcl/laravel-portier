<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('portier.table_prefix', 'portier_');

        Schema::create($prefix.'role_permissions', function (Blueprint $table) use ($prefix) {
            $table->foreignId('role_id')->constrained($prefix.'roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained($prefix.'permissions')->cascadeOnDelete();
            $table->boolean('granted')->default(true);
            $table->primary(['role_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        $prefix = config('portier.table_prefix', 'portier_');
        Schema::dropIfExists($prefix.'role_permissions');
    }
};
