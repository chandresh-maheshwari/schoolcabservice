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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="assets/js/pricing.js"></script>
    <style>
        /* Chrome, Safari, Edge, Opera */
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        #viewpromo {
            display: none;
        }

        /* Firefox */
        input[type=number] {
            -moz-appearance: textfield;
        }
    </style>

</head>

<body>

    {{-- {{dd(Session::has('orderdetails'))}} --}}

    {{-- Session::has('orderdetails') --}}
    <div class="jumbotron">
        <h3>LOVESPACE</h3>
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-1"></div>
            <form action="{{ route('orderconfirm') }}" method="POST">
                @csrf
                <div class="col-md-10">

                    <div class="card bg-warning">
                        <h3 class="mt-3 ms-5 text-light">Address Information</h3>
                        <div class="row mt-5 ms-5">
                            <div class="col-md-4">
                                {{-- <h5 class="text-light">Are you a University Student?</h5> --}}
                            </div>
                            <div class="col-md-8">
                                <input name="university_student" value="yes" type="radio" required>Yes &nbsp&nbsp&nbsp&nbsp
                                <input name="university_student" value="no" type="radio" required>No
                            </div>
                        </div>
                        <div class="row mt-3 ms-5">
                            <div class="col-md-12">
                                <h5 class="text-light">Any Special Instructions?</h5>
                            </div>
                        </div>
                        <div class="row mt-3 ms-5">
                            <div class="col-md-8">
                                <textarea rows="7" name="instructions" placeholder="please enter here !" class="form-control mb-5" required></textarea>
                            </div>
                        </div>
                        <div class="row mt-1 ms-5">
                            <div class="col-md-4">
                                <h5 class="text-light">Do you live in Walk-up ? </h5>
                            </div>
                            <div class="col-md-8">
                                <input name="walk_up" value="yes" type="radio" required>Yes &nbsp&nbsp&nbsp&nbsp
                                <input name="walk_up" value="no" type="radio" required>No
                            </div>
                        </div>
                        <div class="row mt-1 ms-5">
                            <div class="col-md-12">
                                <h5 class="text-light">Have you been in close contact with someone who is on Mandatory
                                    Home
                                    Quarantine from the Centre For Health Protection, or have been issued one? </h5>
                            </div>
                        </div>
                        <div class="row mt-1 ms-5">
                            <div class="col-md-12">
                                <input  name="health_protection" value="yes" type="radio" required>Yes &nbsp&nbsp&nbsp&nbsp
                                <input  name="health_protection" value="no" type="radio" required>No
                            </div>
                        </div>
                        <div class="row mt-1 ms-5">
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-light form-control mb-5">Next</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-1"></div>
            </form>
        </div>
    </div>

</body>

</html>
