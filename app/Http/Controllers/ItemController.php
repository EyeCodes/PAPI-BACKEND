<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ItemController extends Controller
{
   
    public function index()
    {
        return Item::all();
    }

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

    public function show($id)
    {
        return Item::findOrFail($id);
    }

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

    public function destroy($id)
    {
        Item::findOrFail($id)->delete();
        return response()->json(null, 204);
    }


    public function expiringSoon()
    {
        return Item::where('expiration_date', '<=', now()->addDays(30))
                  ->where('expiration_date', '>=', now())
                  ->orderBy('expiration_date')
                  ->get();
    }

    public function expired()
    {
        return Item::where('expiration_date', '<', now())->get();
    }
}