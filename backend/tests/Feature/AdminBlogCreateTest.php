<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminBlogCreateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Super Admin',
            'mobile' => '09120000999',
            'email' => 'admin@posheapp.ir',
            'role' => UserRole::SuperAdmin,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_create_draft_post(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/admin/blog', [
            'title' => 'راهنمای تست وبلاگ پوشه',
            'content' => '<h2>مقدمه</h2><p>'.str_repeat('متن فارسی تست برای مقاله جدید. ', 40).'</p>',
            'excerpt' => 'خلاصه تست',
            'category_slug' => 'software',
            'search_intent' => 'informational',
            'content_type' => 'guide',
            'schema_type' => 'Article',
            'funnel_stage' => 'awareness',
            'author_name' => 'تیم پوشه',
            'is_published' => false,
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'پیش‌نویس با موفقیت ذخیره شد.')
            ->assertJsonPath('data.title', 'راهنمای تست وبلاگ پوشه');

        $this->assertDatabaseHas('blog_posts', [
            'title' => 'راهنمای تست وبلاگ پوشه',
            'review_status' => BlogPost::REVIEW_DRAFT,
        ]);
    }

    public function test_admin_can_force_generate_cover_image(): void
    {
        Sanctum::actingAs($this->admin);

        $post = BlogPost::create([
            'title' => 'مقاله بدون تصویر',
            'slug' => 'post-without-cover-'.uniqid(),
            'content' => '<p>'.str_repeat('متن کافی برای ساخت تصویر شاخص مقاله. ', 50).'</p>',
            'excerpt' => 'بدون کاور',
            'is_published' => true,
            'review_status' => BlogPost::REVIEW_PUBLISHED,
            'published_at' => now(),
            'word_count' => 400,
        ]);

        $this->postJson('/api/v1/admin/blog-images/bootstrap')->assertOk();

        $response = $this->postJson('/api/v1/admin/blog-images/jobs', [
            'blog_post_id' => $post->id,
            'force' => true,
            'run_now' => true,
        ]);

        $response->assertCreated();
        $this->assertNotEmpty($response->json('data.public_url'));

        $post->refresh();
        $this->assertNotEmpty($post->cover_image);
    }

    public function test_blog_categories_endpoint_never_500(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/admin/blog/categories')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }
}
