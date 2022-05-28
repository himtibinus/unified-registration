<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <script type="module" src="/pwabuilder-sw-register.js"></script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="shortcut icon" href="{{ url('./assets/himti-mono.png') }}" type="image/x-icon">
    <!-- Scripts -->
    <script src="{{ url('/js/app.js') }}"></script>
    <script src="{{ url('/js/cssua.min.js') }}"></script>
    <script>
        function adjustDate(element) {
            // Localize date and time
            var eventDate = document.getElementById(element);
            var date = new Date(Date.parse(eventDate.textContent + "Z"));

            eventDate.textContent = date.toLocaleString();
        }
    </script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">

    <!-- Styles -->
    <link href="{{ url('/css/app.css') }}" rel="stylesheet">
</head>

<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-dark shadow-sm fixed-top w-100">
            <div class="container">
                <a class="navbar-brand fw-bold me-0" href="{{ url('/') }}">
                    <div class="d-flex justify-content-between align-items-center">
                        <img src="{{ url('./assets/himti-mono.png') }}" width="45px" alt="">
                        <p class="d-none d-md-block app-title mb-0 ms-2 fw-bold">
                            {{ config('app.name', 'Laravel') }}</p>
                    </div>
                </a>
                <p class="app-mobile-title d-md-none app-title mb-0 fw-bold text-white">
                    HIMTI Registration</p>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse flex-row-reverse" id="navbarSupportedContent">
                    <ul class="navbar-nav">
                        @guest
                            @if (Route::has('register'))
                                <li class="nav-item me-4">
                                    <a class="nav-link fw-bold"
                                        href="{{ route('register') }}">{{ __('Register') }}</a>
                                </li>
                            @endif
                            @if (Route::has('login'))
                                <li class="nav-item nav-item-custom d-flex">
                                    <a class="btn btn-light d-flex align-items-center gap-1 lh-normal"
                                        href="{{ route('login') }}"> <i class="bi bi-box-arrow-in-left"></i>
                                        <span>Login</span>
                                    </a>

                                    <form id="logout-form" action="https://testing.himti.or.id/logout" method="POST"
                                        class="d-none">
                                        <input type="hidden" name="_token" value="p8QEcoj1U8jpS7kLeX8R87Y12LYjPQLIXChCWtgQ">
                                    </form>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle " href="#" role="button"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    <i class="bi bi-person-circle me-1"></i>
                                    <span>{{ Auth::user()->name }}</span>
                                </a>

                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="/profile">
                                        <i class="bi bi-person-circle"></i> Profile
                                    </a>
                                    <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault();
                                                             document.getElementById('logout-form').submit();">
                                        <i class="bi bi-box-arrow-right"></i>
                                        {{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                        class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main>
            @yield('content')
        </main>
        <footer class="container">
            <hr>
            <p class="text-center">© 2021–2022 Research and Development, <a href="https://himti.or.id"
                    class="backlink-himti text-decoration-none">HIMTI BINUS University</a>.</p>
        </footer>
    </div>
</body>

</html>
