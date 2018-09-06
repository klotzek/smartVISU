<?php 
/** 
 * ----------------------------------------------------------------------------- 
 * @package     smartVISU 
 * @author      Martin Gleiß, updated by Carsten Gotschlich got google API V3 
 * @copyright   2012 
 * @license     GPL [http://www.gnu.de] 
 * ----------------------------------------------------------------------------- 
 */ 


require_once '../../../lib/includes.php'; 
require_once const_path_system.'calendar/calendar.php'; 


/** 
 * This class reads a google calenda 
 */  
class calendar_google extends calendar 
{ 

    /** 
     * initialization of some parameters 
     */ 
    public function init($request) 
    { 
        parent::init($request); 
    } 

    /** 
     * Check if the cache-file exists 
     */ 
    public function run() 
    { 
        //--------------------------------------------------------------------- 
        // let's define some parameters - they need to be fetched manually 
        //--------------------------------------------------------------------- 
        $client_id      =   '597328892848-8oeaehdm6c7d2qc8mltjuc7hbtt7chn3.apps.googleusercontent.com';  // put your clientID here - from Google website 
        $client_secret  =   'WhXUqe-6g7h7TkPXZqGzZgW6';  // put your client Secret here - from Google WebSite 
        $redirect_uri   =   'urn:ietf:wg:oauth:2.0:oob';     // put you redirect URI here from Google Website - only first line ! 

        $refresh_token  =   '1/uRoU_GYDNmB59hDD0RozdnBpGBV-cGIVStT99b5KZYs';  // Put your google refresh token here - from other PHP script 
        $calendar_id = 'eduard.ege.13@gmail.com'; // get it this way: http://googleappstroubleshootinghelp.blogspot.de/2012/09/how-to-find-calendar-id-of-google.html 
        //--------------------------------------------------------------------- 
        // get access token first  
        $token_url = 'https://accounts.google.com/o/oauth2/token'; 
        $post_data = array( 
                        'client_secret' =>   $client_secret, 
                        'grant_type'    =>   'refresh_token', 
                        'refresh_token' =>   $refresh_token, 
                        'client_id'     =>   $client_id 
                        ); 
        $ch = curl_init(); 

        curl_setopt($ch, CURLOPT_URL, $token_url); 
        curl_setopt($ch, CURLOPT_POST, 1); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 

        $result = curl_exec($ch); 
        $token_object = json_decode($result); 
        $access_token = $token_object->access_token; 

        //--------------------------------------------------------------------- 
        // so now we have an access-token, let's use it to access the calendar 
        $context = stream_context_create(array('http' => array('method' => "GET", 'header' => "Authorization: OAuth " . $access_token))); 
        $resturl = 'https://www.googleapis.com/calendar/v3/calendars/'. $calendar_id . '/events?maxResults=5&singleEvents=true&orderBy=startTime&timeMin='.urlencode(date('c')); 
        $content = @file_get_contents($resturl, false, $context); 
        $this->debug($content); 

        
        if ($content !== false) 
        { 
            $result = json_decode($content,true); 

            $i = 1; 

            foreach ($result["items"] as $entry) 
            { 
//                 $TZ = date_default_timezone_set ( 'Europa/Busingen' );
//                 $DST = date("I", $entry["start"]["dateTime"]);
//                 if ($DST) {$offset = 3600;}
                $startstamp = strtotime($entry["start"]["dateTime"]);
//                 $startstamp = strtotime($entry["start"]["dateTime"]) + date("Z", $entry["start"]["dateTime"]);
//                 if ($DST) { $startstamp += 3600 } 
//                 $endstamp = strtotime($entry["end"]["dateTime"]) + date("Z", $entry["end"]["dateTime"]); 
                $endstamp = strtotime($entry["end"]["dateTime"]); 

                $this->data[] = array('pos' => $i++, 
//                     'start' => date('y-m-d', $startstamp).' '.gmdate('H:i:s', $startstamp), 
                    'start' => date('y-m-d', $startstamp).' '.date('H:i:s', $startstamp), 
//                     'end' => date('y-m-d', $endstamp).' '.gmdate('H:i:s', $endstamp), 
                    'end' => date('y-m-d', $endstamp).' '.date('H:i:s', $endstamp), 
                    'title' => (string)($entry["summary"]), 
//                     'title' => (string)($DST), 
                    'content' => (string)($entry["description"]), 
                    'where' => (string)($entry["location"]), 
                    'link' => (string)($entry["htmlLink"]) 
                ); 
            } 
        } 
        else 
            $this->error('Calendar: Google', 'Calendar read request failed!'); 
    } 
} 


// ----------------------------------------------------------------------------- 
// call the service 
// ---------------------------------------------------------------------------- 

$service = new calendar_google(array_merge($_GET, $_POST)); 
echo $service->json(); 

?>