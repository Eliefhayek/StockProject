<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use OpenAI\Laravel\Facades\OpenAI;
use QuickChart;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Dompdf\Dompdf;
class ReportController extends Controller
{
    //
    public function Report(Request $request){
        $client = new Client();
        //Validating data
        try{
        $validated_data=$request->validate([
            'company_name'=>'required'
        ]);
    }
    catch (Exception $e) {


        return response()->json('inValid Data');
    }
    $comp=$validated_data['company_name'];
    $comp=$validated_data['company_name'];
    $url='https://www.alphavantage.co/query?function=SYMBOL_SEARCH&keywords='.$comp.'&apikey=OQ6AKG7RHH5WHOY5';
    $json = file_get_contents($url);

    $data = json_decode($json,true);
    $comp=$data['bestMatches'][0]['1. symbol'];
    $url='https://yahoo-finance127.p.rapidapi.com/finance-analytics/'.$comp;
    try{
    $response = $client->request('GET', $url, [
	    'headers' => [
            'X-RapidAPI-Host' => 'yahoo-finance127.p.rapidapi.com',
            'X-RapidAPI-Key' => '734b1990cemsh7da219e7b57aac6p1750c7jsn5773045cb9f9',
	],
]);

    // we get the information from the finance-analytics api from the Yahoo API in the form of JSON
    $data = json_decode($response->getBody(), true);
    // we transform the data into a string to use it for the chatgpt message
    $datas= json_encode($data);

}
    catch(Exception $e){
        return response()->json('API call failed');
    }


       $chat_client = new Client();
       // this is the command sent to chatgpt to get the stock review
        $message='I have the following information about'. $comp .' stock could you please write a detailed analysis and report with bullet points about it : '.$datas;
        // Kindly note the used API key does not Work because it requires a payed account to work
        try{
        $response = $chat_client->post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer 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',
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-3.5-turbo',
                'messages' => [['role' => 'system', 'content' => $message]],
                'max_tokens' => 200,
                'temperature' => 0.2,
            ],
        ]);
        $data = json_decode($response->getBody(), true);

        return response()->json($data);
    }
    catch(Exception $e){
        return response()->json('API call failed');
    }
    }


public function chart(Request $request){
    try{
        $validated_data=$request->validate([
            'company_name'=>'required'
        ]);
    }
    catch (Exception $e) {


        return response()->json('inValid Data');
    }
    $comp=$validated_data['company_name'];
    $url='https://www.alphavantage.co/query?function=SYMBOL_SEARCH&keywords='.$comp.'&apikey=OQ6AKG7RHH5WHOY5';
    $json = file_get_contents($url);
    $data = json_decode($json,true);
    $comp=$data['bestMatches'][0]['1. symbol'];
    $client = new Client();

$response = $client->request('GET', 'https://yahoo-finance127.p.rapidapi.com/earnings/'.$comp, [
	'headers' => [
		'X-RapidAPI-Host' => 'yahoo-finance127.p.rapidapi.com',
		'X-RapidAPI-Key' => 'e37df0ca45msh895064d1580e35dp1334b6jsn4a0af753f28c',
	],
]);
$years=[];
$revenue=[];
$profit=[];
$data = json_decode($response->getBody(), true);
foreach ($data['financialsChart']['yearly'] as $item) {
    $years[] = $item['date'];
    $revenue[]=$item['revenue']['raw'];
    $profit[]= $item['earnings']['raw'];
}
$years= implode(',', $years);
$revenue= implode(',', $revenue);
$profit= implode(',', $profit);
$chart = new QuickChart(array(
  'width' => 500,
  'height' => 300
));

$chart->setConfig('{
  type: "bar",
  data: {
    labels: ['.$years.'],
    datasets: [{
      label: "revenue",
      data: ['.$revenue.'],
    backgroundColor : "rgba(75, 192, 192, 0.2)",
    borderColor : "rgba(75, 192, 192, 1)",
    borderWidth : "1"
    },
    {
    label: "profits",
    data: ['.$profit.'],
    backgroundColor : "rgba(255, 99, 132, 0.2)",
    borderColor : "rgba(255, 99, 132, 1)",
    borderWidth : "1",
    }]
  }
}');
return response()->json($chart->getUrl());
}
}
