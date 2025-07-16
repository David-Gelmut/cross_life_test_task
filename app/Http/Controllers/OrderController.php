<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApprovedOrderRequest;
use App\Http\Requests\CreateOrderRequest;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService)
    {
    }

    public function create(CreateOrderRequest $request): JsonResponse
    {
        try {

            $data = $request->validated();

            $order = $this->orderService->createOrder($data);

            return response()->json(['message' => "Заказ №{$order->number} успешно создан"], 201);
        } catch (\Exception $e) {
            Log::error("Ошибка создания заказа: " . $e->getMessage());
            return response()->json(['error' => 'Ошибка при создании заказа: ' . $e->getMessage()], 400);
        }
    }

    public function approved(ApprovedOrderRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $result = $this->orderService->approvedOrder($data['order_number']);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error("Ошибка подтверждения заказа: " . $e->getMessage());
            return response()->json(['error' => 'Ошибка при подтверждении заказа: ' . $e->getMessage()], 500);
        }
    }
}
