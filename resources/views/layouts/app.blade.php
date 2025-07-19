<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'My Laravel App')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Bulma CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/bulma.min.css') }}">
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <style>
        .navbar.is-primary {
            background-color: #1B3FAB;
            color: #fff;
        }

        .has-text-primary {
            color: #1B3FAB !important;
        }

        .navbar.is-primary .navbar-end .navbar-link.is-active,
        .navbar.is-primary .navbar-end .navbar-link:focus,
        .navbar.is-primary .navbar-end .navbar-link:hover,
        .navbar.is-primary .navbar-end>a.navbar-item.is-active,
        .navbar.is-primary .navbar-end>a.navbar-item:focus,
        .navbar.is-primary .navbar-end>a.navbar-item:hover,
        .navbar.is-primary .navbar-start .navbar-link.is-active,
        .navbar.is-primary .navbar-start .navbar-link:focus,
        .navbar.is-primary .navbar-start .navbar-link:hover,
        .navbar.is-primary .navbar-start>a.navbar-item.is-active,
        .navbar.is-primary .navbar-start>a.navbar-item:focus,
        .navbar.is-primary .navbar-start>a.navbar-item:hover {
            background-color: #234096;
            color: #fff;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const burger = document.querySelector('.navbar-burger');
            const menu = document.querySelector('.navbar-menu');

            if (burger && menu) {
                burger.addEventListener('click', () => {
                    menu.classList.toggle('is-active');
                    burger.classList.toggle('is-active');
                });
            }
        });
    </script>

</head>

<body>
    <!-- Navbar -->
    <nav class="navbar is-primary" role="navigation" aria-label="main navigation">
        <div class="container is-max-desktop">
            <div class="navbar-brand">
                <a class="navbar-item" href="{{ url('/') }}">
                    <strong>MyApp</strong>
                </a>

                <a role="button" class="navbar-burger" aria-label="menu" aria-expanded="false">
                    <span aria-hidden="true"></span>
                    <span aria-hidden="true"></span>
                    <span aria-hidden="true"></span>
                </a>
            </div>

            <div class="navbar-menu">
                <div class="navbar-start">
                    <a class="navbar-item" href="{{ url('/') }}">Home</a>
                    <a class="navbar-item" href="{{ url('/about-us') }}">About</a>
                    <a class="navbar-item" href="{{ url('/feedback') }}">Feedback</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Centered Content -->
    <section class="section is-flex is-justify-content-center is-align-items-center" style="height: 80vh;">
        <div class="container has-text-centered  is-max-desktop">
            @yield('content')
        </div>
    </section>

    <!-- Footer -->
    <footer class="">
        <div class="content has-text-centered">
            <p>&copy; {{ date('Y') }} My Laravel App. All rights reserved.</p>
        </div>
    </footer>

</body>

</html>
