    <?php

    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\Auth\AuthenticatedSessionController;
    use App\Http\Controllers\Admin\UserController;
    use App\Http\Controllers\KnowledgeController;
    use App\Http\Controllers\ChatController;
    use App\Http\Controllers\DashboardController;
    use App\Http\Controllers\ReferensiController;

    /*
    |--------------------------------------------------------------------------
    | LOGIN / LOGOUT
    |--------------------------------------------------------------------------
    */
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.post');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD & AUTH ROUTES
    |--------------------------------------------------------------------------
    */
    Route::middleware('supabase.auth')->group(function () {


        // Dashboard
       Route::get('/', function () {
   $user = session('supabase_user');

    return view('dashboard', compact('user'));
})->name('home');

Route::get('/dashboard', function () {
    $user = auth('supabase')->user(); // pakai guard
    return view('dashboard', compact('user'));
})->name('dashboard');


        /*
        |--------------------------------------------------------------------------
        | KNOWLEDGE MANAGEMENT
        |--------------------------------------------------------------------------
        */
        Route::prefix('knowledge')->group(function () {
            Route::post('/store', [KnowledgeController::class, 'store'])->name('knowledge.store');
            Route::post('/quick-add', [KnowledgeController::class, 'quickAdd'])->name('knowledge.quick-add');
            Route::delete('/{id}/destroy', [KnowledgeController::class, 'destroy'])->name('knowledge.destroy');
            Route::patch('/{id}/verify-data', [KnowledgeController::class, 'verifyData'])->name('knowledge.verify.data');
            Route::patch('/{id}/verify-answer', [KnowledgeController::class, 'verifyAnswer'])->name('knowledge.verify.answer');
        });

        /*
        |--------------------------------------------------------------------------
        | USER MANAGEMENT
        |--------------------------------------------------------------------------
        */
        Route::prefix('users')->group(function () {
            Route::post('/', [UserController::class, 'store'])->name('users.store');
            Route::put('/update-password', [UserController::class, 'updatePassword'])->name('users.updatePassword');
          Route::delete('/users/{id}', [UserController::class, 'destroy'])
    ->name('users.destroy');

        });

        /*
        |--------------------------------------------------------------------------
        | REFERENSI SUMBER MANAGEMENT
        |--------------------------------------------------------------------------
        */
        Route::prefix('referensi')->group(function () {
            Route::get('/', [ReferensiController::class, 'index'])->name('referensi.index');
            Route::post('/store', [ReferensiController::class, 'store'])->name('referensi.store');
            Route::delete('/{id}/destroy', [ReferensiController::class, 'destroy'])->name('referensi.destroy');
        });

        Route::get('/knowledge/unanswered', [KnowledgeController::class, 'unanswered']);

        /*
        |--------------------------------------------------------------------------
        | CHAT API
        |--------------------------------------------------------------------------
        */
        Route::post('/api/chat', [ChatController::class, 'handleChat']);

    });
