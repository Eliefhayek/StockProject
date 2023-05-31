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
use Dompdf\Adapter\PDFLib;
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

    $response = $client->post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer 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',
            'Content-Type' => 'application/json',
        ],
        'json' => [
            'model' => 'gpt-3.5-turbo',
            'messages' => [['role' => 'system', 'content' => $GPT_message]],
            'max_tokens' => 1029,
            'temperature' => 0.2,
        ],
    ]);
    $data = json_decode($response->getBody(), true);

    return response()->json($data['choices'][0]['message']['content']);

}
public function CreatePDF(){
    $GPT_Respons='Financial Report on Nike:\n\nNike is a multinational corporation that designs, develops, and sells athletic footwear, apparel, and accessories. The company was founded in 1964 and is headquartered in Beaverton, Oregon. Nike is one of the world largest suppliers of athletic shoes and apparel, with revenue of $37.4 billion in 2020.\n\nProfitability Report on Nike:\n\nNike net income for the fiscal year 2020 was $5.48 billion, and its earnings per share were $32.44. The companys EBITDA was $7.08 billion. These figures indicate that Nike is a highly profitable company with a strong financial position.\n\nSWOT Report on Nike:\n\nStrengths:\n- Strong brand recognition and reputation\n- Diversified product portfolio\n- Innovative product design and development\n- Strong financial position with high returns on assets and capital\n- Large market share in the athletic footwear and apparel industry\n\nWeaknesses:\n- Dependence on third-party manufacturers\n- High levels of debt and liabilities\n- Vulnerability to economic downturns and changes in consumer preferences\n- Limited presence in emerging markets\n\nOpportunities:\n- Expansion into new markets and product categories\n- Growth in e-commerce and digital marketing\n- Increasing demand for sustainable and eco-friendly products\n- Partnerships and collaborations with other companies and organizations\n\nThreats:\n- Intense competition from other athletic footwear and apparel companies\n- Fluctuations in raw material prices and supply chain disruptions\n- Changing consumer preferences and trends\n- Regulatory and legal challenges related to labor practices and environmental impact.';
    $result=explode('Profitability',$GPT_Respons);
    $introduction=$result[0];
    $introduction = str_replace("\n", "", $introduction);
    $result=explode('SWOT',$result[1]);
    $profitibality=$result[0];
    $profitibality= str_replace("\n", "", $profitibality);
    $result=explode('Strengths',$result[1]);
    $result=explode('Weaknesses',$result[1]);
    $Strength=$result[0];
    $Strength= str_replace("\n", "", $Strength);
    $result=explode('Opportunities',$result[1]);
    $weakness=$result[0];
    $weakness=str_replace("\n", "", $weakness);
    $result=explode('Threats',$result[1]);
    $oppurtunities=$result[0];
    $oppurtunities=str_replace("\n","",$oppurtunities);
    $threat=$result[1];
    $threat=str_replace("\n","",$threat);
    $html = '<html>
    <head>
        <title>financial Report</title>
        <br><br>
    </head>
    <body>
        <p>'.$introduction.'</p>
        <br><br>
        <h2>Profitibality</h2>
        <br>
        <p>'.$profitibality.'</p>
        <br><br>
        <h2>SWOT Report</h2>
        <br>
        <h3>Strength</h3>
        <br>
        <p>'.$Strength.'</p>
        <br><br>
        <h3>Weakness</h3>
        <br>
        <p>'.$weakness.'</p>
        <br><br>
        <h3>Oppurunities</h3>
        <br>
        <p>'.$oppurtunities.'</p>
        <br><br>
        <h3>Threats</h3>
        <br>
        <p>'.$threat.'</p>
        <br><br>
    </body>
</html>';
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

return response()->json( $dompdf->stream('sample.pdf'));


}
}
