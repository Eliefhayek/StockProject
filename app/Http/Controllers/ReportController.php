<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use OpenAI\Laravel\Facades\OpenAI;
use QuickChart;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Exception\RequestException;
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
        return response()->json($e);
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


public function SwotReview(Request $request){
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
$url='https://www.alphavantage.co/query?function=SYMBOL_SEARCH&keywords='.$comp.'&apikey=OQ6AKG7RHH5WHOY5';
$json = file_get_contents($url);

$data = json_decode($json,true);
$comp=$data['bestMatches'][0]['1. symbol'];
$url='https://yahoo-finance127.p.rapidapi.com/key-statistics/'.$comp;
$response = $client->request('GET', $url, [
	'headers' => [
		'X-RapidAPI-Host' => 'yahoo-finance127.p.rapidapi.com',
		'X-RapidAPI-Key' => 'fe6c1becaemshfe5ba5f4b61fb49p132bb1jsn1f6326c6e425',
	],
]);
$data = json_decode($response->getBody(), true);
$price_to_book=$data['priceToBook']['fmt'];
$net_income=$data['netIncomeToCommon']['fmt'];
$url='https://yahoo-finance127.p.rapidapi.com/finance-analytics/'.$comp;
$response = $client->request('GET', $url, [
	'headers' => [
		'X-RapidAPI-Host' => 'yahoo-finance127.p.rapidapi.com',
		'X-RapidAPI-Key' => 'fe6c1becaemshfe5ba5f4b61fb49p132bb1jsn1f6326c6e425',
	],
]);
$data = json_decode($response->getBody(), true);
$return_per_share=$data['revenuePerShare']['fmt'];
$return_per_asset=$data['returnOnAssets']['fmt'];
$return_per_capital=$data['returnOnEquity']['fmt'];
$total_debt=$data['totalDebt']['fmt'];
$ebitda=$data['ebitda']['fmt'];

$url='https://yahoo-finance127.p.rapidapi.com/balance-sheet/'.$comp;
$response = $client->request('GET', $url, [
	'headers' => [
		'X-RapidAPI-Host' => 'yahoo-finance127.p.rapidapi.com',
		'X-RapidAPI-Key' => 'fe6c1becaemshfe5ba5f4b61fb49p132bb1jsn1f6326c6e425',
	],
]);
$data = json_decode($response->getBody(), true);
$total_liabilities=$data['balanceSheetStatements'][0]['totalLiab']['fmt'];
$short_term_investment=$data['balanceSheetStatements'][0]['shortTermInvestments']['fmt'];
$total_assets=$data['balanceSheetStatements'][0]['totalAssets']['fmt'];

$GPT_message='First I would like you to write a small financial report about this company: '. $validated_data['company_name'].' Now I would like you
to write a profitibality report by taking the following parameters into consideration netIncome: '. $net_income. ', earningPerShares: '. $return_per_share. '
and ebitda: ' . $ebitda .' after finishing this report I would Like you to write a SWOT report regarding this company by taking into consideration the following
 competion and industry, return per asset: '. $return_per_asset . ' return per capital: '. $return_per_capital . ', total debt: ' . $total_debt . ', Total Liabalities:
  '. $total_liabilities . ', short_term_investment: ' . $short_term_investment . ', total assets: '. $total_assets. 'and price to book: '. $price_to_book;
  return response()->json($GPT_message);

}
}
