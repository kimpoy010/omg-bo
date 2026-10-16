<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in — OMG Back Office</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#0b1220] min-h-screen flex items-center justify-center">
    <div class="w-full max-w-sm bg-white rounded-xl shadow-lg p-8">
        <h1 class="text-xl font-bold text-gray-800 mb-1">OMG Back Office</h1>
        <p class="text-sm text-gray-500 mb-6">Webmaster and merchant access only.</p>

        @if ($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-800 text-sm px-4 py-3 rounded-lg">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.submit') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Username</label>
                <input type="text" name="username" value="{{ old('username') }}" required autofocus
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Password</label>
                <input type="password" name="password" required
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            </div>
            <label class="flex items-center gap-2 text-xs text-gray-600">
                <input type="checkbox" name="remember"> Remember me
            </label>
            <button type="submit"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-2.5 rounded-lg transition-colors">
                Sign in
            </button>
        </form>
    </div>
@include('partials.password-toggle')
</body>
</html>
