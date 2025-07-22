<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompanyResource;
use App\Models\Companies;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use function Pest\Laravel\delete;

class CompanyController extends Controller
{
    public function index(){
        $company = Companies::get();
        if($company->count() > 0){
            return CompanyResource::collection($company);
        }
        else{
            return response()->json(['message'=> 'Empty'],200);
        }
    }
    public function store(Request $request){
        
        $validate = Validator::make($request->all(),
        [
                    'company_name' => 'required|string|max:255',
                    'display_name' => 'required|string|max:255',
                    'company_logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                    'business_type' => 'required|string|max:255',

                    'telephone_contact_1' => 'required|string|max:255',
                    'telephone_contact_2' => 'required|string|max:255',
                    'email_contact_1' => 'required|string|max:255',
                    'email_contact_2' => 'required|string|max:255',

                    'barangay' => 'required|string|max:255',
                    'city_municipality' => 'required|string|max:255',
                    'province' => 'required|string|max:255',
                    'region' => 'required|string|max:255',
                    'zipcode' => 'required|string|max:255',
                    'country' => 'required|string|max:255',
                    'currency_code' => 'required|string|max:255',

                    'registration_number' => 'required|string|max:255',
                    'tin_number' => 'required|string|max:255',

                ]);

                if($validate->fails()){
                    return response()->json([
                    'message' => 'Validation Failed',
                    'errors' => $validate->errors()
                    ], 422); 
                }

            $validatedData = $validate->validated();

            $company = new Companies($validatedData);
            $company->company_name = $validatedData['company_name'];
            $company->dispaly_name = $validatedData['display_name'];
            $company->business_type = $validatedData['business_type'];

            $company->telephone_contact_1 = $validatedData['telephone_contact_1'];
            $company->telephone_contact_2 = $validatedData['telephone_contact_2'];
            $company->email_contact_1 = $validatedData['email_contact_1'];
            $company->email_contact_2 = $validatedData['email_contact_2'];

            $company->barangay = $validatedData['barangay'];
            $company->city_municipality = $validatedData['city_municipality'];
            $company->province = $validatedData['province'];
            $company->region = $validatedData['region'];
            $company->zipcode = $validatedData['zipcode'];
            $company->country = $validatedData['country'];
            $company->registration_number = $validatedData['registration_number'];
            $company->tin_number = $validatedData['tin_number'];

            if ($request->hasFile('company_logo')) {
            $logo = $request->file('company_logo');

            $path = $logo->store('company_logos', 'public');

            $fileName = Str::uuid() . '.' . $logo->getClientOriginalExtension();
            $path = $logo->storeAs('company_logos', $fileName, 'public');

            $company->logo_path = $path; // Save the generated path to the database
        }
        $company->save();

        return new CompanyResource($company);
    }
    public function update(Request $request, Companies $company){
        
           $validate = Validator::make($request->all(),
        [
                    'company_name' => 'required|string|max:255',
                    'display_name' => 'required|string|max:255',
                    'company_logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                    'business_type' => 'required|string|max:255',

                    'telephone_contact_1' => 'required|string|max:255',
                    'telephone_contact_2' => 'required|string|max:255',
                    'email_contact_1' => 'required|string|max:255',
                    'email_contact_2' => 'required|string|max:255',

                    'barangay' => 'required|string|max:255',
                    'city_municipality' => 'required|string|max:255',
                    'province' => 'required|string|max:255',
                    'region' => 'required|string|max:255',
                    'zipcode' => 'required|string|max:255',
                    'country' => 'required|string|max:255',
                    'currency_code' => 'required|string|max:255',

                    'registration_number' => 'required|string|max:255',
                    'tin_number' => 'required|string|max:255',

                ]);

                if($validate->fails()){
                    return response()->json([
                    'message' => 'Validation Failed',
                    'errors' => $validate->errors()
                    ], 422); 
                }

            $validatedData = $validate->validated();

            $company->company_name = $validatedData['company_name'];
            $company->dispaly_name = $validatedData['dispaly_name'];
            $company->business_type = $validatedData['business_type'];

            $company->telephone_contact_1 = $validatedData['telephone_contact_1'];
            $company->telephone_contact_2 = $validatedData['telephone_contact_2'];
            $company->email_contact_1 = $validatedData['email_contact_1'];
            $company->email_contact_2 = $validatedData['email_contact_2'];

            $company->barangay = $validatedData['barangay'];
            $company->city_municipality = $validatedData['city_municipality'];
            $company->province = $validatedData['province'];
            $company->region = $validatedData['region'];
            $company->zipcode = $validatedData['zipcode'];
            $company->country = $validatedData['country'];
            $company->business_registration_number = $validatedData['business_registration_number'];
            $company->tin_number = $validatedData['tin_number'];

            if ($request->hasFile('company_logo')) {
            $logo = $request->file('company_logo');

            $path = $logo->store('company_logos', 'public');

            $fileName = Str::uuid() . '.' . $logo->getClientOriginalExtension();
            $path = $logo->storeAs('company_logos', $fileName, 'public');

            $company->logo_path = $path; // Save the generated path to the database
        }
        $company->save();

        return new CompanyResource($company);

    }
    public function show(Request $request, Companies $company){
        return new CompanyResource($company);
    }
    public function destroy(Companies $company){

            $company->delete();
            return response()->json([
                'message'=>'Data has been deleted successfully'
            ]);

    }


}
