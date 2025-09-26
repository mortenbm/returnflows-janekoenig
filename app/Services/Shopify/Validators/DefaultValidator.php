<?php declare(strict_types=1);

namespace App\Services\Shopify\Validators;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

abstract class DefaultValidator
{
    public static function validate(array $data): void
    {
        $data = static::prepare($data);
        $validator = Validator::make($data, static::getRules());
        if ($validator->fails()) {
            Log::channel('shopify_graph')->info(json_encode($data));
            throw new ValidationException($validator);
        }
    }

    protected static function prepare(array $data): array
    {
        return $data;
    }

    abstract protected static function getRules(): array;
}
