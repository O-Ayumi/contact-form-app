<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ContactSeeder;
use Database\Seeders\TagSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactControllerTest extends TestCase
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
    public function お問い合わせフォーム入力ページが正常に表示され、カテゴリとタグがビュー変数として渡される(): void
    {
        $category = Category::first();
        $tag = Tag::first();

        $response = $this->get(route('contact.index'));

        $response->assertStatus(200);
        $response->assertViewHas('categories');
        $response->assertViewHas('tags');
        $response->assertSee($category->content);
        $response->assertSee($tag->name);
    }

    /** @test */
    public function サンクスページが正常に表示され中間テーブルに記録される(): void
    {
        $categoryId = Category::first()->id;
        $tags = Tag::take(2)->get();
        $data = [
            'category_id' => $categoryId,
            'first_name' => 'テスト',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'test_target@example.com',
            'tel' => '09012345678',
            'address' => '東京都',
            'detail' => '詳細内容',
            'tag_ids' => [$tags[0]->id, $tags[1]->id],
        ];

        $response = $this->post(route('contact.store'), $data);

        $response->assertRedirect(route('contact.thanks'));
        $this->assertdatabaseHas('contacts', [
            'email' => 'test_target@example.com',
            'category_id' => $categoryId,
        ]);

        $response2 = $this->get(route('contact.thanks'));
        $response2->assertStatus(200);

        $latestContact = Contact::latest('id')->first();

        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $latestContact->id,
            'tag_id' => $tags[0]->id,
        ]);
        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $latestContact->id,
            'tag_id' => $tags[1]->id,
        ]);
    }

    /** @test */
    public function お問い合わせ内容入力後お問い合わせフォーム確認ページに遷移する(): void
    {
        $categoryId = Category::first()->id;
        $categoryContent = Category::first()->content;
        $data = [
            'category_id' => $categoryId,
            'first_name' => 'テスト',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'test_target@example.com',
            'tel' => '09012345678',
            'address' => '東京都',
            'detail' => '詳細内容',
        ];

        $response = $this->post(route('contact.confirm'), $data);

        $response->assertStatus(200);

        $response->assertSee('テスト');
        $response->assertSee('太郎');
        $response->assertSee('test_target@example.com');
        $response->assertSee('09012345678');
        $response->assertSee('東京都');
        $response->assertSee('詳細内容');
        $response->assertSee($categoryContent);
    }

    /** @test */
    public function 必須項目が空の場合はバリデーションエラーになる(): void
    {
        $response = $this->post(route('contact.confirm'), []);

        $response->assertRedirect(route('contact.index'));
        $response->assertSessionHasErrors([
            'first_name' => '姓を入力してください',
            'last_name' => '名を入力してください',
            'gender' => '性別を選択してください',
            'email' => 'メールアドレスを入力してください',
            'tel' => '電話番号を入力してください',
            'address' => '住所を入力してください',
            'category_id' => 'お問い合わせの種類を選択してください',
            'detail' => 'お問い合わせ内容を入力してください',
        ]);
    }

    /** @test */
    public function 形式エラーや文字数制限に引っ掛かる場合はバリデーションエラーになる(): void
    {
        $categoryId = Category::first()->id;
        $invalidData = [
            'category_id' => $categoryId,
            'first_name' => 'テスト',
            'last_name' => '太郎',
            'address' => '東京都',
            'gender' => 5,
            'email' => 'invalid-email-format',
            'tel' => '090-1234-5678',
            'detail' => str_repeat('あ', 121),
        ];

        $response = $this->post(route('contact.confirm'), $invalidData);

        $response->assertRedirect(route('contact.index'));

        $response->assertSessionHasErrors([
            'gender',
            'email' => 'メールアドレスはメール形式で入力してください',
            'tel',
            'detail' => 'お問い合わせ内容は120文字以内で入力してください',
        ]);
    }
}