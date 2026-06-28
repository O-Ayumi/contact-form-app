<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['keyword', 'email', 'gender', 'category_id', 'date']);

        $contacts = Contact::with(['category', 'tags'])->search($filters)->latest()->paginate(7)->withQueryString();

        $categories = Category::all();

        $tags = Tag::all();

        return view('admin.index', compact('contacts', 'categories', 'tags'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Contact $contact)
    {
        $contact->load(['category', 'tags']);

        return view('admin.show', compact('contact'));
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();

        return redirect()->route('admin.index')->with('success', 'お問い合わせを削除しました。');
    }
}
