
var smallboxcounter = 1;
var mediumbox = 1;
var largebox = 1;
var product = "";
var price = 0;


function smallbox(thisId) {

    // $("input.qtybox").attr({
    //     "max" : 100,         
    //     "min" : 2          
    //  });
    // console.log($(thisId).val())
    var id = $(thisId).attr('id');
    var r = $(thisId).data('price');
    var title = $(thisId).data('title');
    var id_val = $(thisId).data('id');
    
    var qtyBoxVal = document.getElementById('sm' + id_val).value;
   
    var hidden_price = document.getElementById('hidden_totalamount').value;
    // console.log('INC hidden price=' + hidden_price)
    // console.log('INC qty=' + qtyBoxVal)
    // console.log('INC price=>' + price)
    // console.log('INC R value=>' + r)
    // console.log('INC smallboxcounter=>' + smallboxcounter)
    price = parseInt(price) + r;
    document.getElementById('totalamount').value = "HR $" + price;
    document.getElementById('hidden_totalamount').value = price;
    document.getElementById('sm' + id_val).value = parseInt(qtyBoxVal) + 1;
    smallboxcounter++;
    product = title + (parseInt(qtyBoxVal) + 1);
    document.getElementById('status' + id_val).value = product;
}
function smallboxDec(thisId) {
    var id = $(thisId).attr('id');
    var r = $(thisId).data('price');
    var title = $(thisId).data('title');
    var id_val = $(thisId).data('id');
    // alert(smallboxcounter);
    var qtyBoxVal = document.getElementById('sm' + id_val).value;
    var hidden_price = document.getElementById('hidden_totalamount').value;
    // if(qtyBoxVal = 1) {

    //     var b = $('#sm' + id_val).val(0);
        
    // }
    price = hidden_price;
    if (smallboxcounter > 0) {

        //      console.log('hidden price='+hidden_price)
        // console.log('qty='+qtyBoxVal)
        // console.log('price=>'+price)
        // console.log('R value=>'+r)
        // console.log('smallboxcounter=>'+smallboxcounter)


        document.getElementById('sm' + id_val).value = parseInt(qtyBoxVal) - 1;

        price = price - r;
        document.getElementById('totalamount').value = "HR $" + price;
        product = title + (parseInt(qtyBoxVal) - 1);
        document.getElementById('status' + id_val).value = product;
        document.getElementById('hidden_totalamount').value = price;
    }
    else {
        document.getElementById('totalamount').value = "HR $" + price;
    }
}
$(document).ready(function () {
    $(".selectmonth").on('change', function () {

        var id = $('span.changeprice').attr('idd')

        // var vid = '#price_'+ id;
        // alert(id);
        var month = $(".selectmonth option:selected").val();
        //  alert(month);
        $.ajax({
            type: "post",
            data: {
                month: month,
            },
            url: "/pricechange",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            dataType: "JSON",
            success: function (data) {
                total_price = 0;
                // console.log(data.data)
                $.each(data.data, function (key, value) {

                    setTimeout(() => {


                        $('#price_' + key).text('$' + parseInt(value));
                        $('#price_' + key).val('$' + parseInt(value));
                        //   $('button data-price' + key).text(value);

                        $('#sn_d' + key).data('price', parseInt(value));
                        $('#sn_d' + key).val(parseInt(value));

                        $('#sn' + key).data('price', parseInt(value));
                        $('#sn' + key).val(parseInt(value));

                        $('#sm_hidden'+key).val(parseInt(value));

                        var qtyBoxVal = document.getElementById('sm' + key).value;

                        total_price = parseInt(total_price) + parseInt(qtyBoxVal) * parseInt(value);
                        // console.log(total_price);
                        document.getElementById('totalamount').value = "HR $" + total_price;
                        document.getElementById('hidden_totalamount').value = parseInt(total_price);



                        // $('#sm_hidden' + key).val(value);
                    }, 2000);

                });
            }
        });
    });

    $('#verifycode').on('click',function(){
        // alert(888888);
            var code = $("#viewpromo").val();
            // alert(code);
            $.ajax({
                type: "post",
                data: {
                    code: code,
                },
                url: "/promoverify",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                dataType: "JSON",
                success: function (priceget) {
                    // total_price = 0;
                    // console.log(priceget.priceget)
                    var emparr = [];
                    $.each(priceget.priceget, function (key , data) {

                        
                    // emparr = emparr.concat(data);

                    // console.log(emparr);
                        // console.log(data);
                    $('#viewpromo_hidden').text(parseInt(data));
                    $('#viewpromo_hidden').val(parseInt(data));

                   ;
                    }); 
                }
            });
        
        })
});

function promocode() {   

    document.getElementById('viewpromo').style.display = 'block';
    document.getElementById('btnview').style.display = 'none';
    document.getElementById('viewmessage').style.display = 'block';
}

