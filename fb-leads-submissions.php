<?php

/**
 * @package: fb-leads-sumbmissions
 * @version: 1.0
 * Plugin name: Fetch Data from FB by Heigh10
 * Author: Deepak Kumar
 * Author URI: https://www.linkedin.com/in/deepak01
 * Description: cURL based plugin to fetch data from FB ad form and save it into website and send mail as well.
 * Version: 1.0
 */

 if (!defined('ABSPATH')) {
     exit;
 }
 
$access_token = 'EAAGAaaCGJUUBAMne9ls7AueQrNZAqwfx4npZAt25iY4c77psNgXJKhEowYle73m35ztSc6YJNmmiPxcyn0D1hzw3YYFakakygmhJdwBfoz607EnyFS8WaLswbxZAVziY7xNzSY50fOiDTdngV6ZA1tFazUgIvbesPE78ZACf0CWMEmVIbVaEeCtjROppuZA9eIPCBvK94YvJbg7lML4U2I';
$form_id = 1018367732139635;

//curl request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://graph.facebook.com/v14.0/'.$form_id.'/leads?access_token='.$access_token);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$contents = curl_exec($ch);

//cURL data to PHP Array
$contents = json_decode($contents,true);

//check for coming ID already saved in WP
if(!function_exists('is_value_exists')){
function is_value_exists($lead_id){
if(empty($lead_id)) return NULL;
global $wpdb, $table_prefix;

$table = $table_prefix.'e_submissions_values';

$query = sprintf("SELECT id FROM %s WHERE `key` = '%s' AND `value` = '%s'",$table,'lead_id',$lead_id);

$id = $wpdb->get_results($query);

return empty($id)? false : true; 
}
}

//insert coming data to WP
function insert_fb_submissions($data){
if(empty($data)) return;
global $wpdb, $table_prefix;
$table1 = $table_prefix.'e_submissions';
$table2 = $table_prefix.'e_submissions_values';

//Generate Hash ID
$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
$randomString = '';

for ($i = 0; $i < 13; $i++) {
    $index = rand(0, strlen($characters) - 1);
    $randomString .= $characters[$index];
}

$randomString = base64_encode($randomString);

//E_Submissions Table
$query1 = sprintf('INSERT INTO %s (`type`,`hash_id`,`referer`,`form_name`,`user_ip`,`user_agent`,`status`,created_at_gmt,updated_at_gmt,created_at,updated_at) VALUES ("submission","%s","http://facebook.com","facebook_lead","%s", "%s","new","%s","%s","%s","%s")',$table1,$randomString,$_SERVER["REMOTE_ADDR"],$_SERVER['HTTP_USER_AGENT'],gmdate("y-m-d h:i:s"),gmdate("y-m-d h:i:s"),date("y-m-d h:i:s"),date("y-m-d h:i:s"));
$wpdb->get_results($query1);
$return_id1 = $wpdb->insert_id;

foreach ($data as $key => $value) {
    //E_Submissions_Values Table
    $query2 = sprintf("INSERT INTO %s (`submission_id`,`key`,`value`) VALUES('%s','%s','%s')",$table2,$return_id1,$key,$value);
    $result = $wpdb->get_results($query2);
}

$return_id2 = $wpdb->insert_id;
$q1 = sprintf("UPDATE %s SET `main_meta_id` = %s WHERE id= %s",$table1,$return_id2-7,$return_id1);
$wpdb->get_results($q1);
}

$contents = $contents['data'][0];

//Loop for all coming objects
for ($i=0; $i < sizeof($contents['field_data']); $i+=4) { 
//data ID
$id = $contents['id'];
$name = $contents['field_data'][0]['values'][0];
$email = $contents['field_data'][1]['values'][0];
$company_name = $contents['field_data'][2]['values'][0];
$phone = $contents['field_data'][3]['values'][0];

$data = [
    'Name'=>$name,
    'Email'=>$email,
    'Company_name'=>$company_name,
    'Phone'=>$phone,
    'lead_id'=>$id,
    'Source'=>'facebook-ad',
    'Campaign'=>'fmla',
    'Ad_Group'=>'form-1'
];

if(!is_value_exists($id)){

insert_fb_submissions($data);

$to = ['deepak@heigh10.com','marketing@heigh10.com'];
$subject = 'FMLA - Call Request';
$message = "<b>Name</b>:".$name."<br><b>Email</b>: ".$email."<br><b>Company Name</b>: ".$company_name."<br> <b>Phone</b>: ".$phone."<br><br><br>----<br><b>Page:</b> Data submitted via FB forms <br><b>Source: </b>".$data['Source']."<br><b>Campaign:</b> ".$data['Campaign']."<br><b>Ad_Group: </b>".$data['Ad_Group'];
$headers[] = 'From: FMLA <no-reply@sterlingadministration.com>';

wp_mail( $to, $subject, $message, $headers);
}
}