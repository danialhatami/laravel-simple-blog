<?php

namespace Database\Factories;

use Str;
use App\Models\User;
use App\Models\Article;
use App\Enums\RoleEnum;
use App\Enums\ArticleStatusEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $author = User::whereHas('roles', function ($query) {
            $query->where('name', RoleEnum::AUTHOR->value);
        })->inRandomOrder()->firstOr(function () {
            return User::factory()->createOne()->assignRole(RoleEnum::AUTHOR->value);
        });

        $admin = User::whereHas('roles', function ($query) {
            $query->where('name', RoleEnum::ADMIN->value);
        })->inRandomOrder()->firstOr(function () {
            return User::factory()->createOne()->assignRole(RoleEnum::ADMIN->value);
        });

        $createdAt = $this->faker->dateTimeBetween('-1 year');

        $isPublished = $this->faker->boolean;
        $publishedAt = $isPublished ? $this->faker->dateTimeBetween($createdAt) : null;
        $status = $isPublished ? ArticleStatusEnum::PUBLISHED : ArticleStatusEnum::DRAFT;

        $updatedAt = $this->faker->dateTimeBetween($createdAt, $isPublished ? $publishedAt : 'now');

        return [
            'title' => $this->faker->realText(50),
            'slug' => $this->faker->slug,
            'content' => $this->faker->text,
            'author_id' => $author->id,
            'status' => $status,
            'approver_id' => $admin->id,
            'published_at' => $publishedAt,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ];
    }
}
