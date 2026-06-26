<!DOCTYPE html>
<html lang="en" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الفاتورة {{ $invoice->code }} </title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            outline: none;
            background-color: #faf2f2
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        main {
            padding: 20px;
        }

        .invoice-info {
            display: flex;
            justify-content: space-between;

        }

        .invoice-info-item {
            width: 30%;
        }

        .invoice-info-item h2 {
            font-size: 18px;
            margin-bottom: 10px;
        }

        .invoice-info-item p {
            font-size: 16px;
            margin-bottom: 10px;
        }

        .invoice-info-item ul {
            list-style: none;
            padding: 0;
        }

        .invoice-info-item ul li {
            margin-bottom: 10px;
            font-size: 16px;
        }



        .invoice-info,
        .invoice-items,
        .invoice-notes {
            margin-bottom: 10px;
            border: 1px solid #ddd;
            padding: 20px;
        }

        h1, h2 {
            margin-top: 0;
        }

        .invoice-items table {
            width: 100%;
            border-collapse: collapse;
        }

        .invoice-items table th,
        .invoice-items table td {
            padding: 5px;
            font-size: 16px;
            border: 1px solid #ddd;
            text-align: center;
        }

        .invoice-items table thead th {
            background-color: #eee;
        }


        .invoice-total {
            text-align: right;
        }

        .headers {
            display: flex;
            justify-content: space-between;
        }

        .headers-wrapper {
            max-height: 5rem;
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .header-img {
            width: 64px
        }

        .header-img img {
            width: 100%;
        }


        .status-red {
            padding: 5px 10px;
            border: 1px solid #ddd;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: red
        }

        .status-red p {
            padding: 3px;
            font-size: 16px;
            font-weight: 600;
            background-color: red;
            color: white;
        }

        .status-yellow {
            padding: 5px 10px;
            border: 1px solid #ddd;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgb(233, 233, 87)
        }

        .status-yellow p {
            padding: 3px;
            font-size: 16px;
            font-weight: 600;
            background-color: rgb(233, 233, 87);
            color: rgb(0, 0, 0);
        }

        .status-green {
            padding: 5px 10px;
            border: 1px solid #ddd;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: green
        }

        .status-green p {
            padding: 3px;
            font-size: 16px;
            font-weight: 600;
            background-color: green;
            color: white;
        }

        .main-title {
            padding: 5px 10px;
            /* border: 1px solid #ddd; */
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .main-title p {
            padding: 3px;
            font-size: 2rem;
            font-weight: 600;
        }

        .invoice-notes {
            display: flex;
            justify-content: space-between
        }

        .invoice-notes h2 {
            margin-bottom: 10px;
            padding-right: 8px
        }

        .invoice-notes p {
            padding: 8px;
            font-size: 16px;
        }

        .border {
            border-right: #000 1px solid;
        }

        .invoice-notes-section {
            width: 30rem;
            overflow-wrap: break-word;

        }

        .invoice-items table tfoot td {
            font-weight: bold;
            background-color: #c5f7f3;

        }
    </style>
</head>

<body>
    <main>
        <div class="headers-wrapper">

            @if ($invoice->status == 'pending')
                <div class="status-yellow">
                    <p>معـــــــــــلـق</p>
                </div>
            @endif
            @if ($invoice->status == 'rejected')
                <div class="status-red">
                    <p>فــــاتورة مـــرفوضه</p>
                </div>
            @endif
            @if ($invoice->status == 'approved')
                <div class="status-green">
                    <p>فــــاتورة مـــقبوله</p>
                </div>
            @endif

            <div class="main-title">
                @if ($invoice->type == 'out_going')
                    <p>اذن صــــــــرف</p>
                @endif
                @if ($invoice->type == 'in_coming')
                    <p>فاتورة توريد </p>
                @endif
                @if ($invoice->type == 'returned')
                    <p>فاتورة مرتجع </p>
                @endif
            </div>
            <div class="header-img">
                <img src="./{{ asset('assets/images/Dar_logo.svg') }}" alt="">
            </div>

        </div>

        </div>
        <div class="invoice-info">
            <div class="invoice-info-item">
                <h2>تـفاصيل الفاتــورة :</h2>
                <p>كـــــود الفاتــورة : {{ $invoice->code }}</p>
                <p>تـاريـــخ الفاتــورة : {{ $invoice->invoice_date }}</p>
            </div>
            <div class="invoice-info-item">
                <h2>مـــــــــن</h2>
                <ul>
                    <li>الاســـــم : {{ $invoice->fromDepartment->name ?? $invoice->supplier->name }}</li>
                    <li>كــود : {{ $invoice->fromDepartment->code ?? 'ـــــــــــــــ' }}</li>
                    <li>الهاتــــــف : {{ $invoice->fromDepartment->phone ?? $invoice->supplier->phone }}</li>
                </ul>
            </div>
            <div class="invoice-info-item">
                <h2>الـــــــــي</h2>
                <ul>
                    <li>الاســـــم : {{ $invoice->toDepartment->name ?? $invoice->customer->name }}</li>
                    <li>كــود : {{ $invoice->toDepartment->code ?? 'ــــــــــــــــ' }}</li>
                    <li>الهاتــــــف : {{ $invoice->toDepartment->phone ?? $invoice->supplier->phone }}</li>
                </ul>
            </div>
        </div>
        <div class="invoice-items">
            <h2>محــــــتويات الـفــاتورة</h2>
            <table>
                <thead>
                    <tr>
                        <th class="text-center" style="width:10%">رقم العنصر</th>
                        <th class="text-center" style="width:20%">اسم العنصر</th>
                        <th class="text-center" style="width:20%">سعر العنصر الواحد </th>
                        <th class="text-right" style="width:15%">الكمية</th>
                        <th class="text-right" style="width:20%">تاريخ انتهاء الصلاحية</th>
                        <th class="text-right" style="width:15%">السعر الكلي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->recipes as $recipe)
                        <tr>
                            <td>{{ $loop->index + 1 }}</td>
                            <td>{{ $recipe->name }}</td>
                            <td>{{ $recipe->pivot->price }}</td>
                            <td>{{ $recipe->pivot->quantity }}</td>
                            <td>{{ $recipe->pivot->expire_date }}</td>
                            <td>{{ $recipe->pivot->total_price }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4"></td>
                        <td>السعر الكلي</td>
                        <td>{{ $invoice->invoice_price }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="invoice-notes">
            <div class="invoice-notes-section">
                <h2>تفــــــاصيـــل الدفــــع</h2>
                <div class="invoice-notes-item">
                    <p>سعر الفاتورة: {{ $invoice->invoice_price }}
                    </p>
                    <p>الخصم : {{ $invoice->discount }}
                    </p>
                    <p>الضريبة المضافة : {{ $invoice->tax }}
                    </p>
                    <p>السعر النهائي : {{ $invoice->total_price }}
                    </p>
                </div>
            </div>

            <div class="invoice-notes-section">
                <h2>ملاحظات</h2>
                <p>{{ $invoice->note }}</p>
            </div>
        </div>
    </main>

    <script>
        
    </script>
</body>

</html>
