<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Post;

class PostApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_posts()
    {
        Post::factory()->count(3)->create();
        $response = $this->getJson('/api/posts');
        $response->assertStatus(200)->assertJsonCount(3);
    }

    public function test_store_creates_post()
    {
        $data = ['title' => 'Título', 'content' => 'Contenido'];
        $response = $this->postJson('/api/posts', $data);
        $response->assertStatus(201)->assertJsonFragment($data);
    }

    public function test_show_returns_post()
    {
        $post = Post::factory()->create();
        $response = $this->getJson("/api/posts/{$post->id}");
        $response->assertStatus(200)->assertJsonFragment(['title' => $post->title]);
    }

    public function test_update_modifies_post()
    {
        $post = Post::factory()->create();
        $data = ['title' => 'Nuevo título'];
        $response = $this->putJson("/api/posts/{$post->id}", $data);
        $response->assertStatus(200)->assertJsonFragment($data);
    }

    public function test_destroy_deletes_post()
    {
        $post = Post::factory()->create();
        $response = $this->deleteJson("/api/posts/{$post->id}");
        $response->assertStatus(200);
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }
}
