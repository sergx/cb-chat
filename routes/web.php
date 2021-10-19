<?php

use App\Services\CbApiService;
use Illuminate\Http\Request;
use App\User;
use LVR\CreditCard\Factory as CardNumberValidate;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
  return view('welcome');
});

// Route::get('/ppp', function () {
//   $client = new \GuzzleHttp\Client([
//     'headers' => [
//       'User-Agent' => "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)"
//     ],
//     //'base_uri' => 'https://www.astreya-radiodetali.ru',
//     'timeout'  => 2.0,
//   ]);

//   $data = include_once($_SERVER['DOCUMENT_ROOT'] . "/astreya-radiodetali.php");
  
//   $done_count = 0;
//   foreach ($data as $item) {
//     $file_url = 'https://www.astreya-radiodetali.ru' . $item['фото большое'];
    
//     //break;
//     //die;
//     $save_dir = $_SERVER['DOCUMENT_ROOT'] . "/radiodetali/";

//     $file_basename = pathinfo($file_url, PATHINFO_BASENAME);

//     $files = scandir($save_dir);

//     if(in_array($file_basename, $files)){
//       continue;
//     }
//     echo $file_url . " <br>\r\n";
//     $new_file_path =  $save_dir . $file_basename;

//     $client->request('GET', $file_url, [
//       'sink' => $new_file_path,
//       //'proxy' => "http://o5NCcW:2WhWUT@138.59.204.119:9449", // https://proxy6.net/user/proxy
//       //'query' => $query
//     ]);
//     if($done_count > 10){
//       break;
//     }
//     $done_count++;
//     usleep(20000);
//   }
//   //echo "<script>document.location.reload();</script>";
// });


Route::get('/dd', 'BotManController@db');
Route::get('/cb', function (Request $request) {
  //dd(\Carbon\Carbon::now()->subMonthsWithOverflow(3)->toDateTimeString());
  $subMonths = "3";
  DB::enableQueryLog();
  $payouts = \App\Payout::query();
  $payouts->with(["BasicUser", "BasicUser.Cb"]);
  $payouts->whereIn('status', [2, 3]);

  if ($subMonths !== "All") {
    $date_from = \Carbon\Carbon::now()->subMonthsWithOverflow($subMonths)->toDateString();
    $date_to = \Carbon\Carbon::now()->addDay()->toDateString();

    $payouts->whereBetween('status_updated_at', [
      $date_from,
      $date_to
    ]);

    $title_range = $date_from . " — " . $date_to;
  } else {
    $title_range = "За все время";
  }

  $payouts = $payouts->get();
  echo $title_range;
  //dd(DB::getQueryLog());
  dd($payouts);

  dd((new \App\Services\CbrApiService)->rate("USD"));
  return;

  $payouts = \App\Payout::query();
  $payouts->with(["BasicUser", "BasicUser.Tg", "BasicUser.Cb"]);
  if (3) {
    $payouts->whereBetween('updated_at', [
      \Carbon\Carbon::now()->subMonthsWithOverflow(3)->toDateTimeString(),
      \Carbon\Carbon::now()->toDateTimeString()
    ]);
  }
  $payouts = $payouts->get();
  dd($payouts->toArray());

  //$ads = new CardNumberValidate("654654");
  //echo $ads->isValidCardNumber();
  // $telegram_user_id = 337506768;
  // $user = User::with(['Tg', 'Cb', 'Payouts'])->whereHas('Tg', function ($query) use ($telegram_user_id) {
  //   $query->where('id', '=', $telegram_user_id);
  // })->first();
  // $payouts = $user->Payouts->sum('amount');
  // dd($payouts);
  // return;
  // return (new CbApiService)->getAccauntStatistic(12);
  // return (new CbApiService)->get($request->query());
});

Route::match(['get', 'post'], '/botman', 'BotManController@handle');
Route::get('/botman/tinker', 'BotManController@tinker');
