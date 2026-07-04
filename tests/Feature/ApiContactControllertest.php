<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ContactSeeder;
use Database\Seeders\TagSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiContactControllerTest extends TestCase
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
    public function jso_n形式でページネーションされた一覧が表示される(): void
    {
        $user = User::first();

        $response = $this->actingAs($user)->getJson('/api/v1/contacts?per_page=7');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'email', 'gender', 'gender_label', 'tel', 'address', 'building', 'detail'],
            ],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'from', 'last_page', 'per_page', 'to', 'total'],
        ]);

        $perPage = $response->json('meta.per_page');
        $this->assertLessThan($response->json('meta.total'), $perPage);
    }

    /** @test */
    public function 一覧表示の際に条件で検索し絞り込み表示できる(): void
    {
        $user = User::first();
        $targetContact = Contact::first();
        $targetContact->update([
            'first_name' => '検証用',
            'last_name' => '太郎',
            'gender' => 2,
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/contacts?keyword=検証用&gender=2');

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'name' => '検証用 太郎',
        ]);

        $responseData = $response->json('data');
        foreach ($responseData as $item) {
            $this->assertEquals(2, $item['gender']);
        }
    }

    /** @test */
    public function 一覧表示のバリデーションエラー時は422が返る(): void
    {
        $user = User::first();
        $invalidFilters = [
            'keyword' => str_repeat('あ', 256),
            'gender' => 4,
            'category_id' => 999999,
            'date' => 'invalid-date',
            'per_page' => 101,
            'page' => 0,
        ];

        $response = $this->actingAs($user)->getJson('/api/v1/contacts?'.http_build_query($invalidFilters));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'keyword',
            'gender' => '性別の値が不正です',
            'category_id' => '選択されたカテゴリーが存在しません',
            'date',
            'per_page',
            'page',
        ]);
    }

    /** @test */
    public function jso_n形式の詳細画面が表示される(): void
    {
        $user = User::first();
        $contact = Contact::first();

        $response = $this->actingAs($user)->getJson("/api/v1/contacts/{$contact->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'id' => $contact->id,
                'name' => $contact->first_name.' '.$contact->last_name,
            ],
        ]);
    }

    /** @test */
    public function 存在しない_i_dで404エラーが返る(): void
    {
        $user = User::first();

        $response = $this->actingAs($user)->getJson('/api/v1/contacts/999999');

        $response->assertStatus(404);
    }

    /** @test */
    public function お問い合わせのレコードが作成され201が返る(): void
    {
        $user = User::first();
        $category = Category::first();
        $tags = Tag::take(2)->pluck('id')->toArray();
        $data = [
            'first_name' => '新規',
            'last_name' => '作成',
            'gender' => 1,
            'email' => 'new.user@example.com',
            'tel' => '09012345678',
            'address' => '東京都',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ新規作成',
            'tag_ids' => $tags,
        ];

        $response = $this->actingAs($user)->postJson('/api/v1/contacts', $data);

        $response->assertStatus(201);
        $response->assertJson([
            'data' => [
                'name' => '新規 作成',
                'email' => 'new.user@example.com',
            ],
        ]);
        $this->assertDatabaseHas('contacts', [
            'first_name' => '新規',
            'email' => 'new.user@example.com',
        ]);
    }

    /** @test */
    public function お問い合わせ作成のバリデーションエラー時は422が返る(): void
    {
        $user = User::first();
        $invalidData = [
            'first_name' => '',
            'last_name' => '',
            'gender' => 5,
            'email' => 'not-an-email',
            'tel' => '090-1234-5678',
            'address' => '東京都',
            'category_id' => 999999,
            'detail' => '詳細内容',
            'tag_ids' => [999999],
        ];

        $response = $this->actingAs($user)->postJson('/api/v1/contacts', $invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'first_name',
            'last_name',
            'gender' => '性別の値が不正です',
            'tel' => '電話番号はハイフンなしの10～11桁で入力してください',
            'category_id' => '選択されたカテゴリーが存在しません',
            'tag_ids.0' => '選択されたタグが存在しません',
        ]);
    }

    /** @test */
    public function 編集にてレコードが更新され200が返る(): void
    {
        $user = User::first();
        $contact = Contact::first();
        $category = Category::first();
        $data = [
            'first_name' => '更新後',
            'last_name' => '編集',
            'gender' => 3,
            'email' => 'updated@example.com',
            'tel' => '08012345678',
            'address' => '北海道',
            'category_id' => $category->id,
            'detail' => '内容を更新',
        ];

        $response = $this->actingAs($user)->putJson("/api/v1/contacts/{$contact->id}", $data);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [

                'id' => $contact->id,
                'name' => '更新後 編集',
                'email' => 'updated@example.com',
            ],
        ]);
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'first_name' => '更新後',
        ]);
    }

    /** @test */
    public function 更新時存在しない_i_dで404が返る(): void
    {
        $user = User::first();

        $response = $this->actingAs($user)->putJson('/api/v1/contacts/999999', [
            'first_name' => 'テスト',
        ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function 更新時バリデーションエラー時は422が返る(): void
    {
        $user = User::first();
        $contact = Contact::first();
        $data = [
            'first_name' => '',
            'gender' => 9,
        ];

        $response = $this->actingAs($user)->putJson("/api/v1/contacts/{$contact->id}", $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['first_name', 'gender']);
    }

    /** @test */
    public function 削除にてレコードが削除され204が返る(): void
    {
        $user = User::first();
        $contact = Contact::first();

        $response = $this->actingAs($user)->deleteJson("/api/v1/contacts/{$contact->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
        ]);
    }

    /** @test */
    public function 削除時存在しない_i_dで404が返る(): void
    {
        $user = User::first();

        $response = $this->actingAs($user)->deleteJson('/api/v1/contacts/999999');

        $response->assertStatus(404);
    }
}
