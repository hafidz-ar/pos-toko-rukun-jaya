<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class TransactionController extends Controller
{
    public function history()
    {
        return Inertia::render('salesHistory', [
            'date' => now()->translatedFormat('l, d M Y'),

            'stats' => [
                'totalSalesToday' => 42850000,
                'salesGrowthPercent' => 12,
                'transactionCount' => 148,
                'averagePerTransaction' => 289000,
                'cashPercent' => 65,
                'qrisPercent' => 35,

                'mostSoldItem' => [
                    'name' => 'Portland Cement Type I',
                    'unitsSold' => 42,
                ],
            ],

            'transactions' => [],

            'pagination' => [
                'currentPage' => 1,
                'lastPage' => 1,
                'totalEntries' => 0,
                'from' => 0,
                'to' => 0,
            ],
        ]);
    }
}