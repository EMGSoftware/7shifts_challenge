<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\TimePunch;
use Illuminate\Support\Facades\DB;

class Calculations extends Model
{
    private $timePunches;
    private $employees;
    private $locations;
    
    private function fetchAll($entity, &$container)
    {
        try
        {
            $baseURL = 'https://shiftstestapi.firebaseio.com/';
            $url = join ('', [$baseURL, $entity, '.json']);
            $ch = curl_init ($url);
            curl_setopt ($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt ($ch, CURLOPT_URL,$url);
            curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec ($ch);
            $info = curl_getinfo ($ch);
            $http_result = $info ['http_code'];
            curl_close ($ch);
            
            $container = json_decode ($output);
            return true;
        }
        catch (\Exception $ex)
        {
            return false;
        }
    }
    
    public function import ()
    {
        try
        {
            $this->fetchAll("timePunches", $this->timePunches);
            $this->fetchAll("users", $this->employees);
            $this->fetchAll("locations", $this->locations);
            
            $queries = [];
            $queries [] = "create table IF NOT EXISTS timePunches (clockedIn text, clockedOut text, elapsed integer, locationId integer, userId integer, hourlyWage real);";
            foreach ($this->timePunches as $timePunch)
            {
                $queries [] = join ("", [
                    "insert into timePunches (clockedIn, clockedOut, elapsed, locationId, userId, hourlyWage, weekOfYear) ",
                    "values (",
                    "'".$timePunch->clockedIn."', ".
                    "'".$timePunch->clockedOut."', ".
                    (strtotime ($timePunch->clockedOut) - strtotime ($timePunch->clockedIn)).", ".
                    $timePunch->locationId.", ".
                    $timePunch->userId.", ".
                    $timePunch->hourlyWage.",".
                    date ("W", strtotime($timePunch->clockedIn)).",".
                    ");"
                    ]);
            }
            DB::insert (join ("", $queries));
            
            $this->employees = current ((Array)$this->employees);
            
            $queries = [];
            $queries [] = "create table IF NOT EXISTS employees (id integer, fullname text, picture text);";
            foreach ($this->employees as $employee)
            {
                
                $queries [] = join ("", [
                    "insert into employees (id, fullname, picture) ",
                    "values (",
                    $employee->id.", ".
                    "'".$employee->firstName." ".$employee->lastName."', ".
                    "'".$employee->photo."'".
                    ");"
                    ]);
            }
            DB::insert (join ("", $queries));
            
            $queries = [];
            $queries [] = "create table IF NOT EXISTS locations (id integer, description text, dailyOvertimeMultiplier float, dailyOvertimeThreshold float, weeklyOvertimeMultiplier float, weeklyOvertimeThreshold float);";
            foreach ($this->locations as $location)
            {
                $queries [] = join ("", [
                    "insert into locations (id, description, dailyOvertimeMultiplier, dailyOvertimeThreshold, weeklyOvertimeMultiplier, weeklyOvertimeThreshold)",
                    "values (",
                    $location->id.", ".
                    "'".$location->address."', ".
                    $location->labourSettings->dailyOvertimeMultiplier.",".
                    $location->labourSettings->dailyOvertimeThreshold.",".
                    $location->labourSettings->weeklyOvertimeMultiplier.",".
                    $location->labourSettings->weeklyOvertimeThreshold.
                    ");"
                    ]);
            }
            
            DB::insert (join ("", $queries));
            return true;
        }
        catch (\Exception $ex)
        {
            return false;
        }
    }
    
    public function getDailyReport ()
    {

        $results = DB::select ("
            select
                user,
                location,
                ((totalMinutes - overtimeUsed) / 60) as regularHours,
                (overtimeUsed  / 60) as overtimeHours,
            from 
            (
                select
                    user,
                    location,
                    (case 
                    when weekly.overtime > daily.overtime 
                    then weekly.overtime
                    else daily.overtime
                    end) as overtimeUsed,
                    totalMinutes
                from
                (
                    select
                        user,
                        location,
                        day,
                        totalMinutes,
                        (case 
                        when totalMinutes > dailyOvertimeThreshold 
                        then ((totalMinutes - dailyOvertimeThreshold) * dailyOvertimeMultiplier)
                        else 0 end) as overtime
                    from (
                        select 
                            ep.fullname as user, 
                            lt.description as location, 
                            substr (tp.clockedIn, 0, 10) as day,
                            null as weekOfYear,
                            lt.dailyOvertimeThreshold
                            lt.dailyOvertimeMultiplier,
                            sum (elapsed) as totalMinutes
                        from 
                            locations as lt inner join 
                            timePunches as tp on lt.id = tp.locationId inner join 
                            employees as ep on tp.userId = ep.id 
                        group by 
                            ep.fullname, 
                            lt.description, 
                            lt.dailyOvertimeThreshold, 
                            lt.dailyOvertimeMultiplier, 
                            substr (tp.clockedIn, 0, 10)
                            ) as daily
                    inner join 
                    (
                    select
                        user,
                        location,
                        weekOfYear,
                        totalMinutes,
                        (case when 
                            totalMinutes > weeklyOvertimeThreshold 
                        then ((totalMinutes - weeklyOvertimeThreshold) * weeklyOvertimeMultiplier)
                        else 0 end) as overtime
                    from (
                        select 
                            ep.fullname as user, 
                            lt.description as location, 
                            '' as day,
                            weekOfYear,
                            lt.weeklyOvertimeThreshold
                            lt.weeklyOvertimeMultiplier,
                            sum (elapsed) as totalMinutes 
                        from 
                            locations as lt inner join 
                            timePunches as tp on lt.id = tp.locationId inner join 
                            employees as ep on tp.userId = ep.id 
                        group by 
                            ep.fullname, 
                            lt.description, 
                            lt.weeklyOvertimeThreshold, 
                            lt.weeklyOvertimeMultiplier, 
                            weekOfYear
                            ) as weekly
                    on daily.user = weekly.user and daily.location = weekly.location
                ) as weeklyAndDaily
            ) as GodzillaQuery");
        
        return $results;    
    }
    
}
