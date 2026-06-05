<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Mine;
use Illuminate\Database\Seeder;

class AreaMineSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'BARORA AREA' => [
                ['2003', 'AMLG. MURAIDIH PHULARITAND OCM', 'OCM'],
                ['2004', 'AMLG. MURAIDIH PHULARITAND UG', 'UG'],
                ['2064', 'DAMODA MINE OCM', 'OCM'],
                ['2109', 'MURAIDIH UG', 'UG'],
                ['2122', 'MADHUBAND UG', 'UG'],
            ],
            'BASTACOLLA AREA' => [
                ['2037', 'BASTACOLLA OCM', 'OCM'],
                ['2038', 'BASTACOLLA UG', 'UG'],
                ['2039', 'RAJAPUR OCM', 'OCM'],
                ['2040', 'KUYA OCM', 'OCM'],
                ['2041', 'KUYA UG', 'UG'],
                ['2042', 'BERA OCM', 'OCM'],
                ['2071', 'DOBARI OCM', 'OCM'],
                ['2072', 'AMLG. EAST BHAGATDIH SIMLABAHA', 'OCM'],
                ['2073', 'GHANOODIH OCM', 'OCM'],
                ['2115', 'RAJAPUR/SJ-HIGHWALL UG MINING', 'UG'],
                ['2129', 'ABDKG COLLIERY OC', 'OCM'],
                ['2130', 'ABDKG COLLIERY UG', 'UG'],
            ],
            'BLOCK E AREA' => [
                ['2117', 'BLOCK E OCP', 'OCM'],
            ],
            'BLOCK-II AREA' => [
                ['2006', 'AMLG. BLOCK II OCM', 'OCM'],
                ['2053', 'MADHUBAN OLD WASHERY', 'WASHERY'],
                ['2102', 'MADHUBAND 5MT WASHERY', 'WASHERY'],
                ['2112', 'HW MINING PROJECT(ABOCP)', 'OCM'],
            ],
            'CHANCH VICTORIA AREA' => [
                ['2049', 'BASANTIMATA-DAHIBARI OCM', 'OCM'],
                ['2050', 'DAMAGORIA COLLIERY OCM', 'OCM'],
                ['2054', 'DAHIBARI WASHERY', 'WASHERY'],
            ],
            'EASTERN JHARIA AREA' => [
                ['2046', 'BHOWRAH (SOUTH) COLLIERY OCM', 'OCM'],
                ['2047', 'AMLG. SUDAMDIH PATHERDIH OCM', 'OCM'],
                ['2058', 'BHOJUDIH WASHERY', 'WASHERY'],
                ['2104', '2 MTPA BHOJUDIH WASHERY', 'WASHERY'],
                ['2114', 'AMAL. BHOWRA N & S COLLIERY', 'OCM'],
            ],
            'EJ AREA' => [
                ['2131', 'Amlabad UG Mine', 'UG'],
            ],
            'EWZ,WASHERY DIVN.' => [
                ['2058', 'BHOJUDIH WASHERY', 'WASHERY'],
                ['2059', 'SUDAMDIH WASHERY', 'WASHERY'],
                ['2063', 'PATHERDIH 5MT WASHERY', 'WASHERY'],
                ['2097', 'PATHERDIH WASHERY(OLD)', 'WASHERY'],
            ],
            'GOVINDPUR AREA' => [
                ['2008', 'AMLG. BLOCK IV GOVINDPUR OCM', 'OCM'],
                ['2009', 'JOGIDIH MINE UG', 'UG'],
                ['2010', 'KHARKHAREE MINE UG', 'UG'],
                ['2011', 'MAHESHPUR MINE UG', 'UG'],
                ['2012', 'NEW AKASHKINAREE COLLIERY OCM', 'OCM'],
                ['2013', 'NEW AKASHKINAREE COLLIERY UG', 'UG'],
                ['2120', 'MAHESHPUR MINE OC', 'OCM'],
                ['2121', 'JOGIDIH MINE OC', 'OCM'],
                ['2127', 'CLUSTER-III GROUP OF MINES OC', 'OCM'],
                ['2128', 'CLUSTER-III GROUP OF MINES UG', 'UG'],
            ],
            'KATRAS AREA' => [
                ['2015', 'SALANPUR MINE UG', 'UG'],
                ['2015', 'ASGKCC UG', 'UG'],
                ['2016', 'AMLG KESHALPUR WESTMUDIDIH OCM', 'OCM'],
                ['2017', 'AMLG KESHALPUR WEST MUDIDIH UG', 'UG'],
                ['2065', 'AGKCC MINE OCM', 'OCM'],
                ['2065', 'ASGKCC OC', 'OCM'],
                ['2126', 'ASGKCC MDO OC', 'OCM'],
            ],
            'KUSUNDA AREA' => [
                ['2026', 'AMLG. DHANSAR INDUSTRY OCM', 'OCM'],
                ['2027', 'NEW GODHUR KUSUNDA ALKUSHA OCM', 'OCM'],
                ['2028', 'NEW GODHUR KUSUNDA ALKUSHA UG', 'UG'],
                ['2029', 'EAST BASSURIYA COLLIERY OCM', 'OCM'],
                ['2030', 'ENA COLLIERY OCM', 'OCM'],
                ['2031', 'GONDUDIH KHAS KUSUNDA OCM', 'OCM'],
                ['2032', 'GONDUDIH KHAS KUSUNDA UG', 'UG'],
            ],
            'LODNA AREA' => [
                ['2044', 'AMLG. N.T.S.T. JEENAGORA OCM', 'OCM'],
                ['2063', 'PATHERDIH 5MT WASHERY', 'WASHERY'],
                ['2074', 'AMLG. JOYRAMPUR COLLIERY OCM', 'OCM'],
                ['2097', 'PATHERDIH WASHERY(OLD)', 'WASHERY'],
                ['2108', 'KUJAMA COLLIERY', 'OCM'],
                ['2113', 'AMALGAMATED NTST KUJAMA OCP', 'OCM'],
            ],
            'POOTKEE BALIHARI AREA' => [
                ['2034', 'GOPALICHUCK OCM', 'OCM'],
                ['2035', 'GOPALICHUCK UG', 'UG'],
                ['2067', 'BHAGABAND MINE UG', 'UG'],
                ['2069', 'P.B.PROJECT UG', 'UG'],
                ['2070', 'KENDUADIH MINE OCM', 'OCM'],
                ['2124', 'PB PROJECT COLLY COAL MINE UG', 'UG'],
                ['2125', 'PB PROJECT COLLY COAL MINE OCM', 'OCM'],
            ],
            'SIJUA AREA' => [
                ['2019', 'KANKANEE OCM', 'OCM'],
                ['2020', 'NICHITPUR OCM', 'OCM'],
                ['2021', 'SENDRA BANSJORA COLIERY OCM', 'OCM'],
                ['2022', 'TETULMARI OCM', 'OCM'],
                ['2023', 'TETULMARI UG', 'UG'],
                ['2024', 'BANSDEOPUR OCM', 'OCM'],
                ['2106', 'MUDIDIH COLLIERY', 'OCM'],
            ],
            'WASHERY DIVISION' => [
                ['2107', 'TATA WASHERY', 'WASHERY'],
            ],
            'WESTERN JHARIA AREA' => [
                ['2052', 'MOONIDIH COLLIERY UG', 'UG'],
                ['2056', 'MOONIDIH WASHERY', 'WASHERY'],
            ],
            'WWZ,WASHERY DIVN.' => [
                ['2055', 'DUGDA WASHERY', 'WASHERY'],
                ['2056', 'MOONIDIH WASHERY', 'WASHERY'],
                ['2057', 'MOHUDA WASHERY', 'WASHERY'],
                ['2098', 'BARORA WASHERY', 'WASHERY'],
            ],
        ];

        foreach ($data as $areaName => $mines) {
            $area = Area::firstOrCreate(
                ['name' => $areaName],
                ['code' => '', 'is_active' => true]
            );

            foreach ($mines as [$code, $name, $type]) {
                Mine::firstOrCreate(
                    ['area_id' => $area->id, 'mine_code' => $code],
                    [
                        'mine_name' => $name,
                        'mine_type' => $type,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
