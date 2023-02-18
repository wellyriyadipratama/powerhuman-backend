<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\CreateCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;

class CompanyController extends Controller
{
    public function fetch(Request $request)
    {
        $id = $request->input('id');
        $name = $request->input('name');
        $limit = $request->input('limit', 10);

        // Get single data
        if ($id) {
            $company = Company::whereHas('users', function($query){
                $query->where('user_id', Auth::id());
            })->with(['users'])->find($id);

            if ($company) {
                return ResponseFormatter::success($company,'Company found');
            }
            return ResponseFormatter::error('Company not found', 404);
        }

        // Get multiple data
        $companies = Company::with(['users'])->whereHas('users', function($query){
            $query->where('user_id', Auth::id());
        });

        // powerhuman.com/api/company?name=kunde
        if ($name) {
            $companies->where('name', 'like', '%' . $name . '%');
        }

        // Company::with(['users'])->where('name', 'like', '%kunde%')->paginate(10);
        return ResponseFormatter::success(
            $companies->paginate($limit),
            'Companies found'
        );
    }


    public function create(CreateCompanyRequest $request)
    {
        try {
            //Upload logo
            if ($request->hasFile('logo')) {
                $path = $request->file('logo')->store('public/logos');
            
            //Create company
            $company = Company::create([
                'name' => $request->name,
                'logo' => $path,
            ]);

            if (!$company) {
                throw new Exception('Company not created');    
            }

            //Attach company to user
            $user = User::find(Auth::user()->id);
            $user ->companies()->attach($company->id);

            //load company to users
            $company->load('users');

                return ResponseFormatter::success($company, 'Company created');
            }
        } catch (Exception $e) {
            return ResponseFormatter::error($e->getMessage(), 500);
        }
    }


    public function update(UpdateCompanyRequest $request)
    {
        try {
            //get company
            $company = Company::find($request->id);

            //check if company exists
            if (!$company) {
                throw new Exception('Company not found');
            }

            //Upload logo
            if ($request->hasFile('logo')) {
                $path = $request->file('logo')->store('public/logos');
                $company->logo = $path;
            }

            //update company
            $company->update([
                'name' => $request->name,
                'logo' => isset($path) ? $path : $company->logo,
            ]);

            return ResponseFormatter::success($company, 'Company updated');
        } catch (Exception $e) {
            return ResponseFormatter::error($e->getMessage(), 500);
        }
    }
}
