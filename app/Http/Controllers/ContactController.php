<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactStoreRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::all();
        $tags = Tag::all();

        return view('contact.index', compact('categories', 'tags'));
    }

    public function confirm(ContactStoreRequest $request)
    {
        $validated = $request->validated();

        $category = Category::findOrFail($validated['category_id']);

        $tags = Tag::whereIn('id', $validated['tag_ids'] ?? [])->get();

        $request->flash();

        return view('contact.confirm', compact('validated', 'category', 'tags'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ContactStoreRequest $request)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $contact = Contact::create($validated);

            $contact->tags()->sync($validated['tag_ids'] ?? []);
        });

        return redirect()->route('contacts.thanks');
    }

    public function thanks()
    {
        return view('contact.thanks');
    }
}
