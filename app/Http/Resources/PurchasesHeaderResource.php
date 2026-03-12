<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchasesHeaderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // --- 1. البيانات الأساسية ---
            'id'                  => $this->id,
            'trx_no'              => $this->trx_no,
            'code'                => $this->trx_no, // الجدول يتوقع code
            'invoice_date'        => $this->invoice_date,
            'trx_date'            => $this->invoice_date, // الفورم والجدول يتوقعان trx_date
            'supplier_invoice_no' => $this->supplier_invoice_no,
            'status'              => $this->status,
            'notes'               => $this->notes,

            // --- 2. الـ IDs الضرورية لفورم التعديل ---
            'partner_id'          => $this->partner_id,
            'warehouse_id'        => $this->warehouse_id,
            'treasury_id'         => $this->treasury_id,

            // --- 3. كائنات مجهزة لجدول العرض ---
            'partner'             => ['name' => $this->partner->name ?? '---'],
            'warehouse'           => ['name' => $this->warehouse->name ?? '---'],
            'created_by'          => $this->createdBy->name ?? '',

            // إضافة متغير is_approved للجدول بناءً على حالة الـ enum
            'is_approved'         => $this->status?->value === 'approved' || $this->status === 'approved',

            // --- 4. الأرقام المالية ---
            'total_amount'        => (float)$this->total_amount,
            'discount_amount'     => (float)$this->discount_amount,
            'tax_amount'          => (float)$this->tax_amount,
            'net_amount'          => (float)$this->net_amount,
            'grand_total'         => (float)$this->net_amount, // الجدول يتوقع grand_total
            'paid_amount'         => (float)$this->paid_amount,
            'remaining_amount'    => (float)$this->remaining_amount,

            // --- 5. تفاصيل الأصناف (الفورم يتوقع مصفوفة باسم items) ---
            'items' => $this->details->map(function ($detail) {
                return [
                    'id'              => $detail->id,
                    'item_id'         => $detail->item_id, // ضروري للقائمة المنسدلة
                    'unit_id'         => $detail->unit_id, // ضروري للقائمة المنسدلة
                    'item_name'       => $detail->item->name ?? '',
                    'unit_name'       => $detail->unit->name ?? '',
                    'qty'             => (float)$detail->qty,
                    'price'           => (float)$detail->unit_cost, // تعيين unit_cost إلى price كما يتوقعه الفورم
                    'total_row'       => (float)$detail->total_row,
                    'production_date' => $detail->production_date,
                    'expiry_date'     => $detail->expiry_date,
                ];
            }),

            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
