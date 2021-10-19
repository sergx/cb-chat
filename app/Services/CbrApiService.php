<?php
namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
// (new \App\Services\CbrApiService)->rate("USD");
class CbrApiService{

  public function rate(String $code, $value = false) {
    $data = $this->cbr();
    $code = strtoupper($code);
    $rate = 0;

    if (!empty($data[$code])) {
      $rate =  $data[$code]['v'];
    }
    if($value){
      return floor($rate * $value);
    }
    return $rate;
  }

  public function cbr() {
    $data = Cache::get("cbr_rates");
    if (!$data) {
      $client = new Client([
        'base_uri' => 'https://www.cbr.ru',
        'timeout'  => 7.0,
      ]);
      $res = $client->request('GET', "/scripts/XML_daily.asp", [
        'query' => ['date_req' => date("d/m/Y")]
      ]);
      $output = $res->getBody();

      $json_daily_temp = simplexml_load_string($output);
      if ($res->getStatusCode() === 200 && $json_daily_temp && !empty($json_daily_temp->Valute)) {
        $is_valid = true;
      }
      if ($is_valid) {
        foreach ($json_daily_temp as $v) {
          $ta = [];
          $ta["n"] = (string) $v->Name;
          $ta["v"] = round(floatval(str_replace(",", ".", $v->Value)) / intval($v->Nominal), 2);
          $data[strval($v->CharCode)] = $ta;
        }
        Cache::put("cbr_rates", $data, now()->addDays(1));
        Cache::forever("cbr_rates_backup", $data);
      } else{
        $data = Cache::get("cbr_rates_backup");
      }
    }

    return $data;
  }

}