$('#verifycode').on('click',function(){
alert(888888);
    // var code = $("#viewpromo").val();
    // alert(code);
    // $.ajax({
    //     type: "post",
    //     data: {
    //         month: month,
    //     },
    //     url: "/promoverify",
    //     headers: {
    //         'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    //     },
    //     dataType: "JSON",
    //     success: function (data) {
    //         total_price = 0;
    //         // console.log(data.data)
            

    //     }
    // });

})

function mbox() {
    var r = 70;
    price = price + r * mediumbox;
    document.getElementById('totalamount').value = "HR $" + price;
    document.getElementById('md').value = mediumbox;
    mediumbox++;
    product = "Medium Box " + mediumbox;
    document.getElementById('status2').value = product;
}
function mboxDec() {
    if (mediumbox > 0) {
        var r = 70;
        document.getElementById('md').value = --mediumbox;

        price = price - r * mediumbox;
        document.getElementById('totalamount').value = "HR $" + price;
        product = "Medium Box " + mediumbox;
        document.getElementById('status2').value = product;
    }
    else {
        document.getElementById('totalamount').value = "HR $" + price;
    }
}

function lbox() {
    var r = 90;
    price = price + r * largebox;
    document.getElementById('totalamount').value = "HR $" + price;
    document.getElementById('lg').value = largebox;
    largebox++;
    product = "Large Box " + largebox;
    document.getElementById('status3').value = product;
}
function lboxDec() {
    if (largebox > 0) {
        var r = 90;
        document.getElementById('lg').value = --largebox;

        price = price - r * largebox;
        document.getElementById('totalamount').value = "HR $" + price;
        product = "Large Box " + largebox;
        document.getElementById('status3').value = product;
    }
    else {
        document.getElementById('totalamount').value = "HR $" + price;
    }
}


// suitcase
var mdsuitcase = 1;
function suitCase() {
    var r = 19;
    price = price + r * mdsuitcase;
    document.getElementById('totalamount').value = "HR $" + price;
    document.getElementById('suit').value = mdsuitcase;
    mdsuitcase++;
    product = "SuitCase Box " + mdsuitcase;
    document.getElementById('status4').value = product;
}
function suitCaseDec() {
    if (mdsuitcase > 0) {
        var r = 19;
        document.getElementById('suit').value = --mdsuitcase;

        price = price - r * mdsuitcase;
        document.getElementById('totalamount').value = "HR $" + price;
        product = "SuitCase Box " + mdsuitcase;
        document.getElementById('status4').value = product;
    }
    else {
        document.getElementById('totalamount').value = "HR $" + price;
    }
}

// end suitcase
// largesuitcase
var largesuitcase = 1;
function lsuitCase() {
    var r = 21;
    price = price + r * largesuitcase;
    document.getElementById('totalamount').value = "HR $" + price;
    document.getElementById('lsuit').value = largesuitcase;
    largesuitcase++;
    product = "Large SuitCase Box " + largesuitcase;
    document.getElementById('status5').value = product;
}
function lsuitCaseDec() {
    if (largesuitcase > 0) {
        var r = 21;
        document.getElementById('lsuit').value = --largesuitcase;

        price = price - r * largesuitcase;
        document.getElementById('totalamount').value = "HR $" + price;
        product = "Large SuitCase Box " + largesuitcase;
        document.getElementById('status5').value = product;
    }
    else {
        document.getElementById('totalamount').value = "HR $" + price;
    }
}

// endlargesuitcase

// start crockery

var crock = 1;
function crockery() {
    var r = 13;
    price = price + r * crock;
    document.getElementById('totalamount').value = "HR $" + price;
    document.getElementById('crock').value = crock;
    crock++;
    product = "Crockery " + crock;
    document.getElementById('status6').value = product;
}
function crockeryDec() {
    if (crock > 0) {
        var r = 13;
        document.getElementById('crock').value = --crock;

        price = price - r * crock;
        document.getElementById('totalamount').value = "HR $" + price;
        product = "Crockery " + crock;
        document.getElementById('status6').value = product;
    }
    else {
        document.getElementById('totalamount').value = "HR $" + price;
    }
}

// end crockery

// start bike

var bikecounter = 1;
function bike() {
    var r = 13.5;
    price = price + r * bikecounter;
    document.getElementById('totalamount').value = "HR $" + price;
    document.getElementById('bike').value = bikecounter;
    bikecounter++;
    product = "Bike " + bikecounter;
    document.getElementById('status7').value = product;
}
function bikeDec() {
    if (bikecounter > 0) {
        var r = 13.5;
        document.getElementById('bike').value = --bikecounter;

        price = price - r * bikecounter;
        document.getElementById('totalamount').value = "HR $" + price;
        product = "Bike " + bikecounter;
        document.getElementById('status7').value = product;
    }
    else {
        document.getElementById('totalamount').value = "HR $" + price;
    }
}


