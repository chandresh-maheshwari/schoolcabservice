@extends('layouts.app')
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="shortcut icon" type="images/png" href="{{ asset('assets/images/fav-icon/Tahukar Magazine logo vv [Recovered].png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="{{ asset('assets/css/custom-css/admincss.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/default_css/main.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/cherrypik-custom-css/custom.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/register.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- <style>
  
    </style> -->
</head>

<body>
    @section('content')
    <div class="container">
        <form class="form" id="registerForm">
        <div class="text text-center my-4">
            <img src="{{ asset('images/for-schools.png') }}" alt="Logo" style="width: 100px; height: 100px;">
        </div>
            <p class="title" style="font-size: 20px;">Sign Up!</p>
            <!-- <p class="message">Sign up now and get full access to our app.</p> -->
            <div class="flex">
                <label>
                    <input required="" placeholder="" type="text" class="input" name="first_name" id="first_name">
                    <span>First Name</span>
                </label>
                <label>
                    <input required="" placeholder="" type="text" class="input" name="last_name" id="last_name">
                    <span>Last Name</span>
                </label>
            </div>
            <label>
                <input required="" placeholder="" type="email" class="input" name="email" id="email">
                <span>Email</span>
            </label>
            <label>
                <input required="" placeholder="" type="password" class="input" name="password" id="password">
                <span>Password <small style="font-size: 88%; color: #888;">(8-15 characters, include at least one number and one special character)</small></span>
                <i id="togglePassword" class="fas fa-eye" style="cursor: pointer; position: absolute; right: 14px; top: 20px;"></i>
                <small id="passwordHint" style="color: #888; display: none;">Password must be 8-15 characters, include at least one number and one special character.</small>
                <span id="passwordError" style="color: red; display: none;"></span>
            </label>
            <label>
                <input required="" placeholder="" type="password" class="input" name="confirm_password" id="confirm_password">
                <span>Confirm password</span>
                <i id="toggleConfirmPassword" class="fas fa-eye" style="cursor: pointer; position: absolute; right: 14px; top: 20px;"></i>
            </label>
            <button type="submit" class="submit">Submit</button>
            <p class="signin">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
        </form>
    </div>
    @endsection

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('registerForm').addEventListener('submit', async function(event) {
            event.preventDefault();

            const first_name = document.getElementById('first_name').value;
            const last_name = document.getElementById('last_name').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Passwords do not match!',
                });
                return;
            }

            try {
                const response = await fetch('/api/register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ first_name, last_name, email, password })
                });

                const data = await response.json();

                if (response.ok) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Registration successful!',
                        text: 'You can now log in.',
                        confirmButtonColor: '#2D336B',
                        customClass: {
                            popup: 'swal2-popup'
                        }
                    }).then(() => {
                        window.location.href = '/admin/login'; 
                    });
                } else {
                    let errorMessages = '';
                    if (data.errors) {
                        errorMessages = Object.values(data.errors).flat().join('\n');
                    } else {
                        errorMessages = 'Registration failed. Please try again.';
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Registration failed',
                        text: errorMessages,
                    });
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });


        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const passwordHint = document.getElementById('passwordHint');
        const passwordError = document.getElementById('passwordError');

        function validatePasswordFormat(password) {
            // 8-15 chars, at least one number, at least one special char
            return /^(?=.*[0-9])(?=.*[\W_]).{8,15}$/.test(password);
        }

        passwordInput.addEventListener('input', function() {
            if (passwordInput.value.length === 0) {
                passwordError.style.display = 'none';
                passwordInput.style.borderColor = '';
                passwordHint.style.display = 'none';
                return;
            }
            if (validatePasswordFormat(passwordInput.value)) {
                passwordError.style.display = 'none';
                passwordInput.style.borderColor = 'green';
                passwordHint.style.display = 'none';
            } else {
                // passwordError.textContent = 'Password format is invalid.';
                passwordError.style.display = 'block';
                passwordInput.style.borderColor = 'red';
                passwordHint.style.display = 'block';
                passwordHint.style.color = 'red';
            }
        });

        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const confirmPasswordInput = document.getElementById('confirm_password');

        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPasswordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
    });
    </script>
</body>

</html>
