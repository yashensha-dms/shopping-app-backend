<!DOCTYPE html>
<html>
<head>
    <title>{{ $settings['general']['site_title'] }}</title>
</head>
<style type="text/css">
    body{
        font-family: 'Roboto Condensed', sans-serif;
    }
    .m-0{
        margin: 0px;
    }
    .p-0{
        padding: 0px;
    }
    .pt-5{
        padding-top:5px;
    }
    .mt-10{
        margin-top:10px;
    }
    .text-center{
        text-align:center !important;
    }
    .w-100{
        width: 100%;
    }
    .w-50{
        width:50%;
    }
    .w-85{
        width:85%;
    }
    .w-15{
        width:15%;
    }
    .logo img{
        width:200px;
        height:60px;
    }
    .gray-color{
        color:#52a750a4;
    }
    .text-bold{
        font-weight: bold;
    }
    .border{
        border:1px solid black;
    }
    table tr,th,td{
        border: 1px solid #d2d2d2;
        border-collapse:collapse;
        padding:7px 8px;
    }
    table tr th{
        background: #F4F4F4;
        font-size:15px;
    }
    table tr td{
        font-size:13px;
    }
    table{
        border-collapse:collapse;
    }
    .box-text p{
        line-height:10px;
    }
    .float-left{
        float:left;
    }
    .total-part{
        font-size:16px;
        line-height:12px;
    }
    .total-right p{
        padding-right:20px;
    }
</style>
<body>
<div class="head-title">
    <h1 class="text-center m-0 p-0">Invoice</h1>
</div>
<div class="add-detail mt-10">
    <div class="w-50 float-left mt-10">
        <p class="m-0 pt-5 text-bold w-100">Order Id - <span class="gray-color">{{$orders->order_number}}</span></p>
        <p class="m-0 pt-5 text-bold w-100">Order Date - <span class="gray-color">{{$orders->created_at->format("d/m/Y")}}</span></p>
        <p class="m-0 pt-5 text-bold w-100">Payment Method - <span class="gray-color">{{$orders->payment_method}}</span></p>
    </div>
    <div style="clear: both;"></div>
</div>
<div class="table-section bill-tbl w-100 mt-10">
    <table class="table w-100 mt-10">
        <tr>
            <th class="w-50">Billing Address</th>
            <th class="w-50">Shipping Address</th>
        </tr>
        <tr>
            <td>
                <div class="box-text">
                    <p>{{$orders->billing_address->street}}</p>
                    <p>{{$orders->billing_address->pincode}},</p>
                    <p>{{$orders->billing_address->city}},</p>
                    <p>{{$orders->billing_address?->state->name}}, {{$orders->billing_address?->country->name}}</p>
                    <p>Contact: ({{$orders->billing_address?->country_code}}) {{$orders->billing_address?->phone}}</p>
                </div>
            </td>
            <td>
                <div class="box-text">
                    <p>{{$orders->shipping_address->street}}</p>
                    <p>{{$orders->shipping_address->pincode}},</p>
                    <p>{{$orders->shipping_address->city}},</p>
                    <p>{{$orders->shipping_address?->state->name}}, {{$orders->shipping_address?->country->name}}</p>
                    <p>Contact: ({{$orders->shipping_address?->country_code}}) {{$orders->shipping_address?->phone}}</p>
                </div>
            </td>
        </tr>
    </table>
</div>
<div class="table-section bill-tbl w-100 mt-10">
    <table class="table w-100 mt-10">
        <tr>
            <th class="w-50">No</th>
            <th class="w-50">Product Name</th>
            <th class="w-50">Price</th>
            <th class="w-50">Qty</th>
            <th class="w-50">Subtotal</th>
            <th class="w-50">Shipping Cost</th>
            <th class="w-50">Grand Total</th>
        </tr>
        @foreach ($orders->products as $no => $product)
        <tr align="center">
            <td>{{++$no}}</td>
            <td>{{$product->name}}</td>
            <td>$ {{$product->pivot->single_price}}</td>
            <td>{{$product->pivot->quantity}}</td>
            <td>$ {{$product->pivot->subtotal}}</td>
            <td>$ {{$product->pivot->shipping_cost}}</td>
            <td>$ {{$product->pivot->subtotal + $product->pivot->shipping_cost + $product->pivot->tax}}</td>
        </tr>
        @endforeach
        <tr>
            <td colspan="7">
                <div class="total-part">
                    <div class="total-left w-85 float-left" align="right">
                        <p>Sub Total</p>
                        <p>Tax</p>
                        <p>Shipping</p>
                        <p>Total Payable</p>
                    </div>
                    <div class="total-right w-15 float-left text-bold" align="right">
                        <p>${{$orders->amount}}</p>
                        <p>${{$orders->tax_total}}</p>
                        <p>${{$orders->shipping_total}}</p>
                        <p>${{$orders->total}}</p>
                    </div>
                    <div style="clear: both;"></div>
                </div>
            </td>
        </tr>
    </table>
</div>
</html>
