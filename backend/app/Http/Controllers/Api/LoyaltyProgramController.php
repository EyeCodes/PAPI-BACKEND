<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoyaltyProgramRequest;
use App\Http\Resources\LoyaltyProgramResource;
use App\Models\LoyaltyProgram;
use Illuminate\Http\Request;

class LoyaltyProgramController extends Controller
{
    public function index()
    {
        $programs = LoyaltyProgram::all();

        return response()->json($programs);
    }
    public function show($id)
    {
        $program = LoyaltyProgram::find($id);

        if (!$program) {
            return response()->json(['message' => 'Loyalty program not found'], 404);
        }

        return response()->json($program);
    }

    public function store(LoyaltyProgramRequest $request)
    {
        $program = LoyaltyProgram::create($request->validated());

        return response()->json([
            'message' => 'Loyalty program created successfully',
            'data' => $program
        ], 201);
    }

    public function update(LoyaltyProgramRequest $request, $id)
    {
        $program = LoyaltyProgram::find($id);
        $program = LoyaltyProgram::update($request->validated());

    return new LoyaltyProgramResource($program);
    }

    public function destroy($id)
    {
        $program = LoyaltyProgram::find($id);

        if (!$program) {
            return response()->json(['message' => 'Loyalty program not found'], 404);
        }

        $program->delete();

        return response()->json(['message' => 'Loyalty program deleted successfully']);
    }
}
