<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TreasuryTransactionResource extends JsonResource
{
    /**
     * تحويل المورد إلى مصفوفة (Array).
     */
    public function toArray(Request $request): array
    {
        return [
            // البيانات الأساسية
            'id'             => $this->id,
            'trx_no'         => $this->trx_no, // سيرجع كـ رقم/نص بناءً على التهيئة، وهو دقيق لكونه Decimal(18,0)
            'trx_date'       => $this->trx_date,

            // نوع السند
            'type'           => $this->type,
            'type_label'     => $this->type?->label(), // استخدام دالة الـ Enum لعرض النص العربي (سند قبض/صرف)

            // المعرفات (ضرورية لقوائم التعديل Dropdowns)
            'treasury_id'    => $this->treasury_id,
            'partner_id'     => $this->partner_id,

            // الأسماء المعروضة في الجداول (Tables)
            'treasury_name'  => $this->treasury->name ?? '---',
            'partner_name'   => $this->partner->name ?? '---',
            'created_by'     => $this->createdBy->name ?? '---',

            // المبالغ المالية (تحويل صريح لـ float)
            'amount'         => (float)$this->amount,

            'notes'          => $this->notes,
            'created_at'     => $this->created_at?->toDateTimeString(),
        ];
    }
}
