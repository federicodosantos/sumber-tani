<?php

namespace Tests\Unit;

use App\Services\DecimalMathService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DecimalMathServiceTest extends TestCase
{
    private DecimalMathService $math;

    protected function setUp(): void
    {
        parent::setUp();

        $this->math = new DecimalMathService;
    }

    public function test_add_avoids_float_precision_errors(): void
    {
        $this->assertSame('0.300', $this->math->add('0.1', '0.2'));
    }

    public function test_subtract_returns_scaled_result(): void
    {
        $this->assertSame('5.000', $this->math->subtract('10.000', '5.000'));
    }

    public function test_multiply_scales_to_three_decimals(): void
    {
        $this->assertSame('20.250', $this->math->multiply('10.125', '2'));
    }

    public function test_multiply_price_by_decimal_quantity(): void
    {
        $this->assertSame('12.500', $this->math->multiply('10.000', '1.250'));
    }

    public function test_divide_returns_scaled_result(): void
    {
        $this->assertSame('5.000', $this->math->divide('10.000', '2'));
    }

    public function test_divide_by_zero_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->math->divide('1', '0');
    }

    public function test_payment_of_full_debt_leaves_zero_remainder(): void
    {
        $debt = '100.125';
        $paid = '100.125';

        $this->assertSame('0.000', $this->math->subtract($debt, $paid));
        $this->assertTrue($this->math->isZero($this->math->subtract($debt, $paid)));
    }

    public function test_compare_three_way(): void
    {
        $this->assertSame(0, $this->math->compare('100.125', '100.125'));
        $this->assertSame(-1, $this->math->compare('99.999', '100.125'));
        $this->assertSame(1, $this->math->compare('100.126', '100.125'));
    }

    public function test_is_zero_negative_positive(): void
    {
        $this->assertTrue($this->math->isZero('0.000'));
        $this->assertTrue($this->math->isNegative('-0.001'));
        $this->assertTrue($this->math->isPositive('0.001'));
    }

    public function test_round_half_away_from_zero(): void
    {
        $this->assertSame('1.235', $this->math->round('1.2345'));
        $this->assertSame('1.234', $this->math->round('1.2344'));
        $this->assertSame('-1.235', $this->math->round('-1.2345'));
        $this->assertSame('10.125', $this->math->round('10.1250'));
    }

    public function test_round_to_integer(): void
    {
        $this->assertSame('5', $this->math->round('5.4', 0));
        $this->assertSame('6', $this->math->round('5.5', 0));
        $this->assertSame('0', $this->math->round('-0.4', 0));
    }

    public function test_repeated_additions_do_not_accumulate_error(): void
    {
        $sum = '0.000';

        for ($i = 0; $i < 10; $i++) {
            $sum = $this->math->add($sum, '0.1');
        }

        $this->assertSame('1.000', $sum);
    }
}
