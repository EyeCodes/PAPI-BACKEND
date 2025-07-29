<?php

namespace App\Http\Controllers;
use App\Models\LoyaltyProgram;
use Illuminate\Support\Facades\Validator;

use Illuminate\Http\Request;

class LoyaltyProgramController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return LoyaltyProgram::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies, id',
            'program_name' => 'required|string|max:255',
            'description'=> 'nullable|string',
            'program_type'=> 'required|string|max:255',
            'is_active' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'instructions' => 'nullable|string'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 422);
        }

        $program = LoyaltyProgram::create($request->all());

        return response()->json($program, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return LoyaltyProgram::findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
         $program = LoyaltyProgram::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'company_id' => 'sometimes|required|exists:companies,id',
            'program_name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'program_type' => 'sometimes|required|string|max:255',
            'is_active' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'instructions' => 'nullable|string'
        ]);

        if($validator->fails()){
            return response()->json($validator->error(), 422);
        }

        $program->update($request->all());

        return response()->json($program);
    }

   public function active(){
    return LoyaltyProgram::where("is_active", true)->get();
   }

   public function byCompany($companyId){
    return LoyaltyProgram::where('company_id', $companyId)->get();
   }
}
