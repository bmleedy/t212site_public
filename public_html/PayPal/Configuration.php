<?php 
class Configuration
{
  // For a full list of configuration parameters refer in wiki page (https://github.com/paypal/sdk-core-php/wiki/Configuring-the-SDK)
  public static function getConfig()
  {
    $config = array(
        // values: 'sandbox' for testing
        //       'live' for production
        "mode" => "live"

        // These values are defaulted in SDK. If you want to override default values, uncomment it and add your value.
        // "http.ConnectionTimeOut" => "5000",
        // "http.Retry" => "2",
      );
    return $config;
  }

  // Creates a configuration array containing credentials and other required configuration parameters.
  public static function getAcctAndConfig()
  {
    // Load credentials from CREDENTIALS.json
    try {
      require_once(__DIR__ . '/../includes/credentials.php');
      $creds = Credentials::getInstance();
    } catch (Exception $e) {
      die("Error loading credentials in Configuration.php: " . $e->getMessage());
    }

    $config = array(
        // Signature Credential - loaded from CREDENTIALS.json

        "acct1.UserName" => $creds->getPayPalProductionUsername(),
        "acct1.Password" => $creds->getPayPalProductionPassword(),
        "acct1.Signature" => $creds->getPayPalProductionSignature(),
        "acct1.AppId" => $creds->getPayPalProductionAppId()  // live
        // For sandbox, uncomment the line below and comment out the line above:
        //"acct1.AppId" => $creds->getPayPalSandboxAppId()  // sandbox

        /*
        //"acct1.UserName" => "jb-us-seller_api1.paypal.com",
        //"acct1.Password" => "WX4WTU3S8MY44S7F",
        //"acct1.Signature" => "AFcWxV21C7fd0v3bYYYRCpSSRl31A7yDhhsPUU2XhtMoZXsWHFxu-RWy",
        //"acct1.AppId" => "APP-80W284485P519543T"
        */
        // Sample Certificate Credential
        // "acct1.UserName" => "certuser_biz_api1.paypal.com",
        // "acct1.Password" => "D6JNKKULHN3G5B8A",
        // Certificate path relative to config folder or absolute path in file system
        // "acct1.CertPath" => "cert_key.pem",
        // "acct1.AppId" => "APP-80W284485P519543T"
        );

    return array_merge($config, self::getConfig());;
  }

}