// end bike

// start guiter 

var guitrcounter = 1;
function guiter() {
    var r = 13.5;
    price = price + r * guitrcounter;
    document.getElementById('totalamount').value = "HR $" + price;
    document.getElementById('guiter').value = guitrcounter;
    guitrcounter++;
    product = "Guiter " + guitrcounter;
    document.getElementById('status8').value = product;
}
function guiterDec() {
    if (guitrcounter > 0) {
        var r = 13.5;
        document.getElementById('guiter').value = --guitrcounter;

        price = price - r * guitrcounter;
        document.getElementById('totalamount').value = "HR $" + price;
        product = "Guiter " + guitrcounter;
        document.getElementById('status8').value = product;
    }
    else {
        document.getElementById('totalamount').value = "HR $" + price;
    }
}


// end guiter

// start keyboard 

var keyboardcounter = 1;
function keyboard() {
    var r = 13.5;
    price = price + r * keyboardcounter;
    document.getElementById('totalamount').value = "HR $" + price;
    document.getElementById('keyboard').value = keyboardcounter;
    keyboardcounter++;
    product = "Keyboard " + keyboardcounter;
    document.getElementById('status9').value = product;
}
function keyboardDec() {
    if (keyboardcounter > 0) {
        var r = 13.5;
        document.getElementById('keyboard').value = --keyboardcounter;

        price = price - r * keyboardcounter;
        document.getElementById('totalamount').value = "HR $" + price;
        product = "Keyboard " + keyboardcounter;
        document.getElementById('status9').value = product;
    }
    else {
        document.getElementById('totalamount').value = "HR $" + price;
    }
}


// end keyboard 

// start tv


var tvcounter = 1;
function tv() {
    var r = 13.5;
    price = price + r * tvcounter;
    document.getElementById('totalamount').value = "HR $" + price;
    document.getElementById('tv').value = tvcounter;
    tvcounter++;
    product = "TV " + tvcounter;
    document.getElementById('status10').value = product;
}
function tvDec() {
    if (tvcounter > 0) {
        var r = 13.5;
        document.getElementById('tv').value = --tvcounter;

        price = price - r * tvcounter;
        document.getElementById('totalamount').value = "HR $" + price;
        product = "TV " + tvcounter;
        document.getElementById('status10').value = product;
    }
    else {
        document.getElementById('totalamount').value = "HR $" + price;
    }
}


// end tv 

// start rack 

var clothcounter = 1;
function clothrack() {
    var r = 13.5;
    price = price + r * clothcounter;
    document.getElementById('totalamount').value = "HR $" + price;
    document.getElementById('rack').value = clothcounter;
    clothcounter++;
    product = "Clothes Rack " + clothcounter;
    document.getElementById('status11').value = product;
}
function clothrackDec() {
    if (clothcounter > 0) {
        var r = 13.5;
        document.getElementById('rack').value = --clothcounter;

        price = price - r * clothcounter
        document.getElementById('totalamount').value = "HR $" + price;
        product = "Clothes Rack " + clothcounter;
        document.getElementById('status11').value = product;
    }
    else {
        document.getElementById('totalamount').value = "HR $" + price;
    }
}

// end rack  

// start iron 

var ironcounter = 1;
function iron() {
    var r = 13.5;
    price = price + r * ironcounter;
    document.getElementById('totalamount').value = "HR $" + price;
    document.getElementById('rack').value = ironcounter;
    ironcounter++;
    product = "Iron" + ironcounter;
    document.getElementById('status12').value = product;
}
function ironDec() {
    if (ironcounter > 0) {
        var r = 13.5;
        document.getElementById('rack').value = --ironcounter;

        price = price - r * ironcounter;
        document.getElementById('totalamount').value = "HR $" + price;
        product = "Iron" + ironcounter;
        document.getElementById('status12').value = product;
    }
    else {
        document.getElementById('totalamount').value = "HR $" + price;
    }
}
// end iron 

// start other item 

var otheritem1 = 1;
function otheritem() {
    var r = 13.5;
    price = price + r * otheritem1;
    document.getElementById('totalamount').value = "HR $" + price;
    document.getElementById('otheritem').value = otheritem1;
    otheritem1++;
    product = "Other Item" + otheritem1;
    document.getElementById('status13').value = product;
}
function otheritemDec() {
    if (otheritem1 > 0) {
        var r = 13.5;
        document.getElementById('otheritem').value = --otheritem1;

        price = price - r * otheritem1;
        document.getElementById('totalamount').value = "HR $" + price;
        product = "Other Item" + otheritem1;
        document.getElementById('status13').value = product;
    }
    else {
        document.getElementById('totalamount').value = "HR $" + price;
    }
}

// end other item 







