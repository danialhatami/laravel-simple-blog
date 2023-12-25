<?php

use App\Models\User;
use App\Enums\ArticleStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title', 512);
            $table->string('slug', 512)->unique();
            $table->text('content');
            $table->foreignIdFor(User::class, 'author_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignIdFor(User::class, 'approver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ArticleStatusEnum::values())->default(ArticleStatusEnum::DRAFT->value);
            $table->timestamp('published_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
