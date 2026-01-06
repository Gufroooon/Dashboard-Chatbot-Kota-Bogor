<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f0f2f5; }
        .login-card { background: #ffffff; border-radius: 20px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05); }
        .avatar-circle { width: 100px; height: 100px; background: #f8fafc; border: 6px solid #ffffff; box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06), 0 4px 6px -1px rgba(0, 0, 0, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-top: -50px; }
        .input-field { transition: all 0.3s ease; border: 1px solid #e2e8f0; }
        .input-field:focus { border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); outline: none; }
        .btn-primary { background: #007bff; transition: all 0.2s ease; box-shadow: 0 4px 14px 0 rgba(0, 123, 255, 0.39); }
        .btn-primary:hover { background: #0069d9; transform: translateY(-1px); }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-4">

    <div class="w-full max-w-[380px]">
        
        <div class="login-card p-10 mt-12">
            <div class="flex justify-center mb-6">
                <div class="avatar-circle">
                    <svg class="w-12 h-12 text-gray-300" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                </div>
            </div>

            @if ($errors->any())
                <div class="mb-4 p-3 bg-red-50 rounded-lg border border-red-100">
                    <p class="text-xs text-red-600 text-center font-medium">{{ $errors->first() }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
                @csrf

                <div class="relative">
                    <input
                        type="text"
                        name="username" 
                        value="{{ old('username') }}"
                        placeholder="   Username"
                        required
                        autofocus
                        class="input-field w-full px-5 py-3.5 rounded-xl text-sm text-gray-700 bg-gray-50/50 placeholder-gray-400"
                    >
                </div>

                <div class="relative">
                    <input
                        type="password"
                        name="password"
                        placeholder="Password"
                        required
                        autocomplete="current-password"
                        class="input-field w-full px-5 py-3.5 rounded-xl text-sm text-gray-700 bg-gray-50/50 placeholder-gray-400"
                    >
                </div>

                <div class="pt-4">
                    <button type="submit" class="btn-primary w-full text-white py-3.5 rounded-full text-sm font-bold tracking-widest uppercase">
                        LOGIN
                    </button>
                </div>
            </form>
        </div>

        <p class="mt-10 text-center text-[10px] leading-relaxed text-gray-400 tracking-[0.1em] uppercase px-4">
            © {{ date('Y') }} Copyright Bidang Statistik Sektoral <br> Diskominfo Kota Bogor
        </p>

    </div>

</body>
</html>