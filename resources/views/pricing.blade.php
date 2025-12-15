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
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <script src="{{ url('assets/js/pricing.js') }}"></script>


    <style>
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        #viewpromo {
            display: none;
        }

        input[type=number] {
            -moz-appearance: textfield;
        }
    </style>
</head>

<body>
    <div class="jumbotron">
        {{-- <h3>LOVESPACE</h3> --}}
        <img src="/images/WasteAccountant_LOGO.png" width="20%">
    </div>
    <div class="container-fluid">
        <form action="{{ route('address') }}" method="post" >
            @csrf
            <div class="row">
                <div class="col-md-1"></div>
                <div class="col-md-6">
                    <p><b>How long are you storing?</b></p>
                    <p id="message" class="mb-3" style="margin-top: -10px;">The longer you store, the more you save.
                    </p>
                    <div class="row">
                        <div class="col-md-12">

                            <select class="form-control btn btn-warning selectmonth" id="selectmonth" >
                                <option class="btn btn-light" value="1">1 month</option>
                                <option class="btn btn-light" value="2">2 Month</option>
                                <option class="btn btn-light" value="3">3 Month</option>
                                <option class="btn btn-light" value="4">4 Month</option>
                                <option class="btn btn-light" value="5">5 Month</option>
                                <option class="btn btn-light" value="6">6 Month</option>
                                <option class="btn btn-light" value="7">7 Month</option>
                                <option class="btn btn-light" value="8">8 Month</option>
                                <option class="btn btn-light" value="9">9 Month</option>
                                <option class="btn btn-light" value="10">10 Month</option>
                                <option class="btn btn-light" value="11">11 Month</option>
                                <option class="btn btn-light" value="12">12 Month</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <p id="messageShow" class="mt-3">You've selected a rolling monthly plan, order your boxes back
                            anytime.</p>
                    </div>
                    <hr>
                    <div class="container me-5 resetdata" style="margin-left: -37px;">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="m-4">
                                    <p><b>What are You Storing?</b>
                                        <button type="button" class="btn btn-link ms-4" data-bs-toggle="collapse"
                                            data-bs-target="#myCollapse"><img
                                                src="https://www.iconpacks.net/icons/2/free-arrow-down-icon-3101-thumb.png"style="height:20px ;width: 20px;"
                                                alt="">
                                        </button>
                                    </p>
                                    @foreach ($data as $item)
                                        <div class="collapse" id="myCollapse" attr={{ $item->id }}
                                            price={{ $item->price_per_month_6below }}>

                                            <button class="btn btn-link" id="sn{{ $item->id }}" type="button"
                                                data-price="{{ $item->price_per_month_6below }}"
                                                value="{{ $item->price_per_month_6below }}"
                                                data-title="{{ $item->title }}" data-id="{{ $item->id }}"
                                                onclick="smallbox(this)"><i class="fa fa-angle-up" aria-hidden="true"
                                                    style="margin-bottom: -500px;margin-left: 4px;"></i>
                                            </button>
                                            <br>
                                            <input  style="height: 40px;width: 40px;text-align: center;"
                                                name="sm[{{ $item->id }}]" class="qtybox" id="sm{{ $item->id }}"
                                                type="number" value="0" min="0"  />
{{-- <script>$("input.qtybox").attr({
    "max" : 5,         
    "min" : 2          
 });</script> --}}
                                            {{-- ---------hidden start --}}

                                            <input style="height: 40px;width: 40px;text-align: center;"
                                                name="productid[{{ $item->id }}]"
                                                id="productid{{ $item->id }}" type="hidden"
                                                value="{{ $item->id }}" />

                                            <input style="height: 40px;width: 40px;text-align: center;"
                                                name=" titleget[{{ $item->id }}]" id="title{{ $item->id }}"
                                                type="hidden" value="{{ $item->title }}" />

                                            <input style="height: 40px;width: 40px;text-align:center;"
                                                name="priceget[{{ $item->id }}]" id="sm_hidden{{ $item->id }}"
                                                type="hidden" value="{{ $item->price_per_month_6above }}" />

                                            {{-- ---------hidden    end --}}

                                            <strong><span id="title_{{ $item->id }}">{{ $item->title }}</span>

                                                <span class="changeprice" idd="{{ $item->id }}" value=""
                                                    id="price_{{ $item->id }}">${{ $item->price_per_month_6below }}</span>

                                            </strong>
                                            <br>
                                            <button class="btn btn-link" id="sn_d{{ $item->id }}" type="button"
                                                data-price="{{ $item->price_per_month_6below }}"
                                                data-title="{{ $item->title }}" data-id="{{ $item->id }}"
                                                onclick="smallboxDec(this)"><i class="fa fa-angle-down"
                                                    style="margin-left: 4px;" aria-hidden="true"></i></button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- <form action="{{ route('address') }}" method="post">
                    @csrf --}}
                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-warning form-control">Continue
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="container" style="width: 450px;">
                        <div class="card bg-warning">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="container">
                                        <div class="row">
                                            <div class="col-md-7"> 
                                               
                                             
                                                    {{-- <button>
                                                    <a >+Promo Code</a></button> --}}
                                                <h5 class="mt-5  text-light" id="viewmessage"
                                                    style="display: none;margin-top:55px!important;">Enter Promo Code
                                                </h5>
                                            </div>
                                            <div class="col-md-5">
                                                <input type="text" class="form-control mt-5" value="" id="viewpromo"
                                                    name="promo">
                                                    <input type="hidden" class="form-control mt-5" value="" id="viewpromo_hidden"
                                                    name="promoprice">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-7">
                                    <h5 class="mt-5 ms-3 text-light">Monthly Cost Estimated</h5>
                                </div>
                                <div class="col-md-5">
                                    <input class="mt-5 mb-5 form-control" type="text" name=""
                                        id="totalamount" value="HR$ 0" style="width: 140px;" disabled />
                                    <input name="hidden_totalamount" id="hidden_totalamount" type="hidden"
                                        value="0" />
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    @foreach ($data as $stetusid)
                                        <input type="text" id="status{{ $stetusid->id }}"
                                            class="form-control bg-warning" style="border: 0px;" readonly>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        {{-- </form> --}}

                    </div>
                </div>

            </div>
        </form>

        <button class="btn btn-info mt-5 ms-5" onclick="promocode()"
        id="btnview"><a href='#'></a>+Promo Code</button>
        <button class="btn btn-info mt-5 ms-5"  id="verifycode">
            {{-- <a href="{{route('promoverify')}}"></a> --}}
            Verify</button>
    </div>
    </div>
    </div>
    </div>
    <script>
      
    </script>
</body>

</html>
