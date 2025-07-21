<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;

use function Pest\Laravel\delete;

class CompanyController extends Controller
{
    public function index(){
        $company = Company::get();
        if($company->count() > 0){
            return CompanyResource::collection($company);
        }
        else{
            return response()->json(['message'=> 'Empty'],200);
        }
    }
    public function store(CompanyRequest $request)
    {
        $validated = $request->validated();

        $company = new Company($validated);
        $company->company_name = $validated['company_name'];
        $company->display_name = $validated['display_name'];
        $company->business_type = $validated['business_type'];

        if ($request->hasFile('company_logo')) {
            $company_logo = $request->file('company_logo');
            $fileName = Str::uuid() . '.' . $company_logo->getClientOriginalExtension();
            $path = $company_logo->storeAs('company_logos', $fileName, 'public');
            $company->company_logo = $path;
        }

        $company->save();

        return new CompanyResource($company);
    }

    public function update(CompanyRequest $request, Company $company)
    {
        $validated = $request->validated();

        $company->fill($validated);

        if ($request->hasFile('company_logo')) {
            $company_logo = $request->file('company_logo');
            $fileName = Str::uuid() . '.' . $company_logo->getClientOriginalExtension();
            $path = $company_logo->storeAs('company_logos', $fileName, 'public');
            $company->company_logo = $path;
        }

        $company->save();

        return new CompanyResource($company);
    }


    public function show(Company $company): CompanyResource{
        return new CompanyResource($company);
    }
    public function destroy(Company $company){

            $company->delete();
            return response()->json([
                'message'=>'Data has been deleted successfully'
            ]);

    }


}
