<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
// لا تنسَ استدعاء موديل السندات للبحث فيه
use App\Models\TreasuryTransaction;

class SalesHeaderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // 1. حساب المبلغ المدفوع رياضياً (الصافي - المتبقي)
        $calculatedPaidAmount = (float)($this->net_amount - $this->remaining_amount);

        // 2. جلب الخزينة التي تم الإيداع فيها من واقع السند المالي المرتبط بالفاتورة
        $actualTreasuryId = TreasuryTransaction::where('sales_header_id', $this->id)->value('treasury_id') ?? $this->treasury_id;

        return [
            'id'              => $this->id,
            'trx_no'          => $this->trx_no,
            'invoice_date'    => $this->invoice_date,
            'status'          => $this->status,
            'notes'           => $this->notes,

            'partner_id'      => $this->partner_id,
            'warehouse_id'    => $this->warehouse_id,
            'designer_id'     => $this->designer_id,

            // --- [تم التعديل هنا] إرسال الخزينة الصحيحة المستخرجة ---
            'treasury_id'     => $actualTreasuryId,

            'partner_name'    => $this->partner->name ?? '',
            'warehouse_name'  => $this->warehouse->name ?? '',
            'created_by'      => $this->createdBy->name ?? '',

            'walk_in_customer_name' => $this->walk_in_customer_name,
            'shipping_destination'  => $this->shipping_destination,
            'designer_name'         => $this->designer->name ?? '',

            // الأرقام المالية
            'total_amount'      => (float)$this->total_amount,
            'discount_amount'   => (float)$this->discount_amount,
            'tax_amount'        => (float)$this->tax_amount,
            'net_amount'        => (float)$this->net_amount,
            'remaining_amount'  => (float)$this->remaining_amount,

            // --- [تم التعديل هنا] إرسال المبلغ المدفوع المحسوب ---
            'paid_amount'       => $calculatedPaidAmount,
            'total_area'        => $this->total_area !== null ? (float)$this->total_area : 0,
            'design_commission' => $this->design_commission !== null ? (float)$this->design_commission : null,

            // تفاصيل الأصناف
            'details' => $this->details->map(function ($detail) {
                return [
                    'id'         => $detail->id,
                    'item_id'    => $detail->item_id,
                    'unit_id'    => $detail->unit_id,
                    'item_name'  => $detail->item->name ?? '',
                    'unit_name'  => $detail->unit->name ?? '',
                    'qty'        => (float)$detail->qty,
                    'price'      => (float)$detail->price,
                    'total_row'  => (float)$detail->total_row,
                    'description'=> $detail->description,
                    'length'     => $detail->length !== null ? (float)$detail->length : null,
                    'width'      => $detail->width !== null ? (float)$detail->width : null,
                    'area'       => $detail->area !== null ? (float)$detail->area : null,
                ];
            }),

            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
