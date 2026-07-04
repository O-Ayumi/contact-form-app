<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ContactSeeder;
use Database\Seeders\TagSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            CategorySeeder::class,
            TagSeeder::class,
            UserSeeder::class,
            ContactSeeder::class,
        ]);
    }

    /** @test */
    public function 認証済みユーザーはタグの編集画面を表示できる(): void
    {
        $user = User::first();
        $tag = Tag::first();

        $response = $this->actingAs($user)->get(route('admin.tags.edit', $tag));

        $response->assertStatus(200);
        $response->assertViewIs('admin.tags.edit');
    }

    /** @test */
    public function 認証ユーザーはタグを作成できる(): void
    {
        $user = User::first();
        $contact = Contact::first();

        $response = $this->actingAs($user)->post(route('admin.tags.store'), [
            'name' => 'テストタグ',
            'contact_id' => $contact->id,
        ]);

        $response->assertRedirect(route('admin.index'));
        $this->assertDatabaseHas('tags', [
            'name' => 'テストタグ',
        ]);
    }

    /** @test */
    public function 認証ユーザーは_pu_tでタグを更新できる(): void
    {
        $user = User::first();
        $tag = Tag::first();

        $response = $this->actingAs($user)->put(route('admin.tags.update', $tag), [
            'name' => '更新後のタグ',
        ]);

        $response->assertRedirect(route('admin.index'));
        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => '更新後のタグ',
        ]);
    }

    /** @test */
    public function 認証済みユーザーは_delet_eでタグを削除できる(): void
    {
        $user = User::first();
        $tag = Tag::first();

        $response = $this->actingAs($user)->delete(route('admin.tags.destroy', $tag));

        $response->assertRedirect(route('admin.index'));
        $this->assertDatabaseMissing('tags', [
            'id' => $tag->id,
        ]);
    }

    /** @test */
    public function 未認証ユーザーはタグ操作が拒否されログイン画面にリダイレクトされる(): void
    {
        $tag = Tag::first();

        $storeResponse = $this->post(route('admin.tags.store'), ['name' => '拒否テスト']);
        $storeResponse->assertRedirect('/login');

        $editResponse = $this->get(route('admin.tags.edit', $tag));
        $editResponse->assertRedirect('/login');

        $updateResponse = $this->put(route('admin.tags.update', $tag), [
            'name' => '拒否テスト',
        ]);
        $updateResponse->assertRedirect('/login');

        $destroyResponse = $this->delete(route('admin.tags.destroy', $tag));
        $destroyResponse->assertRedirect('/login');
    }

    /** @test */
    public function タグ名が空だとバリデーションエラーになる(): void
    {
        $user = User::first();

        $response = $this->actingAs($user)->post(route('admin.tags.store'), [
            'name' => '',
        ]);

        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function タグ名は50文字まで入力できる(): void
    {
        $user = User::first();

        $response = $this->actingAs($user)->post(route('admin.tags.store'), [
            'name' => str_repeat('あ', 50),
        ]);

        $response->assertRedirect(route('admin.index'));
        $this->assertDatabaseHas('tags', [
            'name' => str_repeat('あ', 50),
        ]);
    }

    /** @test */
    public function タグ名が50文字以上だとバリデーションエラーになる(): void
    {
        $user = User::first();

        $response = $this->actingAs($user)->post(route('admin.tags.store'), [
            'name' => str_repeat('あ', 51),
        ]);

        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function 同じタグ名は新規作成できない(): void
    {
        $user = User::first();
        $contact = Contact::first();
        Tag::create(['name' => '既存のタグ']);

        $response = $this->actingAs($user)->post(route('admin.tags.store'), [
            'name' => '既存のタグ',
            'contact_id' => $contact->id,
        ]);

        $response->assertSessionHasErrors(['name']);
        $this->assertEquals(1, Tag::where(['name' => '既存のタグ'])->count());
    }

    /** @test */
    public function 自身の名前維持は可能だが、他の既存のタグ名への変更は拒否される(): void
    {
        $user = User::first();
        $tagA = Tag::create(['name' => 'タグA']);
        Tag::create(['name' => 'タグB']);

        $response1 = $this->actingAs($user)->put(route('admin.tags.update', $tagA), [
            'name' => 'タグB',
        ]);

        $response1->assertSessionHasErrors(['name']);

        $response2 = $this->actingAs($user)->put(route('admin.tags.update', $tagA), [
            'name' => 'タグA',
        ]);

        $response2->assertRedirect(route('admin.index'));
    }

    /** @test */
    public function 一つのタグは複数のお問い合わせに紐づいている(): void
    {
        $tag = Tag::first();
        $contacts = Contact::take(2)->get();
        $contactA = $contacts[0];
        $contactB = $contacts[1];

        $tag->contacts()->sync([$contactA->id, $contactB->id]);

        $this->assertEquals(2, $tag->contacts()->count());
        $this->assertTrue($tag->contacts->contains($contactA));
        $this->assertTrue($tag->contacts->contains($contactB));
    }
}
