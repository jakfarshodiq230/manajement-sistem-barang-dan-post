@extends('pdf.layout')

@section('title', 'Slip Gaji')

@section('document_title', 'SLIP GAJI KARYAWAN')
@section('document_number', 'SLIP/' . $payroll->period_year . '/' . str_pad($payroll->period_month, 2, '0', STR_PAD_LEFT) . '/' . str_pad($payroll->id, 4, '0', STR_PAD_LEFT))
@section('document_date', date('d F Y'))

@section('content')

<table style="width: 100%; margin-bottom: 20px; font-size: 12px;">
    <tr>
        <td style="width: 15%; font-weight: bold;">Nama Karyawan</td>
        <td style="width: 2%;">:</td>
        <td style="width: 33%;">{{ $payroll->employee->name ?? '-' }}</td>
        
        <td style="width: 15%; font-weight: bold;">Periode</td>
        <td style="width: 2%;">:</td>
        <td style="width: 33%;">{{ ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][$payroll->period_month - 1] }} {{ $payroll->period_year }}</td>
    </tr>
    <tr>
        <td style="font-weight: bold;">Jabatan</td>
        <td>:</td>
        <td>{{ $payroll->employee->position->name ?? '-' }}</td>
        
        <td style="font-weight: bold;">Total Kehadiran</td>
        <td>:</td>
        <td>{{ $payroll->total_attendance_days }} Hari</td>
    </tr>
</table>

<table class="content-table" style="width: 100%;">
    <thead>
        <tr>
            <th style="width: 50%;">PENERIMAAN</th>
            <th style="width: 50%;">POTONGAN</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <!-- PENERIMAAN -->
            <td style="vertical-align: top; padding: 0;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 5px 10px; border-bottom: 1px dashed #eee;">Gaji Pokok</td>
                        <td style="padding: 5px 10px; text-align: right; border-bottom: 1px dashed #eee;">Rp {{ number_format($payroll->base_salary, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 10px; border-bottom: 1px dashed #eee;">Tunjangan</td>
                        <td style="padding: 5px 10px; text-align: right; border-bottom: 1px dashed #eee;">Rp {{ number_format($payroll->allowances, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 10px;">Bonus / Lembur</td>
                        <td style="padding: 5px 10px; text-align: right;">Rp {{ number_format($payroll->bonus, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
            
            <!-- POTONGAN -->
            <td style="vertical-align: top; padding: 0;">
                <table style="width: 100%; border-collapse: collapse;">
                    @if(count($dynamicDeductions) > 0)
                        @foreach($dynamicDeductions as $deduction)
                        <tr>
                            <td style="padding: 5px 10px; border-bottom: 1px dashed #eee;">{{ $deduction['name'] }}</td>
                            <td style="padding: 5px 10px; text-align: right; border-bottom: 1px dashed #eee;">Rp {{ number_format($deduction['amount'], 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td style="padding: 5px 10px; border-bottom: 1px dashed #eee;">Total Potongan</td>
                            <td style="padding: 5px 10px; text-align: right; border-bottom: 1px dashed #eee;">Rp {{ number_format($payroll->deductions, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
        <tr>
            <!-- TOTAL PENERIMAAN -->
            <td style="background-color: #f9f9f9;">
                <table style="width: 100%;">
                    <tr>
                        <td style="padding: 5px 10px; font-weight: bold;">Total Penerimaan</td>
                        <td style="padding: 5px 10px; text-align: right; font-weight: bold;">Rp {{ number_format($payroll->base_salary + $payroll->allowances + $payroll->bonus, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
            <!-- TOTAL POTONGAN -->
            <td style="background-color: #f9f9f9;">
                <table style="width: 100%;">
                    <tr>
                        <td style="padding: 5px 10px; font-weight: bold;">Total Potongan</td>
                        <td style="padding: 5px 10px; text-align: right; font-weight: bold;">Rp {{ number_format($payroll->deductions, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2" style="background-color: #e3f2fd; text-align: center; font-size: 14px; font-weight: bold; padding: 12px;">
                TAKE HOME PAY : Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}
            </td>
        </tr>
    </tfoot>
</table>

<p style="font-size: 10px; color: #555; margin-top: 20px; font-style: italic;">
    * Slip gaji ini di-generate secara otomatis oleh sistem dan sah tanpa cap basah. <br>
    * Harap simpan slip gaji ini sebagai bukti penerimaan gaji yang sah.
</p>

@endsection
