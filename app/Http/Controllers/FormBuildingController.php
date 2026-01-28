<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class FormBuildingController extends Controller
{

    public function loadForm()
    {
        $product = Config::get('stripe.product');
        return view('form', compact('product'));
    }

    public function getFormFieldHTML(Request $request)
    {
        switch ($request->field_type) {
            case "Text":
                $output = "<input type='text' style='
                border: #232323 1px solid;
                border-radius: 4px;
                box-shadow: 0 1px 2px rgba(32,33,36,.28);' />";
                break;

            case "Textarea":
                $output = "<textarea type='text' cols='60' rows='4' style='
                border: #232323 1px solid;
                border-radius: 4px;
                box-shadow: 0 1px 2px rgba(32,33,36,.28);
                max-width: -webkit-fill-available;'></textarea>";
                break;

            case "Button":
                $output = "<button style='border: #4486da 1px solid;
                border-radius: 4px;
                box-shadow: 0 1px 2px rgba(32,33,36,.28);
                background: #4a91eb;
                color: #FFF;
                padding: 3px 40px;'>Button</button>";
                break;

            case "Selectbox":
                $output = "<select type='text' style='
                border: #232323 1px solid;
                border-radius: 4px;
                box-shadow: 0 1px 2px rgba(32,33,36,.28);
                padding: 3px 5px;
                width: 158px;'><option>Select one</options></select> <input class='add-more' placeholder='Eg. Option1,Option2'>";
                break;
        }

        return $output;
    }
}
