<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Services\Security\SecurityAdminCenterService;
use Illuminate\Http\Request;

class SecurityAdminCenterController extends Controller
{
    public function index(Request $request, SecurityAdminCenterService $service)
    {
        return view('security.admin_center.index', $service->dashboard($request));
    }
}
