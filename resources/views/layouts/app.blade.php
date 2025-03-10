<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'OpenSign') }} - @yield('title', 'Document Signing Platform')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Theme CSS -->
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet">
    
    <!-- Additional Styles -->
    @stack('styles')
</head>
<body class="font-sans antialiased h-full bg-gray-50 text-gray-900">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        @include('includes.header')
        
        <!-- Notifications -->
        @if(View::exists('includes.notifications'))
            @include('includes.notifications')
        @endif
        
        <!-- Main Content -->
        <main class="flex-grow container mx-auto px-4 py-6 sm:px-6 lg:px-8">
            @yield('content')
        </main>
        
        <!-- Footer -->
        @if(View::exists('includes.footer'))
            @include('includes.footer')
        @endif
    </div>
    
    <!-- Additional Scripts -->
    @stack('scripts')
</body>
</html>
