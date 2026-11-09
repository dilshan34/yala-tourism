<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In - Tourism Business Manager</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center p-6 bg-gradient-to-br from-slate-100 via-emerald-50/20 to-teal-50/30">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-slate-200/80 p-8 space-y-6">
        <!-- Logo & Header -->
        <div class="text-center space-y-2">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center text-white shadow-lg shadow-emerald-500/25">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Wild Pulse & Yala Bliss</h2>
            <p class="text-xs text-slate-500">Multi-Property Tourism Business Management System</p>
        </div>

        <!-- Validation Errors -->
        @if ($errors->any())
            <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Login Form -->
        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Email Address</label>
                <input id="email" type="email" name="email" value="{{ old('email', 'admin@tourism.com') }}" required autofocus
                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 transition-colors shadow-xs" />
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Password</label>
                <input id="password" type="password" name="password" value="password" required
                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 transition-colors shadow-xs" />
            </div>

            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center gap-2 cursor-pointer text-slate-600">
                    <input type="checkbox" name="remember" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                    <span>Remember me</span>
                </label>
            </div>

            <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm shadow-md shadow-emerald-600/20 transition-all hover:shadow-lg hover:shadow-emerald-600/30 active:scale-[0.99]">
                Sign In to Platform
            </button>
        </form>

        <!-- Quick Demo Switcher -->
        <div class="pt-4 border-t border-slate-100 text-center">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Quick Fill Demo Roles</span>
            <div class="grid grid-cols-3 gap-2 mt-2">
                <button type="button" onclick="document.getElementById('email').value='admin@tourism.com'; document.getElementById('password').value='password';" class="px-2 py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                    Admin
                </button>
                <button type="button" onclick="document.getElementById('email').value='manager@tourism.com'; document.getElementById('password').value='password';" class="px-2 py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                    Manager
                </button>
                <button type="button" onclick="document.getElementById('email').value='staff@tourism.com'; document.getElementById('password').value='password';" class="px-2 py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                    Staff
                </button>
            </div>
        </div>
    </div>
</body>
</html>
