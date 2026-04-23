<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
        integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"
        integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous">
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<style>
    .unit-5 {
        padding-top: 50px;
        padding-bottom: 50px;
        background-size: cover;
        background-position: center center;
    }

    .unit-5.overlay {
        position: relative;
    }

    .unit-5.overlay:before {
        position: absolute;
        content: "";
        left: 0;
        right: 0;
        bottom: 0;
        top: 0;
        z-index: 1;
        background: rgba(0, 0, 0, 0.5);
    }

    .password-field-wrapper {
        position: relative;
    }

    .password-field-wrapper .form-control {
        padding-right: 42px;
    }

    .password-toggle-icon {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #2d336b;
        cursor: pointer;
        z-index: 3;
    }
</style>

<script> 


// $(document).ready(function(){
//     //   $("#submit").reset();
    
//     $('#myForm').reset();
//     alert('dfhdfhdgf');
 
// });

document.getElementById("myForm").reset();
</script>

<body>
    <div class="card" style="border-bottom:0px;">
        <div class="card-header unit-5 "
            style="opacity: 0.6;;height: 400px;background-image: url('https://soms.ubox.com.hk/vendor/authsomsclient/images/hero_bg_2.jpg') ;">

            <div class="text-center">
                <h1 class="text-warning" style="padding-top: 100px;"><a href="home.html"
                        style="color: white;text-decoration: none;">Book Storage</a></h1>
            </div>
        </div>


        <div class="container-fluid" style="margin-top:60px;">
            <div class="row">
                <div class="col-lg-4"></div>
                <div class="col-lg-4">
                    <form class="mt-5 mb-5" id="myForm" action="{{ route('loginuser') }}" method="post" name="contact-form" >
                        @csrf
                        <div class="form-outline">
                            <label class="form-label" for="" style="margin-right:280px ;">Email
                                address</label>
                            <input type="email" name="email" class="form-control" id="" required
                                 />
                                @error('email')
                                <span><strong style="color: red">{{ $message }}</strong>
                                   {{-- <strong>{{ $errors }}</strong>  --}}
                                </span> @enderror  
                        </div>
                        <div class="form-outline ">
                            <label class="form-label"for="" style="margin-right:310px ;">Password</label>
                            <div class="password-field-wrapper">
                                <input type="password" name="password" class="form-control" id="signin_password"
                                    placeholder="enter password" required />
                                <span class="password-toggle-icon" onclick="togglePasswordField('signin_password')">
                                    <i class="fa fa-eye"></i>
                                </span>
                            </div>
                            @error('password')
                                <span><strong style="color: red">{{ $message }}</strong></span>
                            @enderror                               
                        </div>
                     
                        <button class="btn btn-warning mt-3 text-light" id="submit" type="submit" >Submit</button><br>
                        <button class="btn btn-warning mt-5"><a href="" class="btn btn-link">Reset Password</a></button>
                        <button class="btn btn-warning mt-5"> <a href="{{ route('register') }}"
                                class="btn btn-link">Register Now</a></button>
                    </form>

                </div>
                <div class="col-lg-4"></div>
            </div>
        </div>
    </div>
<script>
function togglePasswordField(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = field.closest('.password-field-wrapper').querySelector('i');

    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>
</body>
</html>
