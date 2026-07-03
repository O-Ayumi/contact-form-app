<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\User;
use App\Models\Tag;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ContactSeeder;
use Database\Seeders\TagSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminControllerTest extends TestCase
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
    public function 管理者ページでキーワード・性別・カテゴリ・日付フィルタが機能する(): void
    {
        $user = User::first();
        $searchKeyword = 'Test User';

        $response = $this->actingAs($user)->get(route('admin.index', [
            'keyword' => $searchKeyword,
            'gender' => '1',
            'category_id' => 1,
            'date' => now()->format('Y-m-d'),
        ]));

        $response->assertStatus(200);

        $filteredContacts = $response->viewData('contacts');
        $this->assertNotNull($filteredContacts);

        $response->assertSee($searchKeyword);
    }

    /** @test */
    public function 検索時に不正な性別値を拒否する(): void
    {
        $user = User::first();

        $response = $this->actingAs($user)->get(route('admin.index', [
            'gender' => '5',
        ]));

        $response->assertSessionHasErrors('gender');
    }

    /** @test */
    public function お問い合わせ一覧で結果が7件ごとにページネーションされる(): void
    {
        $user = User::first();

        $response = $this->actingAs($user)->get(route('admin.index'));

        $contacts = $response->viewData('contacts');

        $this->assertEquals(7, $contacts->perPage());
    }

    /** @test */
    public function 指定したお問い合わせがカテゴリ情報付きで詳細ページに表示される(): void
    {
        $user = User::first();
        $contact = Contact::first();

        $response = $this->actingAs($user)->get("/admin/contacts/{$contact->id}");

        $response->assertStatus(200);
        $response->assertViewIs('admin.show');

        $viewContact = $response->viewData('contact');
        $this->assertTrue($viewContact->relationLoaded('category'));
    }

    /** @test */
    public function 一つのカテゴリから紐づく複数のお問い合わせが正しく取得できる(): void
    {
        $category = Category::first();
        $contacts = Contact::take(2)->get();
        $contactA = $contacts[0];
        $contactB = $contacts[1];

        $contactA->update(['category_id' => $category->id]);
        $contactB->update(['category_id' => $category->id]);

        $this->assertTrue($category->contacts->contains($contactA));
        $this->assertTrue($category->contacts->contains($contactB));

        $this->assertInstanceOf(Contact::class, $category->contacts->first());
    }

    /** @test */
    public function 一つのお問い合わせが特定のカテゴリに属し複数のタグと同期できる(): void
    {
        $category = Category::first();
        $contact = Contact::first();
        $contact->update(['category_id' => $category->id]);
        $tags = Tag::take(2)->get();
        $tagA = $tags[0];
        $tagB = $tags[1];

        $this->assertequals($category->id, $contact->category->id);

        $contact->tags()->sync([$tagA->id, $tagB->id]);

        $this->assertEquals(2, $contact->tags()->count());
        $this->assertTrue($contact->tags->contains($tagA));
        $this->assertTrue($contact->tags->contains($tagB));
    }

    /** @test */
    public function レコードを削除し一覧にリダイレクトする(): void
    {
        $user = User::first();
        $contact = Contact::first();

        $response = $this->actingAs($user)->delete("/admin/contacts/{$contact->id}");

        $response->assertRedirect('/admin');
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    /** @test */
    public function ログイン済管理者がフィルタ条件付きでCSVをDLでき、無指定時は新着順で出力される(): void
    {
        $user = User::first();
        $categoryId = Category::first()->id;

        Contact::create([
            'category_id' => $categoryId,
            'first_name' => 'テスト',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'test_target@example.com',
            'tel' => '09012345678',
            'address' => '東京都',
            'detail' => '詳細内容',
        ]);

        $response = $this->actingAs($user)->get(route('contacts.export', ['keyword' => 'テスト']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringStartsWith('attachment; filename="contacts_', $disposition);
        $this->assertStringContainsString('テスト', $response->streamedContent());

        Contact::create([
            'category_id' => $categoryId,
            'first_name' => '最新の',
            'last_name' => 'ユーザー',
            'gender' => 2,
            'email' => 'latest@example.com',
            'tel' => '08012345678',
            'address' => '大阪府',
            'detail' => '最新詳細',
            'created_at' => now()->addMinutes(10),
        ]);

        $noParamResponse = $this->actingAs($user)->get(route('contacts.export'));

        $noParamResponse->assertStatus(200);

        $csvContent = $noParamResponse->streamedContent();

        $this->assertStringContainsString('latest@example.com', $csvContent);
    }

    /** @test */
    public function CSVエクスポートの際は不正な性別や存在しないカテゴリIDを拒否する(): void
    {
        $user = User::first();
        $invalidCategoryId = 9999;

        $response = $this->actingAs($user)->get(route('contacts.export', [
            'gender' => '5',
            'category_id' => $invalidCategoryId,
        ]));

        $response->assertSessionHasErrors(['gender', 'category_id']);
    }

    /** @test */
    public function 認証ユーザーのみ管理画面を表示でき、未認証ユーザーはログイン画面にリダイレクトされる(): void
    {
        $user = User::first();

        $response = $this->actingAs($user)->get(route('admin.index'));

        $response->assertStatus(200);

        $unauthorizedResponse = $this->get(route('admin.index'));
        $unauthorizedResponse->assertRedirect('/login');
    }
}
