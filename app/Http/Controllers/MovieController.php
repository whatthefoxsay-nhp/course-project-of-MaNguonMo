<?php

namespace App\Http\Controllers;

use App\Support\DemoCatalog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MovieController extends Controller
{
    public function index(Request $request): View
    {
        $query = trim((string) $request->query('q'));
        $categoryId = $request->query('category');

        $movies = collect(DemoCatalog::movies())
            ->when($query !== '', fn ($items) => $items->filter(
                fn ($movie) => str_contains(mb_strtolower($movie->title), mb_strtolower($query))
            ))
            ->when($categoryId, fn ($items) => $items->filter(
                fn ($movie) => (string) $movie->category->id === (string) $categoryId
            ))
            ->values();

        return view('movies.index', [
            'movies' => $movies,
            'categories' => DemoCatalog::categories(),
            'query' => $query,
            'categoryId' => $categoryId,
        ]);
    }

    public function show(string $slug): View
    {
        $movie = DemoCatalog::movieBySlug($slug);

        abort_if($movie === null, 404);

        return view('movies.show', [
            'movie' => $movie,
            'showtimes' => DemoCatalog::showtimesForMovie($movie->id),
        ]);
    }
}
