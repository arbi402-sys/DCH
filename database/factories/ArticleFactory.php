<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(5),
            'body' => fake()->paragraphs(3, true),
            'image_path' => null,
        ];
    }

    public function approved(): static
    {
        return $this->afterMaking(function (Article $article) {
            $article->status = Article::STATUS_APPROVED;
        });
    }
}
