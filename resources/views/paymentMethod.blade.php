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
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
        crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
        integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"
        integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct"
        crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="assets/js/pricing.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

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
<style>
    table tr td 
    {
        color:white;
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
                <div class="card bg-warning">
                    <form action="{{route('cardinformation')}}" method="POST">
                        @csrf
                    <h5 class="mt-5 ms-5 text-light">Payment Method</h5>
                    <h5 class="mt-3 ms-5 text-light">
                        <input type="radio" name="selectcard" value="creditcard">Credit Card
                    </h5>
                    <h5 class="mt-1 ms-5 text-light">
                        <input type="radio" name="selectcard" value="webchatpay">WebChat Pay
                    </h5>
                    <h5 class="mt-1 ms-5 text-light">
                        <input type="radio" name="selectcard" value="cash/ATM">Cash/ATM
                    </h5>
                    {{-- {{dd(Session::get('addressaccinfo'));}} --}}
                    <h5 class="mt-1 ms-5 text-light" style="margin-top:30px!important">
                        <i class="fa fa-credit-card" aria-hidden="true">&nbsp&nbsp&nbspCard Infromation</i>
                        <input type="text" name="card_information" placeholder="Enter Card Number" class="form-control mt-1" style="border: 0px; width:400px ;">
                        <br>
                        
                        <input type="text" placeholder="DD/MM/YYYY" name="expireedate" class="form-control mt-1" style="border: 0px; width:400px ;" inputmode="numeric">
                        <br>
                        <input type="text" placeholder="Enter CVC Number" name="cvc_number" class="form-control mt-1" style="border: 0px; width:400px ;">
                        <br>
{{-- {{dd($lastinsertedid);}} --}}
                        @foreach ($lastinsertedid as $item)

                        {{-- {{dd($item);}} --}}
                        <input type="hidden" name="lastinsertedid[]" value="{{$item}}" class="form-control mt-1" style="border: 0px; width:400px ;">
                        @endforeach                     

                    </h5>
                    <button class="form-control ms-5 mb-5" style="width: 400px;">Place Order</button>
                </form>
                </div>
            </div>
            <div class="col-md-1"></div>
        </div>
    </div>
</body>

</html>
