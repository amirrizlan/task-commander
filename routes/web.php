<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

// 1. HALAMAN UTAMA (Satu Fail, Dua Paparan Dinamik)
Route::get('/', function (Request $request) {
    // Jika Pengguna SUDAH Log Masuk -> Ambil Data Dashboard
    if (Auth::check()) {
        $userId = Auth::id();
        $query = DB::table('tasks')->where('user_id', $userId);

        if ($request->has('search') && $request->search != '') {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->has('status') && $request->status != '') {
            if ($request->status == 'completed') {
                $query->where('is_completed', true);
            } elseif ($request->status == 'pending') {
                $query->where('is_completed', false);
            }
        }

        $tasks = $query->orderBy('created_at', 'desc')->get();

        // Statistik Dashboard
        $totalTasks = DB::table('tasks')->where('user_id', $userId)->count();
        $completedTasks = DB::table('tasks')->where('user_id', $userId)->where('is_completed', true)->count();
        $pendingTasks = DB::table('tasks')->where('user_id', $userId)->where('is_completed', false)->count();
        $efficiency = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

        // Ambil Anime Quote
        try {
            $response = Http::timeout(2)->get('https://animechan.xyz/api/random');
            if ($response->successful()) {
                $quoteData = $response->json();
                $animeQuote = '"' . $quoteData['quote'] . '"';
                $animeCharacter = '- ' . $quoteData['character'] . ' (' . $quoteData['anime'] . ')';
            } else { throw new \Exception(); }
        } catch (\Exception $e) {
            $animeQuote = '"Ganbare! Selesaikan tugasan anda satu demi satu."';
            $animeCharacter = '- Saitama (One Punch Man)';
        }

        return view('todo', [
            'tasks' => $tasks,
            'animeQuote' => $animeQuote,
            'animeCharacter' => $animeCharacter,
            'stats' => [
                'total' => $totalTasks,
                'completed' => $completedTasks,
                'pending' => $pendingTasks,
                'efficiency' => $efficiency
            ]
        ]);
    }

    // Jika Pengguna BELUM Log Masuk -> Papar Halaman Todo yang memaparkan Borang Auth Mini
    return view('todo', ['tasks' => [], 'stats' => null, 'animeQuote' => '', 'animeCharacter' => '']);
});

// 2. PROSES LOGIN (Inline)
Route::post('/inline-login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();
        return redirect('/');
    }

    return back()->withErrors(['email' => 'Maklumat log masuk tidak sepadan.']);
});

// 3. PROSES REGISTER (Inline)
Route::post('/inline-register', function (Request $request) {
    $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
        'password' => ['required', 'confirmed', Rules\Password::defaults()],
    ]);

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
    ]);

    Auth::login($user);

    return redirect('/');
});

// 4. PROSES LOGOUT
Route::post('/inline-logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/');
})->name('inline-logout');


// --- GRUP AJAX TUGASAN (Hanya diproses jika sudah login) ---
Route::middleware(['auth'])->group(function () {
    
    // NAIK TARAF: Ditambah ruangan description & due_date (dateline)
    Route::post('/task', function (Request $request) {
        $request->validate([
            'title' => 'required|max:255',
            'category' => 'required',
            'priority' => 'required',
            'description' => 'nullable',
            'due_date' => 'nullable|date'
        ]);

        $id = DB::table('tasks')->insertGetId([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
            'category' => $request->category,
            'priority' => $request->priority,
            'is_completed' => false,
            'created_at' => now(), 
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true, 
            'task' => [
                'id' => $id, 
                'title' => $request->title, 
                'description' => $request->description,
                'due_date' => $request->due_date ? \Carbon\Carbon::parse($request->due_date)->format('d M Y') : null,
                'category' => $request->category, 
                'priority' => $request->priority, 
                'is_completed' => false
            ], 
            'stats' => getUpdatedStats(Auth::id())
        ]);
    });

    Route::patch('/task/{id}', function ($id) {
        $task = DB::table('tasks')->where('id', $id)->where('user_id', Auth::id())->first();
        $newStatus = !$task->is_completed;
        DB::table('tasks')->where('id', $id)->update(['is_completed' => $newStatus]);
        return response()->json(['success' => true, 'is_completed' => $newStatus, 'stats' => getUpdatedStats(Auth::id())]);
    });

    Route::delete('/task/{id}', function ($id) {
        DB::table('tasks')->where('id', $id)->where('user_id', Auth::id())->delete();
        return response()->json(['success' => true, 'stats' => getUpdatedStats(Auth::id())]);
    });
});

function getUpdatedStats($userId) {
    $total = DB::table('tasks')->where('user_id', $userId)->count();
    $completed = DB::table('tasks')->where('user_id', $userId)->where('is_completed', true)->count();
    $pending = DB::table('tasks')->where('user_id', $userId)->where('is_completed', false)->count();
    $efficiency = $total > 0 ? round(($completed / $total) * 100) : 0;
    return compact('total', 'completed', 'pending', 'efficiency');
}