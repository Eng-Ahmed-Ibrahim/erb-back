<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاتورة {{ $invoice->code }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #faf2f2;
            margin: 0;
            padding: 0;
        }

        .container {
            padding: 20px;
            max-width: 80vw;
            margin: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 5px;

            border: 1px solid #ddd;
            text-align: right;
        }

        th {
            background-color: #eee;
        }
    </style>
</head>

<body>
    <div class="container">
        <table style="border: none; border-color: transparent">
            <tr>
                <td
                    style="font-size: 18px; width: 200px; font-weight: bold; background-color:@if ($invoice->status == 'pending') yellow @elseif($invoice->status == 'approved') green @else red @endif;">
                    @if ($invoice->status == 'pending')
                        معلقة
                    @elseif ($invoice->status == 'approved')
                        موافق عليها
                    @else
                        مرفوضة
                    @endif
                </td>
                <td>
                    @if ($invoice->type == 'in_coming')
                        <h4>
                            فاتورة واردة
                        </h4>
                    @elseif($invoice->type == 'out_going')
                        <h2>
                            اذن صــــــــــــرف
                        </h2>
                    @else
                        <h4>
                            فاتورة مرتجعة
                        </h4>
                    @endif
                </td>

                <td>
                    <img src="./{{ asset('assets/images/Dar_logo.svg') }}" alt="" style="width: 64px">
                </td>
            </tr>
        </table>
        <h3>تفاصيل الفاتورة</h3>
        <table>
            <tr>
                <td style="width: 150px">كـــــود الفاتــورة :</td>
                <td colspan="6" style="text-align: center">{{ $invoice->code }}</td>
            </tr>
            <tr>
                <td style="width: 150px">تـاريـــخ الفاتــورة :</td>
                <td colspan="6" style="text-align: center">{{ $invoice->invoice_date }}</td>
            </tr>
            <tr>
                <td>مــــــــــــن</td>
                <td>الاســـــم :</td>
                <td>{{ $invoice->fromDepartment->name ?? $invoice->supplier->name }}</td>
                <td>كــود :</td>
                <td>{{ $invoice->fromDepartment->code ?? 'ـــــــــــــــ' }}</td>
                <td>الهاتــــــف :</td>
                <td>{{ $invoice->fromDepartment->phone ?? $invoice->supplier->phone }}</td>
            </tr>
            <tr>
                <td>الــــــــــــــي</td>
                <td>الاســـــم :</td>
                <td>{{ $invoice->toDepartment->name ?? $invoice->customer->name }}</td>
                <td>كــود :</td>
                <td>{{ $invoice->toDepartment->code ?? 'ــــــــــــــــ' }}</td>
                <td>الهاتــــــف :</td>
                <td>{{ $invoice->toDepartment->phone ?? $invoice->supplier->phone }}</td>
            </tr>
        </table>


        <h3>محـــــتويات الفاتـــــــورة</h3>
        <table>
            <tr>
                <th>رقم العنصر</th>
                <th>اسم العنصر</th>
                <th>سعر العنصر الواحد</th>
                <th>الكمية</th>
                <th>تاريخ انتهاء الصلاحية</th>
                <th>السعر الكلي</th>
            </tr>
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
            <tr>
                <td colspan="5">السعر الكلي</td>
                <td>{{ $invoice->invoice_price }}</td>
            </tr>
        </table>

        <h2>تفاصيل الدفع</h2>
        <table>
            <tr>
                <td>سعر الفاتورة</td>
                <td>{{ $invoice->invoice_price }}</td>
            </tr>
            <tr>
                <td>الخصم</td>
                <td>{{ $invoice->discount }}</td>
            </tr>
            <tr>
                <td>الضريبة المضافة</td>
                <td>{{ $invoice->tax }}</td>
            </tr>
            <tr>
                <td>السعر النهائي</td>
                <td>{{ $invoice->total_price }}</td>
            </tr>
        </table>

        <h3>ملاحظات</h3>
        <p>{{ $invoice->note }}</p>
    </div>
</body>

</html>
