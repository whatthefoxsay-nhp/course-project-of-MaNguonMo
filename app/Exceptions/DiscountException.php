<?php

namespace App\Exceptions;

use RuntimeException;

/** Mã giảm giá không dùng được. Message được trả thẳng cho khách. */
class DiscountException extends RuntimeException
{
}
