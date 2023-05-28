<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use OpenAI\Laravel\Facades\OpenAI;
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
    //!! NOTE: that this code is used in Case that from the frontend we take the company name and Not the stock name
    // it is responsible for taking the company name and getting the stock name that will be used in the second API call
    /*
    $url='https://yahoo-finance127.p.rapidapi.com/search/'.$comp;
    try{
    $response = $client->request('GET', $url, [
            'headers' => [
                'X-RapidAPI-Host' => 'yahoo-finance127.p.rapidapi.com',
                'X-RapidAPI-Key' => 'e37df0ca45msh895064d1580e35dp1334b6jsn4a0af753f28c',
            ],
        ]);
    $company= json_decode($response->getBody(), true);
    $comp=$company['quotes'][0]['symbol'];
    }
    catch(Exception $e){
        return response()->json('API call failed')
    }*/
    $url='https://yahoo-finance127.p.rapidapi.com/finance-analytics/'.$comp;
    try{
    $response = $client->request('GET', $url, [
	    'headers' => [
		    'X-RapidAPI-Host' => 'yahoo-finance127.p.rapidapi.com',
		    'X-RapidAPI-Key' => 'e37df0ca45msh895064d1580e35dp1334b6jsn4a0af753f28c',
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
        $message='I have the following information about'. $comp .' stock could you please write a detailed analysis with bullet points about it : '.$datas;
        // Kindly note the used API key does not Work because it requires
        try{
        $response = $chat_client->post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer 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',
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-3.5-turbo',
                'messages' => [['role' => 'system', 'content' => $message]],
                'max_tokens' => 50,
                'temperature' => 0.2,
            ],
        ]);
        $reply = $response['choices'][0]['message']['content'];

        return response()->json(['reply' => $reply]);
    }
    catch(Exception $e){
        return response()->json('API call failed');
    }
    }


}
