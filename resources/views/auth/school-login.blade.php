@extends('layouts.app')
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Login</title>
    <link rel="shortcut icon" type="images/png"
        href="{{ $schoolBranding['favicon_url'] ?? asset('assets/images/fav-icon/Tahukar Magazine logo vv [Recovered].png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('assets/css/custom-css/admincss.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/default_css/main.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/cherrypik-custom-css/custom.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/login.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --school-primary: {{ $schoolBranding['primary_color'] ?? '#2D336B' }};
            --school-secondary: {{ $schoolBranding['secondary_color'] ?? '#7886c7' }};
        }

        body {
            background: linear-gradient(to right, var(--school-primary), var(--school-secondary)) !important;
        }

        .title,
        .signin a {
            color: var(--school-primary) !important;
        }

        .title::before,
        .title::after {
            background-color: var(--school-primary) !important;
        }

        .submit {
            background-color: var(--school-primary) !important;
        }

        .submit:hover {
            background-color: var(--school-secondary) !important;
        }
    </style>
</head>

<body>
    @section('content')
        <div class="container">
            <form id="loginForm" class="form" action="{{ route('school.slug.login', ['schoolSlug' => $schoolSlug]) }}"
                method="POST">
                <div class="text text-center my-4">
                    <img src="{{ $schoolBranding['logo_url'] ?? asset('images/for-schools.png') }}" alt="Logo" style="width: 100px; height: 100px;">
                </div>
                <p class="title" style="font-size: 20px;">{{ ($schoolBranding['header_title'] ?? $schoolName) ? ($schoolBranding['header_title'] ?? $schoolName) . ' Admin' : 'School Admin' }}
                    Login</p>
                <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                <input type="hidden" name="source" value="admin" />
                <input type="hidden" name="school_slug" value="{{ $schoolSlug }}" />

                <label>
                    <input id="login" type="text" placeholder="" class="input" name="login" required
                        autocomplete="username" autofocus>
                    <span>Username</span>
                    <div id="loginError" class="text-danger"></div>
                </label>
                <label>
                    <input id="password" type="password" placeholder="" class="input" name="password" required
                        autocomplete="current-password">
                    <span>Password</span>
                    <i id="togglePassword" class="fas fa-eye"
                        style="cursor: pointer; position: absolute; right: 14px; top: 20px;"></i>
                    <div id="passwordError" class="text-danger"></div>
                </label>
                <button type="submit" class="submit">Submit</button>
                <p class="signin" style="text-align: center; margin-top: 12px;">
                    {{-- <a href="{{ route('login') }}">Admin Login</a> --}}
                </p>
            </form>
        </div>
    @endsection

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('loginForm').addEventListener('submit', async function(event) {
                event.preventDefault();

                const login = document.getElementById('login').value;
                const password = document.getElementById('password').value;

                document.getElementById('loginError').textContent = '';
                document.getElementById('passwordError').textContent = '';

                try {
                    const response = await fetch('{{ route('school.slug.login', ['schoolSlug' => $schoolSlug]) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                        },
                        body: JSON.stringify({
                            login,
                            password,
                            source: 'admin',
                            school_slug: '{{ $schoolSlug }}'
                        })
                    });

                    const data = await response.json();

                    if (response.ok) {
                        localStorage.setItem('token', data.token);
                        localStorage.setItem('login_success', 'true');
                        window.location.href = data.redirect_url || '/admin/dashboard';

                    } else {
                        if (data.errors && data.errors.login) {
                            document.getElementById('loginError').textContent = data.errors.login[0];
                        }
                        if (data.errors && data.errors.email) {
                            document.getElementById('loginError').textContent = data.errors.email[0];
                        }
                        if (data.errors && data.errors.password) {
                            document.getElementById('passwordError').textContent = data.errors.password[0];
                        }
                        if (data.errors && data.errors.school_slug) {
                            document.getElementById('loginError').textContent = data.errors.school_slug[0];
                        }
                    }
                } catch (error) {
                    console.error('Error:', error);
                }
            });

            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');

            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.classList.toggle('fa-eye-slash');
            });
        });
    </script>
</body>

</html>
