<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transactions;
use App\Helpers\ResponseFormatter;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function all(Request $request)
    {
        $id = $request->input('id');
        $limit = $request->input('limit', 10);
        $status = $request->input('status');

        if ($id) {
           $transactions = Transactions::with(['detail.product'])->find($id);
            return ResponseFormatter::success(
                $transactions, 'Data list transactions berhasil diambil'
            );
        }else{
            return ResponseFormatter::error(
                null, 'Data transactions tidak ada', 404
            );
        }

        $transactions = Transactions::with(['detail.product'])->where('users_id', Auth::user()->id);

        if ($status) {
            $transactions->where('status', $status);
        }

        return ResponseFormatter::success(
            $transactions->paginate($limit),
            'Data list transactions berhasil diambil'
        );
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'exists:product,id',
            'total_price' => 'required',
            'shipping_price' => 'required',
            'status' => 'required|in:PENDING,SUCCESS,CANCELLED,FAILED,SHIPPING,SHIPPED',
        ]);

        $transactions = Transactions::create([
            'users_id' => Auth::user()->id,
            'address' => $request->address,
            'total_price' => $request->total_price,
            'shipping_price' => $request->shipping_price,
            'status' => $request->status,
        ]);

        foreach ($request->items as $product) {
            Transactions::create([
                'users_id' => Auth::user()->id,
                'product_id' => $product['product_id'],
                'transactions_id' => $transactions->id,
                'quantity' => $product['quantity']
            ]);
        }
        return ResponseFormatter::success($transactions->load('items.product'), 'Transaksi berhasil');
    }
}
