# Serializer

The MoneyBundle provides support for serializing `Money\Money` instances using either the [Symfony Serializer component](https://symfony.com/doc/current/components/serializer.html) or the [JMS Serializer](https://jmsyst.com/libs/serializer) (note, the `JMSSerializerBundle` must be installed to enable the serialization handler for the JMS serializer).

Below is an example of building a JSON response in a controller using the Symfony Serializer:

```php
<?php

namespace App\Controller\API;

use Money\Money;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class MoneyCalculatorController extends AbstractController
{
    /**
     * @Route("/api/money/add", name="app_api_add_money", methods={"POST"})
     */
    public function apiAddValues(Request $request): JsonResponse
    {
        $firstValue = $request->request->getInt('first_value');
        $secondValue = $request->request->getInt('second_value');

        $addedValues = Money::USD($firstValue)->add(Money::USD($secondValue));

        return $this->json($addedValues);
    }
}
```

Below is an example of how a `Money\Money` instance is serialized into JSON format:

```json
{
    "amount": "1000",
    "currency": "USD"
}
```
