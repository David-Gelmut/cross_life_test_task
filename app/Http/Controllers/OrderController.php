<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApprovedOrderRequest;
use App\Http\Requests\CreateOrderRequest;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function create(CreateOrderRequest $request): JsonResponse
    {
        try {
            $orderNumber = Str::uuid();
            $syncArray = [];
            $data = $request->validated();

            DB::beginTransaction();

            $order = Order::query()->create([
                'user_id' => $data['user_id'],
                'number' => $orderNumber,
                'date' => now(),
            ]);

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
                $product->quantity-=$quantity;

                $syncArray[$productId] = [
                    'price' => $price,
                    'quantity' => $quantity
                ];
                $product->save();
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

    public function approved(ApprovedOrderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $order = Order::query()->where('number', '=', $data['order_number'])->first();
        if (!$order) {
            return response()->json(['error' => 'Заказ не найден'], 404);
        }

        if ($order->status == 'paid' || $order->status == 'cancelled') {
            return response()->json(['error' => 'Заказ уже оплачен или отменен'], 400);
        }

        $totalSum = 0;
        foreach ($order->products as $product) {
            $totalSum += floatval($product->pivot->price)*$product->pivot->quantity;
        }
        // Проверка баланса пользователя:
        if ($order -> user -> balance < $totalSum){
            return response()->json(['error'=>'Недостаточно средств на балансе'],400);
        }

        try{

            DB::beginTransaction();
            // Списание средств:
            $_user= $order -> user;
            $_user -> balance -=$totalSum;
            if (!$_user -> save()){
                throw new \Exception("Ошибка списания средств");
            }

            // Обновление статуса заказа:
            $order -> status= 'approved';
            if (!$order -> save()){
                throw new \Exception("Ошибка обновления статуса заказа");
            }

            DB::commit();

            return response() -> json(['message'=>'Заказ подтвержден','total_amount'=>$totalSum]);

        } catch (\Exception$e){
            DB::rollBack();
            Log::error("Ошибка подтверждения заказа: ".$e->getMessage());
            return response()->json(['error'=>'Ошибка при подтверждении заказа: '.$e->getMessage()],500);
        }
    }
}
