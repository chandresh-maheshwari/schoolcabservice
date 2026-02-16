@extends('layouts.app')
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="shortcut icon" type="images/png"
        href="{{ asset('assets/images/fav-icon/Tahukar Magazine logo vv [Recovered].png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"
        integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js"
        integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous">
    </script>
    <link rel="stylesheet" href="{{ asset('assets/css/custom-css/admincss.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/default_css/main.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/cherrypik-custom-css/custom.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/login.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    @section('content')
        <div class="container">
            <form id="loginForm" class="form" action="{{ route('api.login') }}" method="POST">
                <div class="text text-center my-4">
                    <img src="{{ asset('images/for-schools.png') }}" alt="Logo" style="width: 100px; height: 100px;">
                </div>
                <p class="title" style="font-size: 20px;">Welcome back! Sign in to continue.</p>
                <!-- <p class="message">Sign in to access your account.</p> -->
                <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                <input type="hidden" name="source" value="admin" />

                <label>
                    <input id="email" type="email" placeholder="" class="input" name="email" required
                        autocomplete="email" autofocus>
                    <span>Email</span>
                    <div id="emailError" class="text-danger"></div>
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
                <p class="signin">Not a member? <a href="{{ route('register') }}">Sign Up</a></p>
                <p class="forgot-password" style="text-align: center;">
                    <a href="{{ route('forgot-password', ['source' => 'login']) }}" style="color: #2d336b;">Forgot
                        Password?</a>
                </p>
            </form>
        </div>
    @endsection

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('loginForm').addEventListener('submit', async function(event) {
                event.preventDefault();

                const email = document.getElementById('email').value;
                const password = document.getElementById('password').value;

                document.getElementById('emailError').textContent = '';
                document.getElementById('passwordError').textContent = '';

                try {
                    const response = await fetch('{{ route('api.login') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                        },
                        body: JSON.stringify({
                            email,
                            password,
                            source: 'admin'
                        })
                    });

                    const data = await response.json();

                    if (response.ok) {
                        localStorage.setItem('token', data.token);
                        localStorage.setItem('login_success', 'true');
                        window.location.href = '/admin/dashboard';
                    } else {
                        if (data.errors.email) {
                            document.getElementById('emailError').textContent = data.errors.email[0];
                        }
                        if (data.errors.password) {
                            document.getElementById('passwordError').textContent = data.errors.password[
                                0];
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
