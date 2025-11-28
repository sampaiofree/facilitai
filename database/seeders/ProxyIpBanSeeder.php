<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProxyIpBan;

class ProxyIpBanSeeder extends Seeder
{
    public function run(): void
    {
        $samples = [
            '100.100.100.1',
            '200.200.200.2',
            '50.60.70.80',
        ];

        foreach ($samples as $ip) {
            ProxyIpBan::firstOrCreate(['ip' => $ip]);
        }
    }
}
