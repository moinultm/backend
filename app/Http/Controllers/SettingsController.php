<?php

namespace App\Http\Controllers;

use App\Setting;
use App\Tax;
use Illuminate\Http\Request;
use App\Traits\Paginator;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    use Paginator;

    public function getIndex()
    {
        $setting = Setting::whereId(1)->first();
        $taxes = Tax::all();

        if($setting) {
            $tax_rate = $setting->invoice_tax_rate;
        }else{
            $tax_rate = 0;
        }

        if(!$setting){
            $setting = new Setting;
        }

        $data=   compact('setting','taxes','tax_rate');

        $AssociateArray = array(
            'data' =>  $setting
        );

        return response()->json($setting ,200);


    }


    public function update(Request $request,$id)
    {


        $rules = [
            'site_name' => 'required|max:255',
            'email' => 'required|email',
            'address' => 'required|max:255',
            'phone' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(collect($validator->getMessageBag())->flatten()->toArray(), 403);
        }






        if($request->get('invoice_tax') == 1){
            $this->validate(
                $request,
                ['invoice_tax_id' => 'required'],
                ['invoice_tax_id.required' => 'When you enable Order Tax, you must select Order Tax rate']
            );
        }

        $setting = Setting::findOrFail(1);
        $setting->site_name = $request->get('site_name');
        $setting->slogan = $request->get('slogan');
        $setting->address = $request->get('address');
        $setting->email = $request->get('email');
        $setting->phone = $request->get('phone');
        $setting->owner_name = $request->get('owner_name');
        $setting->currency_code = $request->get('currency_code');
        $setting->alert_quantity = $request->get('alert_quantity');
        $setting->product_tax = $request->get('product_tax');
        $setting->invoice_tax = $request->get('invoice_tax') ? 1 : 0;
        $setting->invoice_tax_rate = ($request->get('invoice_tax_id')) ? Tax::whereId($request->get('invoice_tax_id'))->first()->rate : 0;
        $setting->invoice_tax_type = ($request->get('invoice_tax_id')) ? Tax::whereId($request->get('invoice_tax_id'))->first()->type : 2;

        $setting->theme = $request->get('theme');
        $setting->enable_purchaser = $request->get('enable_purchaser');
        $setting->enable_customer = $request->get('enable_customer');
        $setting->vat_no = $request->get('vat_no');
        $setting->pos_invoice_footer_text = $request->get('pos_invoice_footer_text');
        $setting->dashboard = $request->get('dashboard');

        if($request->hasFile('image')){
            $file = $request->file('image');
            $imageSize = getimagesize($file);
            $width = $imageSize[0];
            $height = $imageSize[1];
            if($width > 190 || $height > 34){
                $warning = "Invalid Image Size";
                return redirect()->back()->withWarning($warning);
            }
            $file_extension = $file->getClientOriginalExtension();
            $random_string = str_random(12);
            $file_name = $random_string.'.'.$file_extension;
            $destination_path = public_path().'/uploads/site/';
            $request->file('image')->move($destination_path, $file_name);

            $setting->site_logo = $file_name;
        }

        $message = trans('core.changes_saved');
        $setting->save();

        return response()->json('Success', 200);
    }


}
