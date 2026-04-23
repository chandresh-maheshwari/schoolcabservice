<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
</head>
<style>
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

<body>
  <div class="jumbotron">
    <h3>LOVESPACE</h3>
  </div>
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-1"></div>
      <div class="col-md-10">
        <div class="card bg-warning mt-5">
          <h3 class="ms-5 mt-3 text-light text-center mb-3" id="mainpage-title">Register</h3>
          <hr>
          <div class="container" id="registerform">
            <form class="row" method="post" action="{{route('addaccountinformation')}}">
              <input type="hidden" name="direct_register" id="direct_register" value="{{  ! empty($orderprocess) ? $orderprocess : 'No'}}">
              @csrf
              <div class="col-md-6">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="text" name="email" class="form-control">
              </div>
              <div class="col-md-6 mt-3">
                <label class="form-label">password</label>
                <div class="password-field-wrapper">
                  <input type="password" name="password" class="form-control" id="register_password">
                  <span class="password-toggle-icon" onclick="togglePasswordField('register_password')">
                    <i class="fa fa-eye"></i>
                  </span>
                </div>
              </div>
              <div class="col-md-6 mt-3">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control">
              </div>
              <div class="col-md-4 mt-3">
                <label class="form-label">City</label>
                <input type="text" name="city" class="form-control">
              </div>
              <div class="col-md-4 mt-3">
                <label class="form-label">States</label>
                <select name="states" id="" class="form-select">
                  <option value="Punjab">Punjab</option>
                  <option value="Sindh">Sindh</option>
                  <option value="Kpk">Kpk</option>
                  <option value="Balochistan">Balochistan</option>
                </select>
              </div>
              <div class="col-md-4 mt-3">
                <label class="form-label">Contact</label>
                <input name="contact" type="text" class="form-control">
              </div>

              <div class="col-md-12 mt-3">
                <label class="form-label">Pickup Addess will be same </label>
                <br>
                <div class="form-check form-check-inline mt-3">
                  <input type="radio" name="pickup_address" class="form-check-input" id="yes" value="yes">
                  <label class="form-check-label">Yes</label>
                </div>
                <div class="form-check form-check-inline">
                  <input type="radio" class="form-check-input" name="pickup_address" id="no" value="no">

                  <label class="form-check-label">No</label>
                </div>
                <div class="col-md-12 mt-3">
                  <input type="text" id="newaddress" class="form-control" name="newaddress" value="" placeholder="enter new pickup address">
                </div>
              </div>
              <div class="col-md-6 mt-3">
                <label class="form-label">Date</label>
                <input type="date" name="date1" class="form-control">
              </div>
              <div class="col-md-6 mt-3">
                <label class="form-label">Time</label>
                <input type="time" name="time1" class="form-control">
              </div>
              <div class="col-md-6 mt-3">
                <input type="date" name="date2" class="form-control">
              </div>
              <div class="col-md-6 mt-3">
                <input type="time" name="time2" class="form-control">
              </div>
              <div class="col-md-6 mt-3">
                <input type="date" name="date3" class="form-control">
              </div>
              <div class="col-md-6 mt-3">
                <input type="time" name="time3" class="form-control">
              </div>
              <div class="col-md-12 mt-3">
                <br>
                <button class="btn btn-primary form-control">Submit</button>
                <button type="button" class="btn btn-primary form-control mt-5" id="loginbtn">Login</button>
              </div>
            </form>
          </div>
          <div class="container d-none" id="loginform">
            <div class="row">
              <div class="col-lg-4"></div>
              <div class="col-lg-4">
                <form class="mt-5 mb-5" id="myForm" action="{{ route('loginuser') }}" method="post" name="contact-form">
                <input type="hidden" name="direct_login" id="direct_login" value="{{  ! empty($orderprocess) ? $orderprocess : 'No'}}">
                  @csrf
                  <div class="form-outline">
                    <label class="form-label" for="" style="margin-right:280px ;">Email
                      address</label>
                    <input type="email" name="email" class="form-control" id="" required />
                    @error('email')
                    <span><strong style="color: red">{{ $message }}</strong>
                      {{-- <strong>{{ $errors }}</strong> --}}
                    </span> @enderror
                  </div>
                  <div class="form-outline ">
                    <label class="form-label" for="" style="margin-right:310px ;">Password</label>
                    <div class="password-field-wrapper">
                      <input type="password" name="password" class="form-control" id="login_password" placeholder="enter password" required />
                      <span class="password-toggle-icon" onclick="togglePasswordField('login_password')">
                        <i class="fa fa-eye"></i>
                      </span>
                    </div>
                    @error('password')
                    <span><strong style="color: red">{{ $message }}</strong></span>
                    @enderror
                  </div>

                  <button class="btn btn-primary text-light mt-2" id="submit" type="submit">Submit</button><br>
                  <button class="btn btn-warning"><a href="" class="btn btn-link">Reset Password</a></button>
                  <button type="button" class="btn btn-primary form-control" id="registerbtn">
                    Register
                  </button>
                </form>

              </div>
              <div class="col-lg-4"></div>
            </div>

          </div>
          <br><br><br>
        </div>
      </div>
      <div class="col-md-1"></div>
    </div>
  </div>
</body>
<script src="https://code.jquery.com/jquery-1.9.1.min.js"></script>

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

  $(document).ready(function() {

    $('#loginbtn').on('click', function() {
      $('#loginform').removeClass('d-none').show();
      $('#registerform').hide();
    });

    $('#registerbtn').on('click', function() {
      $('#registerform').show();
      $('#loginform').hide();
    });

    $("#newaddress").hide();
    $('#no').click(function() {
      $("#newaddress").show(500);
    });
    $('#yes').click(function() {
      $("#newaddress").hide(500);
    });
  });
</script>

</html>
