<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('ja_JP');

        $categoryIds = Category::pluck('id')->toArray();
        $tags = Tag::all();

        for ($i = 0; $i < 20; $i++) {
            $tel = $faker->randomElement(['090', '080', '070', '03', '06']).$faker->numberBetween(1000, 9999).$faker->numberBetween(1000, 9999);

            $contact = Contact::create([
                'category_id' => $faker->randomElement($categoryIds),
                'first_name' => $faker->lastName(),
                'last_name' => $faker->firstName(),
                'gender' => $faker->randomElement([1, 2, 3]),
                'email' => $faker->safeEmail(),
                'tel' => $tel,
                'address' => $faker->address(),
                'building' => $faker->optional(0.7)->realText(15).'マンション',
                'detail' => $faker->realText(80),
            ]);

            $randomTags = $tags->random($faker->numberBetween(1, 3));

            $contact->tags()->attach($randomTags->pluck('id')->toArray());
        }
    }
}
