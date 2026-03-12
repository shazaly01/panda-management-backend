<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryReportController extends Controller
{
    public function stockCard(Request $request)
    {
        $warehouseId = $request->warehouse_id;
        $itemId = $request->item_id;

        // 1. استعلام المشتريات (الوارد)
        $purchases = DB::table('purchases_details as d')
            ->join('purchases_headers as h', 'd.purchases_header_id', '=', 'h.id')
            ->select(
                'h.invoice_date as date',
                'h.trx_no as doc_no',
                DB::raw("'purchase' as type"),
                DB::raw('d.qty * d.unit_factor as in_qty'),
                DB::raw('0 as out_qty')
            )
            ->where('h.warehouse_id', $warehouseId)
            ->where('d.item_id', $itemId);

        // 2. استعلام المبيعات (الصادر)
        $sales = DB::table('sales_details as d')
            ->join('sales_headers as h', 'd.sales_header_id', '=', 'h.id')
            ->select(
                'h.invoice_date as date',
                'h.trx_no as doc_no',
                DB::raw("h.type as type"), // sale or return
                DB::raw('CASE WHEN h.type = "return" THEN d.qty * d.unit_factor ELSE 0 END as in_qty'),
                DB::raw('CASE WHEN h.type = "sale" THEN d.qty * d.unit_factor ELSE 0 END as out_qty')
            )
            ->where('h.warehouse_id', $warehouseId)
            ->where('d.item_id', $itemId);

        // 3. استعلام المخزون (تحويلات وتسويات)
        $inventory = DB::table('inventory_details as d')
            ->join('inventory_headers as h', 'd.inventory_header_id', '=', 'h.id')
            ->select(
                'h.trx_date as date',
                'h.trx_no as doc_no',
                DB::raw('h.trx_type as type'),
                // الوارد: إذا كان تحويل إلينا أو تسوية داخلة
                DB::raw('CASE WHEN h.to_warehouse_id = ' . $warehouseId . ' OR h.trx_type = "adjustment_in" THEN d.qty * d.unit_factor ELSE 0 END as in_qty'),
                // الصادر: إذا كان تحويل منا أو تسوية خارجة
                DB::raw('CASE WHEN h.from_warehouse_id = ' . $warehouseId . ' OR h.trx_type = "adjustment_out" OR h.trx_type = "transfer" THEN d.qty * d.unit_factor ELSE 0 END as out_qty')
            )
            ->where(function($q) use ($warehouseId) {
                $q->where('h.warehouse_id', $warehouseId)
                  ->orWhere('h.from_warehouse_id', $warehouseId)
                  ->orWhere('h.to_warehouse_id', $warehouseId);
            })
            ->where('d.item_id', $itemId);

        // دمج الجميع وترتيبهم
        $allTransactions = $purchases->unionAll($sales)->unionAll($inventory)
            ->orderBy('date')
            ->get();

        // 4. حساب الرصيد التراكمي (Running Balance)
        $balance = 0;
        $reportData = $allTransactions->map(function ($trx) use (&$balance) {
            $balance += ($trx->in_qty - $trx->out_qty);
            $trx->balance = $balance;
            return $trx;
        });

        return response()->json(['data' => $reportData]);
    }
}
