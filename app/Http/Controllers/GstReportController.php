<?php

namespace App\Http\Controllers;

use App\Helpers\GstCalculator;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * GstReportController
 *
 * Provides a GST summary report for a date range.
 * Each row represents one order-product line with full GST breakdown.
 *
 * Usage:
 *   GET /api/gst-report?from=2026-01-01&to=2026-01-31
 *
 * Response per line:
 *   order_number, order_date, product_name, hsn_code, gst_rate,
 *   quantity, selling_price_inclusive, taxable_value,
 *   cgst, sgst, total_gst, invoice_total
 */
class GstReportController extends Controller
{
    /**
     * Return a line-by-line GST report for the given date range.
     *
     * Query params:
     *   from  (string) Start date  YYYY-MM-DD  (default: start of current month)
     *   to    (string) End date    YYYY-MM-DD  (default: today)
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to   = $request->query('to',   now()->toDateString());

        $orders = Order::with([
                'products:id,name,hsn_code,tax_id',
                'products.tax:id,rate',
            ])
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->get(['id', 'order_number', 'created_at', 'total', 'shipping_total']);

        $lines        = [];
        $summaryTaxable = 0;
        $summaryGst     = 0;
        $summaryTotal   = 0;

        foreach ($orders as $order) {
            foreach ($order->products as $product) {
                $pivot = $product->pivot;

                // pivot->subtotal is the taxable value (ex-GST) for new orders.
                // pivot->tax     is the extracted GST amount.
                // For legacy orders where tax was not stored in pivot, fall back gracefully.
                $taxableValue  = (float) ($pivot->subtotal ?? 0);
                $gstAmount     = (float) ($pivot->tax      ?? 0);
                $quantity      = (int)   ($pivot->quantity  ?? 1);
                $unitPrice     = (float) ($pivot->single_price ?? 0);
                $shippingCost  = (float) ($pivot->shipping_cost ?? 0);

                // Inclusive line total (what the customer paid for this line)
                $inclusiveLine = $taxableValue + $gstAmount;

                // GST rate — derive from product's tax relationship for display
                $gstRate = (float) ($product->tax->rate ?? 0);

                // CGST / SGST split (intra-state: each is half of total GST)
                $cgst = round($gstAmount / 2, 2);
                $sgst = round($gstAmount / 2, 2);

                $lines[] = [
                    'order_number'           => $order->order_number,
                    'order_date'             => $order->created_at->toDateString(),
                    'product_name'           => $product->name,
                    'hsn_code'               => $product->hsn_code ?? '',
                    'gst_rate'               => $gstRate,
                    'quantity'               => $quantity,
                    'unit_price_inclusive'   => round($unitPrice, 2),
                    'selling_price_inclusive'=> round($inclusiveLine, 2),
                    'taxable_value'          => round($taxableValue, 2),
                    'cgst'                   => $cgst,
                    'sgst'                   => $sgst,
                    'total_gst'              => round($gstAmount, 2),
                    'shipping_cost'          => round($shippingCost, 2),
                    'line_total'             => round($inclusiveLine + $shippingCost, 2),
                ];

                $summaryTaxable += $taxableValue;
                $summaryGst     += $gstAmount;
                $summaryTotal   += $inclusiveLine + $shippingCost;
            }
        }

        return response()->json([
            'from'    => $from,
            'to'      => $to,
            'summary' => [
                'total_taxable_value' => round($summaryTaxable, 2),
                'total_cgst'          => round($summaryGst / 2, 2),
                'total_sgst'          => round($summaryGst / 2, 2),
                'total_gst'           => round($summaryGst, 2),
                'total_invoice_value' => round($summaryTotal, 2),
            ],
            'lines'   => $lines,
        ]);
    }
}