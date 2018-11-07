<?php 
class TablUtil {

const InternalURL    = "localhost:8080";
const ExternalURL    = "frunto.com:8080";
const AuthorizedUser = "FruntoAdmin";


private static function get_trusted_ticket() {
    
    $url = self::InternalURL;
    $url = "http://$url/trusted";
    $post_data = array ("username" => self::AuthorizedUser);
    
    $ch = curl_init();
    if( $ch === FALSE ) 
        throw new Exception("Server Configuration Error: Unable to Initialize cURL."); 

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

    $output = curl_exec($ch);
    curl_close($ch);

    if( $output === FALSE or $output == "-1" )
         throw new Exception("Tableau Server reported authentication error, please find Tableau Support for assistance.");

    return $output;
}

public static function get_trusted_url($view_url) {
    $params = ':embed=yes&toolbar=no';
    
    try{
        $ticket = self::get_trusted_ticket();
        if (strcmp($ticket, "-1") != 0) {
            $url = self::ExternalURL;
            $url = "http://$url/trusted/$ticket/$view_url?$params";
            return $url;
        }
        else 
            return 0;
    }
    catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }
}
    
public static function return_if_invalid_auth($project) {
    $currentSessionID = $_GET['session'];

    session_id($currentSessionID);
    session_start();

    if( $_SESSION[$project] != 'Authenticated') 
        header("Location:http://www.frunto.com/login.php");
}
    
}
?>