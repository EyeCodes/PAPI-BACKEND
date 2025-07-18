<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::all();
        return CompanyResource::collection($companies);
    }

    public function store(CompanyRequest $request)
    {
        $data = $request->validated();

        $company = new Company($data);
        $company->display_name = $data['display_name'];

        if ($request->hasFile('company_logo')) {
            $file = $request->file('company_logo');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('company_logos', $filename, 'public');
            $company->company_logo = $path;
        }

        $company->save();
        return new CompanyResource($company);
    }

    public function update(CompanyRequest $request, Company $company)
    {
        $data = $request->validated();
        $company->fill($data);

        if ($request->hasFile('company_logo')) {
            $file = $request->file('company_logo');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('company_logos', $filename, 'public');
            $company->company_logo = $path;
        }

        $company->save();
        return new CompanyResource($company);
    }

    public function show(Company $company)
    {
        return new CompanyResource($company);
    }

    public function destroy(Company $company)
    {
        $company->delete();
        return response()->json(['message' => 'Data has been deleted successfully']);
    }
}