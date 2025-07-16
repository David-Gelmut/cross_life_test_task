<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function createOrder(array $data): Order
    {
        $orderNumber = Str::uuid();
        $syncArray = [];

        return DB::transaction(function () use ($data, $orderNumber, &$syncArray) {
            // Создаем заказ
            $order = Order::query()->create([
                'user_id' => $data['user_id'],
                'number' => $orderNumber,
                'date' => now(),
            ]);

            // Обработка продуктов
            foreach ($data['products'] as $productData) {
                $productId = $productData['product_id'];
                $quantity = intval($productData['quantity']);

                $product = Product::query()->find($productId);
                if (!$product) {
                    throw new \Exception("Товар с ID {$productId} не найден");
                }

                if ($product->quantity < $quantity) {
                    throw new \Exception("Недостаточно товара '{$product->name}', доступно: {$product->quantity}");
                }

                // Обновляем количество товара
                $product->quantity -= $quantity;
                $product->save();

                // Формируем массив для синхронизации
                $syncArray[$productId] = [
                    'price' => $product->price,
                    'quantity' => $quantity,
                ];
            }

            // Связываем продукты с заказом
            $order->products()->sync($syncArray);

            return $order;
        });
    }

    public function approvedOrder(string $orderNumber): array
    {
        return DB::transaction(function () use ($orderNumber) {
            $order = Order::query()->where('number', $orderNumber)->with('products')->first();

            if (!$order) {
                throw new Exception('Заказ не найден');
            }

            if (in_array($order->status, ['paid', 'cancelled'])) {
                throw new Exception('Заказ уже оплачен или отменен');
            }

            // Расчет общей суммы заказа
            $totalSum = 0;
            foreach ($order->products as $product) {
                $totalSum += floatval($product->pivot->price) * $product->pivot->quantity;
            }

            // Проверка баланса пользователя
            $user = $order->user;
            if ($user->balance < $totalSum) {
                throw new Exception('Недостаточно средств на балансе');
            }

            // Списание средств и обновление статуса заказа
            $user->balance -= $totalSum;
            if (!$user->save()) {
                throw new Exception('Ошибка списания средств');
            }

            $order->status = 'approved';
            if (!$order->save()) {
                throw new Exception('Ошибка обновления статуса заказа');
            }

            return [
                'message' => 'Заказ подтвержден',
                'total_amount' => $totalSum,
            ];
        });
    }
}
