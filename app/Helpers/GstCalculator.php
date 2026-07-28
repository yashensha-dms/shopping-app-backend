<?php

namespace App\Helpers;

/**
 * GstCalculator
 *
 * Single source of truth for Indian GST back-calculation from GST-inclusive prices.
 * Grabzo stores selling prices inclusive of GST. Never add tax on top of an inclusive price.
 *
 * Formulas (from spec):
 *   Taxable Value = Selling Price * 100 / (100 + GST Rate)
 *   GST Amount    = Selling Price - Taxable Value
 *
 * Example @ Rs.110 / 5% GST:
 *   Taxable Value = 110 * 100 / 105 = 104.76
 *   GST Amount    = 5.24, CGST = 2.62, SGST = 2.62
 */
class GstCalculator
{
    /** Taxable value (ex-GST) extracted from a GST-inclusive price. */
    public static function taxableValue(float $inclusivePrice, float $gstRate): float
    {
        if ($gstRate <= 0) {
            return round($inclusivePrice, 2);
        }
        return round(($inclusivePrice * 100) / (100 + $gstRate), 2);
    }

    /** GST amount embedded in a GST-inclusive price. */
    public static function gstAmount(float $inclusivePrice, float $gstRate): float
    {
        if ($gstRate <= 0) {
            return 0.0;
        }
        return round($inclusivePrice - self::taxableValue($inclusivePrice, $gstRate), 2);
    }

    /** CGST = half of total GST (intra-state). */
    public static function cgst(float $inclusivePrice, float $gstRate): float
    {
        return round(self::gstAmount($inclusivePrice, $gstRate) / 2, 2);
    }

    /** SGST = half of total GST (intra-state). */
    public static function sgst(float $inclusivePrice, float $gstRate): float
    {
        return round(self::gstAmount($inclusivePrice, $gstRate) / 2, 2);
    }

    /** IGST = full GST amount (inter-state). */
    public static function igst(float $inclusivePrice, float $gstRate): float
    {
        return self::gstAmount($inclusivePrice, $gstRate);
    }

    /**
     * Full breakdown array for a GST-inclusive line total.
     *
     * @return array{inclusive_price:float, gst_rate:float, taxable_value:float,
     *               gst_amount:float, cgst:float, sgst:float, igst:float}
     */
    public static function breakdown(float $inclusivePrice, float $gstRate): array
    {
        $taxableValue = self::taxableValue($inclusivePrice, $gstRate);
        $gstAmount    = self::gstAmount($inclusivePrice, $gstRate);

        return [
            'inclusive_price' => round($inclusivePrice, 2),
            'gst_rate'        => $gstRate,
            'taxable_value'   => $taxableValue,
            'gst_amount'      => $gstAmount,
            'cgst'            => round($gstAmount / 2, 2),
            'sgst'            => round($gstAmount / 2, 2),
            'igst'            => $gstAmount,
        ];
    }
}