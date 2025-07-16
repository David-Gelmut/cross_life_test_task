<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateOrderRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function create(CreateOrderRequest $request)
    {
        try {
            //имитируем авторизованного пользователя в нашем случае с  email = user@user.com
            $user = User::query()->where('email', '=', 'user@user.com')->first();
            $userId = $user->id;
            $data = $request->validated();

            DB::beginTransaction();
            $orderNumber = Str::uuid();
            // Создаем заказ:
            $order = Order::create([
                'user_id' => $userId,
                'number' => $orderNumber,
                'date' => now(),
            ]);
            $syncArray = [];
            foreach ($data['products'] as $product) {
                $productId = $product['product_id'];
                $quantity = $product['quantity'];
                $product = Product::query()->find($productId);
                $price = $product['price'];
                if (!$product) {
                    throw new \Exception("Товар с ID {$productId} не найден");
                }
                if ($product->quantity < intval($quantity)) {
                    throw new \Exception("Недостаточно товара '{$product->name}', доступно: {$product->quantity}");
                }
                $syncArray[$productId] = [
                    'price' => $price,
                    'quantity' => $quantity
                ];

            }
            $order->products()->sync($syncArray);
            DB::commit();
            return response()->json(['message' => "Заказ №{$orderNumber} успешно создан"], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Ошибка создания заказа: " . $e->getMessage());
            return response()->json(['error' => 'Ошибка при создании заказа: ' . $e->getMessage()], 400);
        }
    }

    public function approved()
    {
        return 'approved';
    }
}
