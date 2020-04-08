<?php
/*
Plugin Name: Sign in with GitHub OAuth
Plugin URI: https://github.com/LouisSung/yourls-oauth_sign_in
Description: Enable GitHub OAuth sign in support
Version: 1.1 (GitHub)
Author: LouisSung
Author URI: https://github.com/LouisSung
*/

// No direct call
if(!defined('YOURLS_ABSPATH')) {die();}
// prerequisite: 'composer require league/oauth2-github'
require_once YOURLS_ABSPATH.'/includes/vendor/autoload.php';

session_start();
yourls_add_filter('login_form_end', 'add_oauth2_support');
yourls_add_filter('admin_page_before_table', 'remove_oauth_args_from_admin_index');

function remove_oauth_args_from_admin_index() {
    // remove args 'code' and 'state' after sign in
    if(isset($_GET['code']) && isset($_GET['code'])) {
        echo "<script type='text/javascript'>window.location.replace('".YOURLS_SITE."/admin/index.php');</script>";}
}

function add_oauth2_support() {
    $config_provider = include_once 'config_provider.php';
    $WARNING_PRINT_PASSWORD_IN_BROWSER = false;

    // ref: https://github.com/thephpleague/oauth2-github
    $provider = new League\OAuth2\Client\Provider\Github($config_provider);

    if(!isset($_GET['code'])) {
        $optional = ['scope' => ['read_user']];    // set up GitHub API scope
        $authUrl = $provider->getAuthorizationUrl($optional);
        $_SESSION['oauth2state'] = $provider->getState();
        header('Location: '.$authUrl);
    }
    elseif(empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
        unset($_SESSION['oauth2state']);
        exit('Invalid state');
    }
    else {
        $token = $provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);
        try {
            $user = $provider->getResourceOwner($token);
            // pass domain verification, get user account and calculate user password
            $account = $user->getNickname();    // `id, usename, or email` are recommended to be used as an account
            // design your own grabled password generating function
            // parameter for grabled password generating function (PICK YOUR OWN ONE AS NEEDED) (at least 16 digits is required)
            // here I use prime numbers from ref: https://primes.utm.edu/curios/index.php?start=16&stop=16
            [$PRIME_16DIGITS, $INITIALIZATION_VECTOR] = ['2355457523880889', '7897466719774591'];
            $tmp_pass = str_repeat(substr(strrev($account), -8).substr($account, -8).
                                   substr(number_format($user->getID()*floatval($PRIME_16DIGITS), 1, '%', '&'), -16), 4);
            $password = substr(openssl_encrypt($tmp_pass, 'AES-256-CTR', $account, 0, $INITIALIZATION_VECTOR), 7, 64);

            if($WARNING_PRINT_PASSWORD_IN_BROWSER) {
                // 1. hide submit button to prevent user from sign in
                // 2. fill in account and password (now, the admin is able to get plaintext password in browser Dev Tools)
                $script_print_password = <<<PRINT_PASSWORD_IN_BROWSER
<script type='text/javascript'>
$(document).ready(function() {
    $('#submit').closest('p').hide();
    $('#username').val('$account');
    $('#password').val('$password');
}());
</script>
PRINT_PASSWORD_IN_BROWSER;
            }
            else {
                // 1. hide the original form first, so that user won't see or accidentally mess up their info
                // 2. make input field readonly in order to prevent browser from (ask for) saving password
                // 3. fill in account and password
                // 4. submit form automatically to sign in
                $script_sign_in = <<<OAUTH_PASSED_AND_TRY_TO_SIGN_IN
<script type='text/javascript'>
$(document).ready(function() {
    $('#submit, #username, #password').closest('p').hide();
    $('#username, #password').attr({'autocomplete': 'off', 'readonly': true});
    $('#username').val('$account');
    $('#password').val('$password');
    $('#submit').click();
}());
</script>
OAUTH_PASSED_AND_TRY_TO_SIGN_IN;
            }
        } catch(Exception $e) {exit($e->getMessage());}
    }

    $auth_url = $authUrl ?? '#';    // make sure $auth_url is not null
    // script order: if print_password; elif sign_in; else change_button
    $script = $script_print_password ?? $script_sign_in ?? <<<HIDE_FORM_AND_CHANGE_BUTTON
<script type='text/javascript'>
    $(document).ready(function() {
        $('#username, #password').closest('p').hide();
        var btn_submit = $('#submit');
        btn_submit.closest('p').css('text-align', 'center');
        btn_submit.attr({'type': 'button', 'style': 'font-size: 14px', 'value': 'Log in with GitHub'});
        btn_submit.click(function(){window.open('$auth_url', '_self');});
    }());
</script>
HIDE_FORM_AND_CHANGE_BUTTON;

    echo $script;    // put JS
}
