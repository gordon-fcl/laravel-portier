<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('portier.table_prefix', 'portier_');

        Schema::create($prefix.'roles', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name')->nullable();
            $table->string('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained($prefix.'roles')->nullOnDelete();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $prefix = config('portier.table_prefix', 'portier_');
        Schema::dropIfExists($prefix.'roles');
    }
};
