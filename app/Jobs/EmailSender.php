<?php

namespace App\Jobs;

use App\Mail\EmailMailable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use GuzzleHttp\Client;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\Mail;

class EmailSender implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public $comp;
    public function __construct($comp)
    {
        //
        $this->comp=$comp;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
        $client = new Client();
        //Validating data

    $comp=$this->comp;
    $url='https://www.alphavantage.co/query?function=SYMBOL_SEARCH&keywords='.$comp.'&apikey=OQ6AKG7RHH5WHOY5';
    $json = file_get_contents($url);

    $data = json_decode($json,true);
    $comp=$data['bestMatches'][0]['1. symbol'];
    $url='https://yahoo-finance127.p.rapidapi.com/key-statistics/'.$comp;
    $response = $client->request('GET', $url, [
        'headers' => [
            'X-RapidAPI-Host' => 'yahoo-finance127.p.rapidapi.com',
            'X-RapidAPI-Key' => '734b1990cemsh7da219e7b57aac6p1750c7jsn5773045cb9f9',
        ],
    ]);
    $data = json_decode($response->getBody(), true);
    $price_to_book=$data['priceToBook']['fmt'];
    $net_income=$data['netIncomeToCommon']['fmt'];
    $url='https://yahoo-finance127.p.rapidapi.com/finance-analytics/'.$comp;
    $response = $client->request('GET', $url, [
        'headers' => [
            'X-RapidAPI-Host' => 'yahoo-finance127.p.rapidapi.com',
            'X-RapidAPI-Key' => '734b1990cemsh7da219e7b57aac6p1750c7jsn5773045cb9f9',
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
            'X-RapidAPI-Key' => '734b1990cemsh7da219e7b57aac6p1750c7jsn5773045cb9f9',
        ],
    ]);
    $data = json_decode($response->getBody(), true);
    $total_liabilities=$data['balanceSheetStatements'][0]['totalLiab']['fmt'];
    $short_term_investment=$data['balanceSheetStatements'][0]['shortTermInvestments']['fmt'];
    $total_assets=$data['balanceSheetStatements'][0]['totalAssets']['fmt'];

    $GPT_message='First I would like you to write a small financial report about this company: '. $this->comp.' Now I would like you
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
                'max_tokens' => 1000,
                'temperature' => 0.2,
            ],
        ]);
        $data = json_decode($response->getBody(), true);

       $dat= $data['choices'][0]['message']['content'];
        $dompdf = new Dompdf();

$html = '<p>'.$dat.'</p>';
$dompdf->loadHtml($html);


$dompdf->render();


$pdfContent = $dompdf->output();
$pdfFileName = $this->comp.'.pdf';

$pdfPath = storage_path('app/public/'.$pdfFileName);


file_put_contents($pdfPath, $pdfContent);


$pdfPath = storage_path('app/public/' . $pdfFileName);
$email="amdmin@example.com";
Mail::to($email)->send(new EmailMailable($pdfPath));
    }
}
