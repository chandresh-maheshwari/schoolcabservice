@extends('layouts.app')
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="shortcut icon" type="images/png" href="{{ asset('assets/images/fav-icon/download (4).png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/custom-css/admincss.css">
    <link rel="stylesheet" href="assets/css/default_css/main.css">
    <link rel="stylesheet" href="assets/css/cherrypik-custom-css/custom.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="{{ asset('css/all-custom.css') }}" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* #emailSection {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .input {
            margin-bottom: 10px;
            width: 100%;
            max-width: 300px;
        }

        .submit {
            width: 100%;
            max-width: 300px;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            box-sizing: border-box;
        } */
    </style>
</head>

<body>
    @section('content')
        <div class="container">
            <form id="forgotPasswordForm" class="form" method="POST">
                <div class="text my-4 text-center">
                    <img src="{{ asset('images/for-schools.png') }}" alt="Logo"
                        style="width: 150px; height: 150px;">
                </div>
                <p class="title" style="font-size: 20px;">Forgot Password</p>
                <input type="hidden" name="_token" value="{{ csrf_token() }}" />

                <div id="emailSection">
                    <label>
                        <input id="email" type="email" placeholder="" class="input" name="email" required
                            autocomplete="email" autofocus>
                        <span>Email</span>
                    </label>
                    <div id="emailError" class="text-danger"></div>

                    <div class="forgot-button-section row mt-3">
                        <div class="col-sm-8">

                            <button type="submit" class="submit submitforgot">Send OTP</button>
                            <a href="{{ route('login') }}" class="btn btn-secondary mb-30 cancelforgot">Cancel</a>
                        </div>
                        <div class="col-sm-4">

                        </div>
                        {{-- <a href="{{ url('login') }}" class="btn btn-secondary mb-30">Cancel</a> --}}
                    </div>
                </div>

                <div id="otpSection" style="display: none;">
                    <label>
                        <input id="otp" type="text" placeholder="" class="input" name="otp">
                        <span>OTP</span>
                        <div id="otpError" class="text-danger"></div>
                    </label>
                    <button type="button" id="verifyOtpButton" class="submit">Verify OTP</button>
                </div>

                <div id="passwordSection" style="display: none;">
                    <label style="display: flex; flex-direction: column; align-items: center;">
                        <input id="newPassword" type="password" placeholder="" class="input" name="newPassword">
                        <span>New Password <small style="font-size: 88%; color: #888;">(8-15 characters, include at least one number and one special character)</small></span>
                        <small id="passwordHint" style="color: #888; display: none;">(8-15 characters, include at least one number and one special character)</small>
                    </label>
                    <label style="display: flex; flex-direction: column; align-items: center;">
                        <input id="confirmPassword" type="password" placeholder="" class="input"
                            name="newPassword_confirmation">
                        <span>Confirm Password</span>
                    </label>
                    <button type="button" id="resetPasswordButton" class="submit">Reset Password</button>
                </div>
            </form>
        </div>
    @endsection

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const emailSection = document.getElementById('emailSection');
            const otpSection = document.getElementById('otpSection');
            const passwordSection = document.getElementById('passwordSection');

            // Remove required attribute from hidden fields initially
            document.getElementById('otp').removeAttribute('required');
            document.getElementById('newPassword').removeAttribute('required');
            document.getElementById('confirmPassword').removeAttribute('required');

            document.getElementById('forgotPasswordForm').addEventListener('submit', async function(event) {
                event.preventDefault();

                const email = document.getElementById('email').value;
                document.getElementById('emailError').textContent = '';

                try {
                    Swal.fire({
                        title: 'Sending OTP...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                            document.body.classList.remove('swal2-height-auto');
                        },
                        backdrop: true,
                        position: 'center'
                    });

                    const response = await fetch('{{ route('api.sendOtp') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                        },
                        body: JSON.stringify({
                            email
                        })
                    });

                    const data = await response.json();
                    Swal.close();

                    if (response.ok) {
                        document.body.classList.remove('swal2-height-auto');
                        Swal.fire({
                            title: 'Success',
                            text: 'OTP sent Successfully!',
                            icon: 'success',
                            didOpen: () => {
                                document.body.classList.remove('swal2-height-auto');
                            }
                        });
                        emailSection.style.display = 'none';
                        otpSection.style.display = 'block';
                        document.getElementById('otp').setAttribute('required', true);
                    } else {
                        if (data.errors.email) {
                            document.getElementById('emailError').textContent = data.errors.email[0];
                        }
                    }
                } catch (error) {
                    Swal.close();
                    console.error('Error:', error);
                }
            });

            document.getElementById('verifyOtpButton').addEventListener('click', async function() {
                const email = document.getElementById('email').value;
                const otp = document.getElementById('otp').value;
                document.getElementById('otpError').textContent = '';

                try {
                    Swal.fire({
                        title: 'Verifying OTP...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                            document.body.classList.remove('swal2-height-auto');
                        }
                    });

                    const response = await fetch('{{ route('api.verifyOtp') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                        },
                        body: JSON.stringify({
                            email,
                            otp
                        })
                    });

                    const data = await response.json();
                    Swal.close();

                    if (response.ok) {
                        Swal.fire({
                            title: 'Success',
                            text: 'OTP verified Successfully!',
                            icon: 'success',
                            didOpen: () => {
                                document.body.classList.remove('swal2-height-auto');
                            }
                        });
                        otpSection.style.display = 'none';
                        passwordSection.style.display = 'block';
                        document.getElementById('newPassword').setAttribute('required', true);
                        document.getElementById('confirmPassword').setAttribute('required', true);
                    } else {
                        if (data.errors.otp) {
                            document.getElementById('otpError').textContent = data.errors.otp[0];
                        }
                    }
                } catch (error) {
                    Swal.close();
                    console.error('Error:', error);
                }
            });

            document.getElementById('resetPasswordButton').addEventListener('click', async function() {
                const email = document.getElementById('email').value;
                const newPassword = document.getElementById('newPassword').value;
                const confirmPassword = document.getElementById('confirmPassword').value;
                const urlParams = new URLSearchParams(window.location.search);
                const source = urlParams.get('source') ||
                    'login'; // Default to 'login' if not specified

                try {
                    Swal.fire({
                        title: 'Resetting Password...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                            document.body.classList.remove('swal2-height-auto');
                        }
                    });

                    const response = await fetch('{{ route('api.resetnewPassword') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                        },
                        body: JSON.stringify({
                            email,
                            newPassword,
                            newPassword_confirmation: confirmPassword
                        })
                    });

                    const text = await response.text();
                    Swal.close();

                    try {
                        const data = JSON.parse(text);

                        if (response.ok) {
                            Swal.fire({
                                title: 'Success',
                                text: 'Password reset successful!',
                                icon: 'success',
                                didOpen: () => {
                                    document.body.classList.remove('swal2-height-auto');
                                }
                            }).then(() => {
                                if (source === 'front') {
                                    window.location.href = '/login';
                                } else {
                                    window.location.href = '/admin/login';
                                } 
                                
                            });
                        } else {
                            console.error('Error:', data);
                        }
                    } catch (jsonError) {
                        console.error('JSON parse error:', jsonError);
                        console.error('Response text:', text);
                    }
                } catch (error) {
                    Swal.close();
                    console.error('Error:', error);
                }
            });

            const newPasswordInput = document.getElementById('newPassword');
            const passwordHint = document.getElementById('passwordHint');

            function validatePasswordFormat(password) {
                return /^(?=.*[0-9])(?=.*[\W_]).{8,15}$/.test(password);
            }

            newPasswordInput.addEventListener('input', function() {
                if (newPasswordInput.value.length === 0) {
                    passwordHint.style.display = 'none';
                    newPasswordInput.style.borderColor = '';
                    return;
                }
                if (validatePasswordFormat(newPasswordInput.value)) {
                    newPasswordInput.style.borderColor = 'green';
                    passwordHint.style.display = 'none';
                } else {
                    newPasswordInput.style.borderColor = 'red';
                    passwordHint.style.display = 'block';
                    passwordHint.style.color = 'red';
                }
            });
        });
    </script>
</body>

</html>
