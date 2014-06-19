<?php

require 'vendor/autoload.php';

$REQUIRED_VARS = array('UNBABEL_USERNAME', 'UNBABEL_APIKEY');

/**
   
   Unbabel's PHP SDK is a wrapper around the HTTP API found at https://github.com/Unbabel/unbabel_api

   SETUP
   
   Set two required environment variables that can be obtained by signing up at https://www.unbabel.com/
     - UNBABEL_USERNAME
     - UNBABEL_APIKEY

   Set this to the string 'true' if you want to use the sandbox machine.
     - UNBABEL_SANDBOX
   
   USAGE

     require 'unbabel';

     //$resp is an instance of a guzzle response object http://docs.guzzlephp.org/en/latest/http-messages.html#responses
     $opts = array('callback_url' => 'http://my-awesome-app/unbabel_callback.php');
     $resp = Unbabel::get_translation($text, $target_language, $opts);
     if ($resp->getStatusCode() == 201) {
         //Hooray! Now we need to get the uid so when we are called back we know which translation it corresponds to.
         $uid = $resp->json()['uid'];
         save_uid_for_callback($uid);
     } else {
         //If you think everything should be working correctly and you still get an error,
         //send email to sam@unbabel.com to complain.
     }

     ////////////////////////////////////////////////
     //       Other examples
     ////////////////////////////////////////////////

     var_dump(Unbabel::get_topics()->json());
     var_dump(Unbabel::submit_translation('This is a test', 'pt')->json());
     var_dump(Unbabel::get_jobs_with_status('new')->json());
     var_dump(Unbabel::get_translation('8a82e622db')->json());
     var_dump(Unbabel::get_tones()->json());

     var_dump(Unbabel::get_language_pairs()->json());

     $bulk = [
         ['text' => 'This is a test', 'target_language' => 'pt'],
         ['text' => 'This is a test', 'target_language' => 'es']
     ];
     var_dump(Unbabel::submit_bulk_translation($bulk)->json());

 */

class Unbabel {
    
    /**
      Submit a single translation. Full set of options can be found here:
          https://github.com/Unbabel/unbabel_api#request-translation

      For example, to translation a phrase from english to pt, do the following:

      Unbabel::submit_translation('This is a test', 'pt')->json();
     */
    public static function submit_translation($text, $target_language, $options = null) {
        if($options == null) {
            $options = array();
        }
        $data = array(
            'text' => $text, 
            'target_language' => $target_language
        );
        $data = array_merge($data, $options);
        return Unbabel::api('/translation/', $data, 'post');
    }
    
    /**
      Submit an array where each entry in the array is an assoc array with a minimum of two
      key / value pairs: 'text' and 'target_language'. For example:

        $bulk = [
          ['text' => 'This is a test', 'target_language' => 'pt'],
          ['text' => 'This is a test', 'target_language' => 'es']
        ];

      Unbabel::submit_bulk_translation($bulk)->json()

     */
    public static function submit_bulk_translation($data, $options = null) {
        if($options == null) {
            $options = array();
        }
        $tosend = array();
        foreach($data as $t) {
            $t = array_merge($t, $options);
            $tosend[] = $t;
        }
        $data = array('objects' => $data);
        return Unbabel::api('/translation/', $data, 'patch');
    }

    /*
      Fetch data for a single translation.
     */
    public static function get_translation($uid) {
        return Unbabel::api("/translation/$uid/", array(), 'get');
    }

    /*
      Get all data for all jobs with the specified status.
     */
    public static function get_jobs_with_status($status) {
        $possible = ['new', 'ready', 'in_progress', 'processing'];
        if (! in_array($status, $possible)) {
            echo("Expected status to be one of $possible");
            return null;
        }
        $query = array('status' => $status);
        return Unbabel::api('/translation/', $query, 'get');
    }
    
    /*
      Get all language pairs avilable.
     */
    public static function get_language_pairs() {
        return Unbabel::api('/language_pair/', array(), 'get');
    }

    /*
      Get all tones available in the platform;
     */
    public static function get_tones() {
        return Unbabel::api('/tone/', array(), 'get');
    }

    /*
      Get all topics available in the platform.
     */
    public static function get_topics() {
        return Unbabel::api('/topic/', array(), 'get');
    }

    private static function api($path, $data, $method = 'get') {

        $response = null;
        $username = $GLOBALS['UNBABEL_USERNAME'];
        $apikey = $GLOBALS['UNBABEL_APIKEY'];
        $sandbox = $GLOBALS['UNBABEL_SANDBOX'];

        if ($sandbox) {
            $endpoint = "http://sandbox.unbabel.com/tapi/v2";
        } else {
            $endpoint = "https://unbabel.co/tapi/v2";
        }

        $url = $endpoint . $path;

        $headers = array(
            'Authorization' => "ApiKey $username:$apikey",
            'Content-Type' => 'application/json'
        );

        $args = array('headers' => $headers);

        if ($method == 'get') {
            $args['query'] = $data;
            $response = GuzzleHttp\get($url, $args);
        } else if ($method == 'post') {
            var_dump($url);
            var_dump($data);
            $args['json'] = $data;
            $response = GuzzleHttp\post($url, $args);
        } else if ($method == 'patch') {
            $args['json'] = $data;
            $response = GuzzleHttp\patch($url, $args);
        }

        return $response;
    }
}

/**
   Run upon requirement to make sure the env vars are set
 */
function check_unbabel_creds() {

    global $REQUIRED_VARS, $OPTIOINAL_VARS;
    $fail = false;
    foreach($REQUIRED_VARS as $varname) {
        $varval = getenv($varname);
        if(! assert('$varval != null', "$varname required to be set as environment variable")) {
            $fail = true;
        }
        $GLOBALS[$varname] = $varval;
    }

    if ($fail) {
        echo("Unable to find all required environment variables\n");
        exit(-1);
    }
    
    $GLOBALS['UNBABEL_SANDBOX'] = getenv('UNBABEL_SANDBOX') == 'true';
}

check_unbabel_creds();

?>