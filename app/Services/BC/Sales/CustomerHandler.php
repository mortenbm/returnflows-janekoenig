<?php declare(strict_types=1);

namespace App\Services\BC\Sales;

use App\Actions\BC\GetCustomerAction;
use App\Enums\CountryList;
use App\Models\Order;

class CustomerHandler
{
    public const string DEFAULT_CUSTOMER = '10000';

    public function __construct(
        protected GetCustomerAction $getCustomerAction
    ) {
    }

    public function handle(Order $order): array
    {
       // $customer = $this->findCustomerByEmail($order->email);
       // if ($customer === null) {
            $countryCode = optional($order->shippingAddress()->first())->country;
            $customer = $this->findCustomerByCountryAndType($countryCode);
        //}

        return [
            'customer_number' => !empty($customer['No']) ? $customer['No'] : self::DEFAULT_CUSTOMER,
            'customer_type' => $customer['Customer_Type'],
        ];
    }

    protected function findCustomerByEmail(string $email): ?array
    {
        $params = [
            '$filter' => sprintf('EMail eq \'%s\'', $email)
        ];
        $customers = $this->getCustomerAction->handle($params);
        return $customers['value'][0] ?? null;
    }

    protected function findCustomerByCountryAndType(?string $country): ?array
    {
        if ($country === null) {
            return null;
        }

        $countryCode = CountryList::tryFrom($country)?->name;
        if ($countryCode === null) {
            $countryCode = 'DK';
        }
        $params = [
            '$filter' => sprintf('Customer_Type eq \'B2C\' and Country_Region_Code eq \'%s\'', $countryCode)
        ];
        $customers = $this->getCustomerAction->handle($params);
        return $customers['value'][0] ?? null;
    }
}
