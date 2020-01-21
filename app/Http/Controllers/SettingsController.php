<?php

namespace App\Http\Controllers;

use App\Setting;
use App\Tax;
use Illuminate\Http\Request;
use App\Traits\Paginator;
use Illuminate\Support\Facades\Validator;
use App\Traits\FileHelper;
class SettingsController extends Controller
{

    use Paginator,FileHelper;
    public function getIndex()
    {

        $setting = Setting::whereId(1)->first();

        if(!$setting){
            $setting = new Setting;
        }

        return response()->json($setting ,200);

    }


    public function update(Request $request,$id)
    {

        $rules = [
            'site_name' => 'required|max:255',
            'email' => 'required',
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


        if ($request->has('site_logo')) {
            if ($setting->site_logo != null) {
                unlink(public_path('uploads/site') . '/' . $setting->site_logo);
            }
            $setting->site_logo = $this->upload($request->site_logo, public_path('uploads/site'));
        }


        if ($request->has('invoice_header')) {
            if ($setting->invoice_header != null) {
                unlink(public_path('uploads/site') . '/' . $setting->invoice_header);
            }
            $setting->invoice_header = $this->upload($request->invoice_header, public_path('uploads/site'));
        }

        $message = trans('core.changes_saved');
        $setting->save();

        return response()->json('Success', 200);
    }

    public function image(int $id)
    {
        $user = Setting::where('id', $id)->first();
        if ($user == null || $user->image == null) {
            return null;
        }
        return $this->download(public_path('uploads/site/'), $user->image);
    }


}
