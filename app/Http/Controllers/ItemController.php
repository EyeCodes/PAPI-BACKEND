<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ItemController extends Controller
{
    /**
     * Display a listing of the items.
     */
    public function index()
    {
        return Item::all();
    }

    /**
     * Store a newly created item.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'sku' => 'nullable|string|unique:items',
            'barcode' => 'nullable|string|unique:items',
            'quantity' => 'integer|default:0',
            'image_url' => 'nullable|url',
            'expiration_date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $item = Item::create($request->all());

        return response()->json($item, 201);
    }

    /**
     * Display the specified item.
     */
    public function show($id)
    {
        return Item::findOrFail($id);
    }

    /**
     * Update the specified item.
     */
    public function update(Request $request, $id)
    {
        $item = Item::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'sku' => 'nullable|string|unique:items,sku,'.$item->id,
            'barcode' => 'nullable|string|unique:items,barcode,'.$item->id,
            'quantity' => 'integer',
            'image_url' => 'nullable|url',
            'expiration_date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $item->update($request->all());

        return response()->json($item);
    }

    /**
     * Remove the specified item.
     */
    public function destroy($id)
    {
        Item::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    /**
     * Get items expiring soon (within 30 days)
     */
    public function expiringSoon()
    {
        return Item::where('expiration_date', '<=', now()->addDays(30))
                  ->where('expiration_date', '>=', now())
                  ->orderBy('expiration_date')
                  ->get();
    }

    /**
     * Get expired items
     */
    public function expired()
    {
        return Item::where('expiration_date', '<', now())->get();
    }
}