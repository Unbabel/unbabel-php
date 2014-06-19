Unbabel's PHP SDK is a wrapper around the HTTP API found at https://github.com/Unbabel/unbabel_api

# SETUP

Set two required environment variables that can be obtained by signing up at https://www.unbabel.com/

* UNBABEL_USERNAME
* UNBABEL_APIKEY

Set this to the string 'true' if you want to use the sandbox machine.

* UNBABEL_SANDBOX

# USAGE

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
  
