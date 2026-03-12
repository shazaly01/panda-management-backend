<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // --- 1. البيانات الأساسية المشتركة ---
            'id'             => $this->id,
            'trx_no'         => $this->trx_no,
            'code'           => $this->trx_no, // تحسباً إذا كان الجدول يتوقع متغير باسم code
            'trx_date'       => $this->trx_date,
            'notes'          => $this->notes,

            // إرسال القيم الأصلية والنصية للـ Enums
            'trx_type'       => $this->trx_type?->value ?? $this->trx_type, // للنموذج (Form)
            'trx_type_label' => $this->trx_type?->label() ?? '', // للجدول (Table)
            'status'         => $this->status,
            'is_approved'    => $this->status?->value === 'approved' || $this->status === 'approved',

            // --- 2. الـ IDs الضرورية لفورم التعديل (Dropdowns) ---
            'from_warehouse_id' => $this->from_warehouse_id,
            'to_warehouse_id'   => $this->to_warehouse_id,

            // --- 3. كائنات مجهزة لجدول العرض ---
            'from_warehouse' => ['name' => $this->fromWarehouse->name ?? '---'],
            'to_warehouse'   => ['name' => $this->toWarehouse->name ?? '---'],
            'created_by'     => $this->createdBy->name ?? '',

            // --- 4. تفاصيل الأصناف (نسميها items ليطابق الفرونت إند) ---
            'items' => $this->details->map(function($detail) {
                return [
                    'id'          => $detail->id,
                    'item_id'     => $detail->item_id, // ضروري للقائمة المنسدلة
                    'unit_id'     => $detail->unit_id, // ضروري للقائمة المنسدلة
                    'item_name'   => $detail->item->name ?? '',
                    'unit_name'   => $detail->unit->name ?? '',
                    'qty'         => (float)$detail->qty,
                    'unit_factor' => (float)$detail->unit_factor,
                ];
            }),
        ];
    }
}
