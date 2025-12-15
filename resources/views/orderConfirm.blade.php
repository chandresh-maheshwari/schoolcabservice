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
<style>
    table tr td {
        color: white;
    }
</style>

<body>
    <div class="jumbotron">
        <h3>LOVESPACE</h3>
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-1"></div>
            <form action="{{route('paymentmethod')}}" method="POST">
                @csrf
                <div class="col-md-10">
                    <div class="card bg-warning">
                        <h5 class="mt-3 ms-5 text-light">Order Confirmation</h5>
                        <div class="row">
                            <h5 class="mt-3 ms-5 text-light">Account Information</h5>
                            <div class="col-md-12">
                                <table class="table table-borderless ms-5">

                                    @foreach ($accountinformation as $item)
                                        <tr>
                                            <td>Name*</td>
                                            <td>{{ $item->name }}</td>
                                        </tr>
                                        <tr>
                                            <td>Email*</td>
                                            <td>{{ $item->email }}</td>
                                        </tr>
                                    @endforeach
                                    @foreach ($checkinoutedetails as $contact)
                                        <tr>
                                            <td>Contact*</td>
                                            <td>{{ $contact->contact }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>

                        </div>
                        <hr>
                        <div class="row">
                            <h5 class="mt-3 ms-5 text-light">Your Orders</h5>
                            <div class="col-md-12">
                                <table class="table table-borderless" style="text-align: center">
                                    {{-- {{dd($data)}} --}}
                                    @php
                                        $products = session()->get('orderdetails');
                                        // {{ dd($products) ;}}
                                    @endphp
                                    <tr>
                                        <td>Product Name *</td>
                                        <td>Product Quantity *</td>
                                        <td>Product Price *</td>
                                        <td>PromoCode</td>
                                        <td>Product Total *</td>
                                    </tr>
                                    @foreach ($products as $qutkey => $orderdata)
                                        @if (is_array($orderdata))
                                            <tr>                                            
                                                <td>{{ $orderdata['title'] }}</td>
                                                <td>{{ $orderdata['qty'] }}</td>
                                                <td>{{ $orderdata['price'] }}</td>
                                                <td>{{ $products['promo'] }}</td>
                                                <td>{{$total =  $orderdata['qty']*$orderdata['price'];}}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </table>
                            </div>

                        </div>
                        <hr>
                        <div class="row">
                            <h5 class="mt-3 ms-5 text-light">CheckIn/CheckOut</h5>
                            {{-- {{dd($checkinoutedetails)}} --}}
                            <div class="col-md-12">
                                <table class="table table-borderless ms-5">
                                    @foreach ($checkinoutedetails as $datetime)
                                        <tr>
                                            <td>Date 1</td>
                                            <td>{{ $datetime->date1 }}</td>
                                            <td>Time 1</td>
                                            <td>{{ $datetime->time1 }}</td>
                                        </tr>
                                        <tr>
                                            <td>date 2</td>
                                            <td>{{ $datetime->date2 }}</td>
                                            <td>Time 2</td>
                                            <td>{{ $datetime->time2 }}</td>
                                        </tr>
                                        <tr>
                                            <td>Date 3</td>
                                            <td>{{ $datetime->date3 }}</td>
                                            <td>Time 3</td>
                                            <td>{{ $datetime->time3 }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>

                        </div>
                        <hr>
                        <div class="row">
                            <h5 class="mt-3 ms-5 text-light">Summary</h5>
                            <div class="col-md-12">
                                <table class="table table-borderless ms-5">
                                    {{-- @foreach ($products as $orderdata)
                        
                        @if (is_array($orderdata))                  
                         --}}

                                    <tr>
                                        <td>Total*</td>
                                        <td>{{ $products['total'] }}</td>
                                    </tr>
                                    <tr>
                                        <td>PromoCode*</td>
                                        <td>{{ $products['promo'] }}</td>
                                    </tr>

                                    {{-- @endif
                        @endforeach --}}
                                </table>
                            </div>

                        </div>
                        <hr>
                        <div class="row mb-5 ms-5">

                            <div class="col-md-6">
                                <button type="submit" class="btn btn-light form-control">Next</button>
                            </div>
                        </div>
                    </div>
            {{-- </form> --}}
            <div class="col-md-1"></div>
        </div>
    </div>
</body>

</html>
