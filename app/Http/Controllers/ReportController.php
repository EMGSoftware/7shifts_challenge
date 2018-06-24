<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Calculations;

class ReportController extends Controller
{

    public function index()
    {
        $calculations = new Calculations();
        
        if (!$calculations->import())
        {
            $message = "An error ocurred while trying to get information from the cloud";
        }
        else
        {
            if (!($report = $calculations->getDailyReport()))
            {
                $message = "An error ocurred while trying to process the information";
            }
            else
            {
                return view ("reports.userHours.index", compact(["report"]));
            }
        }
        
        return view ("error", compact ([$message]));
    }
}
